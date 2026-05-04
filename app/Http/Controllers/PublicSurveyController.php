<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicSurveyController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $survey = Survey::where('public_token', $token)
            ->with(['questions.options'])
            ->firstOrFail();

        if ($survey->status !== 'active') {
            return view('surveys.public-unavailable', [
                'title' => 'نظرسنجی فعال نیست',
                'message' => 'این نظرسنجی در وضعیت «فعال» نیست. در صورت نیاز با مدیر سیستم تماس بگیرید.',
            ]);
        }

        $now = now();
        if ($survey->start_at && $now->lt($survey->start_at)) {
            return view('surveys.public-unavailable', [
                'title' => 'هنوز شروع نشده است',
                'message' => 'زمان شروع این نظرسنجی فرا نرسیده است. لطفاً بعداً دوباره تلاش کنید.',
            ]);
        }
        if ($survey->end_at && $now->gt($survey->end_at)) {
            return view('surveys.public-unavailable', [
                'title' => 'مهلت پاسخ‌دهی به پایان رسیده',
                'message' => 'زمان دریافت پاسخ برای این نظرسنجی تمام شده است.',
            ]);
        }

        if ($survey->require_auth && !Auth::check()) {
            return view('surveys.public-unavailable', [
                'title' => 'ورود لازم است',
                'message' => 'برای شرکت در این نظرسنجی باید وارد حساب کاربری خود شوید.',
            ]);
        }

        $context = $this->buildAccessContext($request, $survey);
        $identityMode = $context['identity_mode'];
        $needsIdentity = $context['needs_identity'];
        $submittedPersonnelCode = $context['submitted_personnel_code'];
        $submittedNationalCode = $context['submitted_national_code'];
        $accessError = $context['access_error'];
        $resolvedPersonnel = $context['resolved_personnel'];
        $audiencePassed = $context['audience_passed'];
        $activeResponse = null;
        $existingAnswers = [];

        if ($audiencePassed) {
            [$activeResponse, $editLockMessage] = $this->resolveAccessibleResponse($survey, $resolvedPersonnel);
            if ($editLockMessage) {
                $audiencePassed = false;
                $accessError = $editLockMessage;
            } elseif ($this->isResponseLimitReachedForNewSubmit($survey, $resolvedPersonnel, $activeResponse)) {
                $audiencePassed = false;
                $accessError = 'ظرفیت پاسخ‌گویی این نظرسنجی تکمیل شده است.';
            } elseif ($activeResponse) {
                $activeResponse->load('answers');
                foreach ($activeResponse->answers as $answer) {
                    $existingAnswers[(string) $answer->question_id] = [
                        'option_id' => $answer->option_id,
                        'option_ids' => $answer->answer_json['option_ids'] ?? [],
                        'text' => $answer->answer_text,
                        'number' => $answer->answer_number,
                        'date' => $answer->answer_date?->format('Y-m-d'),
                    ];
                }
            }
        }

        if ($survey->shuffle_questions) {
            $survey->setRelation('questions', $survey->questions->shuffle()->values());
        }

        foreach ($survey->questions as $question) {
            if ($survey->shuffle_options && $question->relationLoaded('options') && $question->options->isNotEmpty()) {
                $question->setRelation('options', $question->options->shuffle()->values());
            }
        }

        $questionsCount = $survey->questions->count();
        $estimatedDurationMinutes = max(2, (int) ceil($questionsCount * 0.8));
        $participantDisplayName = null;
        if ($resolvedPersonnel) {
            $participantDisplayName = trim($resolvedPersonnel->first_name . ' ' . $resolvedPersonnel->last_name);
        } elseif ($survey->require_auth && Auth::check()) {
            $participantDisplayName = Auth::user()?->name;
        }

        $showIntroStep = filled($survey->intro_text);
        $showAccessGate = $needsIdentity;
        $showCompletedOnLoad = $request->boolean('submitted');

        $wizardFocusQuestionId = null;
        $errorsBag = $request->session()->get('errors');
        if ($errorsBag instanceof \Illuminate\Support\ViewErrorBag) {
            foreach (array_keys($errorsBag->getMessages()) as $key) {
                if (preg_match('/^answers\.(\d+)$/', $key, $m)) {
                    $wizardFocusQuestionId = (int) $m[1];
                    break;
                }
            }
        }

        return view('surveys.public-show', compact(
            'survey',
            'showIntroStep',
            'showAccessGate',
            'audiencePassed',
            'accessError',
            'identityMode',
            'submittedPersonnelCode',
            'submittedNationalCode',
            'estimatedDurationMinutes',
            'questionsCount',
            'participantDisplayName',
            'existingAnswers',
            'activeResponse',
            'showCompletedOnLoad',
            'wizardFocusQuestionId'
        ));
    }

    public function saveDraft(Request $request, string $token): JsonResponse
    {
        $survey = Survey::where('public_token', $token)->with('questions.options')->firstOrFail();
        if (!$survey->allow_partial) {
            return response()->json(['ok' => false, 'message' => 'ذخیره موقت برای این نظرسنجی فعال نیست.'], 422);
        }

        $context = $this->buildAccessContext($request, $survey);
        if (!$context['audience_passed']) {
            return response()->json(['ok' => false, 'message' => $context['access_error'] ?: 'عدم دسترسی'], 403);
        }

        $answersInput = $request->input('answers', []);
        if (!is_array($answersInput)) {
            return response()->json(['ok' => false, 'message' => 'فرمت پاسخ‌ها معتبر نیست.'], 422);
        }

        $response = DB::transaction(function () use ($survey, $context, $answersInput) {
            $response = $this->findOrCreateEditableResponse($survey, $context['resolved_personnel'], false);
            $count = $this->persistAnswers($response, $survey->questions, $answersInput);
            $payload = [
                'answers_count' => $count,
                'last_seen_at' => now(),
            ];
            // ویرایش پاسخ ثبت‌شده: وضعیت را به پیش‌نویس برنگردان؛ فقط پاسخ‌ها به‌روز می‌شوند.
            if ($response->status !== 'submitted') {
                $payload['status'] = 'draft';
            }
            $response->update($payload);
            return $response;
        });

        return response()->json(['ok' => true, 'response_id' => $response->id]);
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $survey = Survey::where('public_token', $token)->with('questions.options')->firstOrFail();
        $context = $this->buildAccessContext($request, $survey);
        if (!$context['audience_passed']) {
            return redirect()
                ->route('surveys.public.show', array_filter([
                    'token' => $token,
                    'personnel_code' => $context['submitted_personnel_code'] ?: null,
                    'national_code' => $context['submitted_national_code'] ?: null,
                ]))
                ->with('status', $context['access_error'] ?: 'دسترسی به ثبت پاسخ ندارید.');
        }

        $answersInput = $request->input('answers', []);
        if (!is_array($answersInput)) {
            return redirect()
                ->route('surveys.public.show', array_filter([
                    'token' => $token,
                    'personnel_code' => $context['submitted_personnel_code'] ?: null,
                    'national_code' => $context['submitted_national_code'] ?: null,
                ]))
                ->withErrors(['answers' => 'پاسخ‌ها معتبر نیست.'])
                ->withInput();
        }

        [$activeResponse, $editLockMessage] = $this->resolveAccessibleResponse($survey, $context['resolved_personnel']);
        if ($editLockMessage) {
            return redirect()
                ->route('surveys.public.show', array_filter([
                    'token' => $token,
                    'personnel_code' => $context['submitted_personnel_code'] ?: null,
                    'national_code' => $context['submitted_national_code'] ?: null,
                ]))
                ->withErrors(['answers' => $editLockMessage])
                ->withInput();
        }
        if ($this->isResponseLimitReachedForNewSubmit($survey, $context['resolved_personnel'], $activeResponse)) {
            return redirect()
                ->route('surveys.public.show', array_filter([
                    'token' => $token,
                    'personnel_code' => $context['submitted_personnel_code'] ?: null,
                    'national_code' => $context['submitted_national_code'] ?: null,
                ]))
                ->withErrors(['answers' => 'ظرفیت پاسخ‌گویی این نظرسنجی تکمیل شده است.'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($survey, $context, $answersInput) {
                $response = $this->findOrCreateEditableResponse($survey, $context['resolved_personnel'], true);
                $count = $this->persistAnswers($response, $survey->questions, $answersInput);
                $this->assertRequiredQuestionsAnswered($survey->questions, $answersInput);

                $response->update([
                    'status' => 'submitted',
                    'answers_count' => $count,
                    'submitted_at' => now(),
                    'last_seen_at' => now(),
                    'edit_token' => $response->edit_token ?: Str::random(48),
                ]);

                $survey->update([
                    'responses_count' => SurveyResponse::where('survey_id', $survey->id)->where('status', 'submitted')->count(),
                ]);
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('surveys.public.show', array_filter([
                    'token' => $token,
                    'personnel_code' => $context['submitted_personnel_code'] ?: null,
                    'national_code' => $context['submitted_national_code'] ?: null,
                ]))
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()->route('surveys.public.show', array_filter([
            'token' => $token,
            'personnel_code' => $context['submitted_personnel_code'] ?: null,
            'national_code' => $context['submitted_national_code'] ?: null,
            'submitted' => 1,
        ]));
    }

    private function resolvePersonnelByIdentity(string $identityMode, string $personnelCode, string $nationalCode): ?Personnel
    {
        $query = Personnel::query();

        if ($identityMode === 'personnel_code') {
            if ($personnelCode === '') {
                return null;
            }
            return $query->where('personnel_code', $personnelCode)->first();
        }

        if ($identityMode === 'national_code') {
            if ($nationalCode === '') {
                return null;
            }
            return $query->where('national_code', $nationalCode)->first();
        }

        if ($identityMode === 'either') {
            if ($personnelCode === '' && $nationalCode === '') {
                return null;
            }

            return $query->where(function ($nested) use ($personnelCode, $nationalCode) {
                if ($personnelCode !== '') {
                    $nested->orWhere('personnel_code', $personnelCode);
                }
                if ($nationalCode !== '') {
                    $nested->orWhere('national_code', $nationalCode);
                }
            })->first();
        }

        return null;
    }

    private function matchesAudienceFilters(Personnel $personnel, array $config): bool
    {
        $modes = $config['modes'];
        if (empty($modes)) {
            return true;
        }

        if (in_array('unit', $modes, true) && !in_array((int) $personnel->unit_id, $config['unit_ids'], true)) {
            return false;
        }
        if (in_array('position', $modes, true) && !in_array((int) $personnel->position_id, $config['position_ids'], true)) {
            return false;
        }
        if (in_array('gender', $modes, true) && !in_array((string) $personnel->gender, $config['genders'], true)) {
            return false;
        }
        if (in_array('personnel', $modes, true) && !in_array((int) $personnel->id, $config['personnel_ids'], true)) {
            return false;
        }

        return true;
    }

    private function buildAccessContext(Request $request, Survey $survey): array
    {
        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);
        $identityMode = $audienceConfig['identity_mode'];
        $needsIdentity = $identityMode !== 'none';
        $submittedPersonnelCode = $this->normalizeDigits(trim((string) $request->input('personnel_code', $request->query('personnel_code', ''))));
        $submittedNationalCode = $this->normalizeDigits(trim((string) $request->input('national_code', $request->query('national_code', ''))));
        $accessError = null;
        $resolvedPersonnel = null;
        $audiencePassed = true;

        if ($needsIdentity) {
            if ($submittedPersonnelCode === '' && $submittedNationalCode === '') {
                $audiencePassed = false;
            } else {
                $resolvedPersonnel = $this->resolvePersonnelByIdentity($identityMode, $submittedPersonnelCode, $submittedNationalCode);
                if (!$resolvedPersonnel) {
                    $audiencePassed = false;
                    $accessError = 'اطلاعات وارد شده معتبر نیست یا در فهرست پرسنل ثبت نشده است.';
                } else {
                    $audiencePassed = $this->matchesAudienceFilters($resolvedPersonnel, $audienceConfig);
                    if (!$audiencePassed) {
                        $accessError = 'شما مجاز به شرکت در این نظرسنجی نیستید.';
                    }
                }
            }
        }

        return [
            'identity_mode' => $identityMode,
            'needs_identity' => $needsIdentity,
            'submitted_personnel_code' => $submittedPersonnelCode,
            'submitted_national_code' => $submittedNationalCode,
            'access_error' => $accessError,
            'resolved_personnel' => $resolvedPersonnel,
            'audience_passed' => $audiencePassed,
        ];
    }

    private function resolveAccessibleResponse(Survey $survey, ?Personnel $personnel): array
    {
        if (!$personnel) {
            return [null, null];
        }

        $response = SurveyResponse::where('survey_id', $survey->id)
            ->where('personnel_id', $personnel->id)
            ->latest()
            ->first();
        if (!$response) {
            return [null, null];
        }

        if ($response->status === 'submitted') {
            if (!$survey->allow_edit) {
                return [null, 'این نظرسنجی امکان ویرایش ندارد.'];
            }
            if ($survey->response_edit_window_hours && $response->submitted_at) {
                $deadline = $response->submitted_at->copy()->addHours((int) $survey->response_edit_window_hours);
                if (now()->greaterThan($deadline)) {
                    return [null, 'مهلت ویرایش پاسخ این نظرسنجی به پایان رسیده است.'];
                }
            }
            return [$response, null];
        }

        if ($response->status === 'draft' && $survey->allow_partial) {
            return [$response, null];
        }

        return [null, null];
    }

    private function isResponseLimitReachedForNewSubmit(Survey $survey, ?Personnel $personnel, ?SurveyResponse $activeResponse): bool
    {
        if (!$survey->response_limit) {
            return false;
        }
        // Editing an existing submitted response does not consume extra capacity.
        if ($activeResponse && $activeResponse->status === 'submitted') {
            return false;
        }

        $submittedCount = SurveyResponse::where('survey_id', $survey->id)->where('status', 'submitted')->count();
        return $submittedCount >= (int) $survey->response_limit;
    }

    /**
     * یک ردیف پاسخ برای این مخاطب پیدا یا می‌سازد.
     *
     * نکته: اگر آخرین پاسخ این کاربر «ثبت نهایی» شده و ویرایش مجاز است، همان ردیف برگردانده می‌شود
     * تا ثبت مجدد همان رکورد به‌روز شود (نه ردیف تکراری).
     */
    private function findOrCreateEditableResponse(Survey $survey, ?Personnel $personnel, bool $forSubmit): SurveyResponse
    {
        $query = SurveyResponse::where('survey_id', $survey->id);
        if ($personnel) {
            $query->where('personnel_id', $personnel->id);
        } else {
            $query->whereNull('personnel_id');
        }
        $existing = $query->latest()->first();

        if ($existing && $existing->status === 'submitted') {
            if (!$survey->allow_edit) {
                abort(403, 'این نظرسنجی امکان ویرایش ندارد.');
            }
            if ($survey->response_edit_window_hours && $existing->submitted_at) {
                $deadline = $existing->submitted_at->copy()->addHours((int) $survey->response_edit_window_hours);
                if (now()->greaterThan($deadline)) {
                    abort(403, 'مهلت ویرایش پاسخ به پایان رسیده است.');
                }
            }

            return $existing;
        }

        if ($existing && $existing->status === 'draft') {
            return $existing;
        }

        return SurveyResponse::create([
            'survey_id' => $survey->id,
            'personnel_id' => $personnel?->id,
            'respondent_name' => $personnel ? trim($personnel->first_name . ' ' . $personnel->last_name) : null,
            'identifier_type' => $personnel ? 'personnel' : null,
            'respondent_identifier' => $personnel ? (string) $personnel->personnel_code : null,
            'is_anonymous' => $survey->is_anonymous,
            'status' => $forSubmit ? 'submitted' : 'draft',
            'last_seen_at' => now(),
        ]);
    }

    private function assertRequiredQuestionsAnswered($questions, array $answersInput): void
    {
        $messages = [];
        foreach ($questions as $question) {
            if (!$question->is_required) {
                continue;
            }
            $raw = $answersInput[$question->id] ?? null;
            if (!$this->isQuestionAnswered($question, $raw)) {
                $messages['answers.' . $question->id] = 'تکمیل سوال «' . $question->title . '» الزامی است.';
            }
        }
        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function isQuestionAnswered(SurveyQuestion $question, mixed $raw): bool
    {
        if (in_array($question->type, ['multiple_choice', 'dropdown', 'yes_no', 'linear_scale'], true)) {
            return is_array($raw) ? !empty($raw['option_id'] ?? null) : !empty($raw);
        }
        if ($question->type === 'rating') {
            if (!is_array($raw)) {
                return !empty($raw);
            }
            return !empty($raw['option_id'] ?? null) || trim((string) ($raw['value'] ?? '')) !== '';
        }
        if ($question->type === 'checkboxes') {
            if (is_array($raw) && isset($raw['option_ids'])) {
                return !empty(array_filter((array) $raw['option_ids']));
            }
            return !empty((array) $raw);
        }
        if (is_array($raw)) {
            return trim((string) ($raw['value'] ?? '')) !== '';
        }
        return trim((string) $raw) !== '';
    }

    private function persistAnswers(SurveyResponse $response, $questions, array $answersInput): int
    {
        $saved = 0;
        foreach ($questions as $question) {
            $raw = $answersInput[$question->id] ?? null;
            $normalized = $this->normalizeAnswer($question, $raw);
            if ($normalized === null) {
                SurveyResponseAnswer::where('response_id', $response->id)->where('question_id', $question->id)->delete();
                continue;
            }
            SurveyResponseAnswer::updateOrCreate(
                ['response_id' => $response->id, 'question_id' => $question->id],
                $normalized
            );
            $saved++;
        }
        return $saved;
    }

    private function normalizeAnswer(SurveyQuestion $question, mixed $raw): ?array
    {
        $base = [
            'option_id' => null,
            'answer_text' => null,
            'answer_number' => null,
            'answer_date' => null,
            'answer_json' => null,
        ];

        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }

        if (in_array($question->type, ['multiple_choice', 'dropdown', 'yes_no', 'linear_scale'], true)) {
            $optionId = (int) (is_array($raw) ? ($raw['option_id'] ?? 0) : $raw);
            if ($optionId <= 0) {
                return null;
            }
            return array_merge($base, ['option_id' => $optionId]);
        }

        if ($question->type === 'rating') {
            $optionId = (int) (is_array($raw) ? ($raw['option_id'] ?? 0) : 0);
            if ($optionId > 0) {
                return array_merge($base, ['option_id' => $optionId]);
            }
            $value = is_array($raw) ? ($raw['value'] ?? null) : $raw;
            if ($value === null || $value === '') {
                return null;
            }
            return array_merge($base, ['answer_number' => (float) $value]);
        }

        if ($question->type === 'checkboxes') {
            $optionIds = is_array($raw) ? ($raw['option_ids'] ?? $raw) : [];
            $optionIds = array_values(array_unique(array_filter(array_map('intval', (array) $optionIds), fn ($id) => $id > 0)));
            if (empty($optionIds)) {
                return null;
            }
            return array_merge($base, ['answer_json' => ['option_ids' => $optionIds]]);
        }

        if ($question->type === 'number') {
            $value = is_array($raw) ? ($raw['value'] ?? null) : $raw;
            if ($value === null || $value === '') {
                return null;
            }
            return array_merge($base, ['answer_number' => (float) $value]);
        }

        if ($question->type === 'date') {
            $value = is_array($raw) ? ($raw['value'] ?? null) : $raw;
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }
            $dateValue = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
            return array_merge($base, ['answer_date' => $dateValue, 'answer_text' => $value]);
        }

        $value = is_array($raw) ? ($raw['value'] ?? null) : $raw;
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        return array_merge($base, ['answer_text' => $value]);
    }

    private function normalizeAudienceConfig(mixed $value): array
    {
        $fallback = [
            'identity_mode' => 'none',
            'modes' => [],
            'unit_ids' => [],
            'genders' => [],
            'position_ids' => [],
            'personnel_ids' => [],
        ];

        if (!is_array($value)) {
            return $fallback;
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        if ($isList) {
            return $fallback;
        }

        $identityMode = $value['identity_mode'] ?? 'none';

        return [
            'identity_mode' => in_array($identityMode, ['none', 'personnel_code', 'national_code', 'either'], true)
                ? $identityMode
                : 'none',
            'modes' => array_values(array_filter((array) ($value['modes'] ?? []), fn ($mode) => in_array($mode, ['unit', 'gender', 'position', 'personnel'], true))),
            'unit_ids' => array_values(array_map('intval', (array) ($value['unit_ids'] ?? []))),
            'genders' => array_values(array_filter((array) ($value['genders'] ?? []), fn ($gender) => in_array($gender, ['male', 'female', 'other'], true))),
            'position_ids' => array_values(array_map('intval', (array) ($value['position_ids'] ?? []))),
            'personnel_ids' => array_values(array_map('intval', (array) ($value['personnel_ids'] ?? []))),
        ];
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);
    }
}
