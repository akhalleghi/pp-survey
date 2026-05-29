<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseAnswer;
use App\Services\Survey\SurveySmsOtpService;
use App\Support\PublicSurveyAccessSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicSurveyController extends Controller
{
    public function __construct(
        private readonly SurveySmsOtpService $surveySmsOtpService,
    ) {}

    public function show(Request $request, string $token): View|RedirectResponse
    {
        if ($request->hasAny(['personnel_code', 'national_code', 'submitted'])) {
            if ($request->boolean('submitted')) {
                session()->flash('survey_completed', true);
            }

            return redirect()->route('surveys.public.show', ['token' => $token]);
        }

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
        if ($survey->end_at && $now->isAfter($survey->end_at->copy()->endOfDay())) {
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

        $context = $this->buildAccessContext($survey);
        $identityMode = $context['identity_mode'];
        $needsIdentity = $context['needs_identity'];
        $requireSmsOtp = $context['require_sms_otp'];
        $showOtpStep = $context['show_otp_step'];
        $submittedPersonnelCode = old('personnel_code', '');
        $submittedNationalCode = old('national_code', '');
        $accessError = $context['access_error'] ?: session('access_error');
        $resolvedPersonnel = $context['resolved_personnel'];
        $pendingPersonnel = $context['pending_personnel'];
        $audiencePassed = $context['audience_passed'];
        $otpNotice = session('otp_notice');
        $otpCooldownSeconds = (int) session('otp_cooldown', 0);
        if ($showOtpStep && $pendingPersonnel && $otpCooldownSeconds <= 0) {
            $otpCooldownSeconds = $this->surveySmsOtpService->resendCooldownRemaining($survey, $pendingPersonnel);
        }
        $maskedMobile = $pendingPersonnel
            ? SurveySmsOtpService::maskMobile((string) $pendingPersonnel->mobile)
            : null;
        $otpCodeLength = (int) config('survey_otp.code_length', 6);
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
                        'file_path' => $answer->answer_json['file_path'] ?? null,
                        'file_name' => $answer->answer_json['file_name'] ?? null,
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

        $questionsCount = $survey->questions->reject(fn ($q) => $q->isStaticDisplay())->count();
        $estimatedDurationMinutes = max(2, (int) ceil(max(1, $questionsCount) * 0.8));
        $participantDisplayName = null;
        if ($resolvedPersonnel) {
            $participantDisplayName = trim($resolvedPersonnel->first_name . ' ' . $resolvedPersonnel->last_name);
        } elseif ($survey->require_auth && Auth::check()) {
            $participantDisplayName = Auth::user()?->name;
        }

        $showIntroStep = filled($survey->intro_text);
        $showAccessGate = $needsIdentity;
        $showCompletedOnLoad = (bool) session('survey_completed', false);

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
            'requireSmsOtp',
            'showOtpStep',
            'pendingPersonnel',
            'maskedMobile',
            'otpNotice',
            'otpCooldownSeconds',
            'otpCodeLength',
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

    public function verifyAccess(Request $request, string $token): RedirectResponse
    {
        $survey = Survey::where('public_token', $token)->firstOrFail();
        if ($survey->status !== 'active'
            || ($survey->start_at && now()->lt($survey->start_at))
            || ($survey->end_at && now()->isAfter($survey->end_at->copy()->endOfDay()))
            || ($survey->require_auth && ! Auth::check())) {
            return redirect()->route('surveys.public.show', ['token' => $token]);
        }

        $context = $this->buildAccessContextFromCredentials($request, $survey);
        if (! $context['audience_passed'] || ! $context['resolved_personnel']) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->withInput($request->only(['personnel_code', 'national_code']))
                ->with('access_error', $context['access_error'] ?: 'اطلاعات وارد شده معتبر نیست.');
        }

        $personnel = $context['resolved_personnel'];
        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);

        if ($this->requiresSmsOtp($audienceConfig)) {
            $sendResult = $this->surveySmsOtpService->sendForPersonnel($survey, $personnel);
            if (! $sendResult['ok']) {
                return redirect()
                    ->route('surveys.public.show', ['token' => $token])
                    ->withInput($request->only(['personnel_code', 'national_code']))
                    ->with('access_error', $sendResult['message']);
            }

            PublicSurveyAccessSession::setPendingOtp($survey, $personnel);

            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->with('otp_notice', $sendResult['message'])
                ->with('otp_cooldown', $sendResult['cooldown_seconds'] ?? 0);
        }

        PublicSurveyAccessSession::grant($survey, $personnel);

        return redirect()->route('surveys.public.show', ['token' => $token]);
    }

    public function resendOtp(Request $request, string $token): JsonResponse
    {
        $survey = Survey::where('public_token', $token)->firstOrFail();
        if (! $this->surveyIsAccessible($survey)) {
            return response()->json(['ok' => false, 'message' => 'دسترسی به این نظرسنجی ممکن نیست.'], 403);
        }

        $pendingPersonnel = PublicSurveyAccessSession::resolvePendingPersonnel($survey);
        if (! $pendingPersonnel) {
            return response()->json(['ok' => false, 'message' => 'نشست تایید پیامکی یافت نشد. لطفاً دوباره اطلاعات پرسنلی را وارد کنید.'], 403);
        }

        $sendResult = $this->surveySmsOtpService->sendForPersonnel($survey, $pendingPersonnel);

        return response()->json([
            'ok' => $sendResult['ok'],
            'message' => $sendResult['message'],
            'cooldown_seconds' => $sendResult['cooldown_seconds'] ?? 0,
        ], $sendResult['ok'] ? 200 : 429);
    }

    public function verifyOtp(Request $request, string $token): RedirectResponse
    {
        $survey = Survey::where('public_token', $token)->firstOrFail();
        if (! $this->surveyIsAccessible($survey)) {
            return redirect()->route('surveys.public.show', ['token' => $token]);
        }

        $pendingPersonnel = PublicSurveyAccessSession::resolvePendingPersonnel($survey);
        if (! $pendingPersonnel) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->with('access_error', 'نشست تایید پیامکی منقضی شده است. لطفاً دوباره اطلاعات پرسنلی را وارد کنید.');
        }

        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);
        if (! $this->matchesAudienceFilters($pendingPersonnel, $audienceConfig)) {
            PublicSurveyAccessSession::clear($survey);

            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->with('access_error', 'شما مجاز به شرکت در این نظرسنجی نیستید.');
        }

        $otpCode = $this->normalizeDigits(trim((string) $request->input('otp_code', '')));
        $verifyResult = $this->surveySmsOtpService->verify($survey, $pendingPersonnel, $otpCode);
        if (! $verifyResult['ok']) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->with('access_error', $verifyResult['message']);
        }

        PublicSurveyAccessSession::grant($survey, $pendingPersonnel, true);

        return redirect()
            ->route('surveys.public.show', ['token' => $token])
            ->with('otp_notice', $verifyResult['message']);
    }

    public function saveDraft(Request $request, string $token): JsonResponse
    {
        $survey = Survey::where('public_token', $token)->with('questions.options')->firstOrFail();
        if (!$survey->allow_partial) {
            return response()->json(['ok' => false, 'message' => 'ذخیره موقت برای این نظرسنجی فعال نیست.'], 422);
        }

        $context = $this->buildAccessContext($survey);
        if (!$context['audience_passed']) {
            return response()->json(['ok' => false, 'message' => $context['access_error'] ?: 'عدم دسترسی'], 403);
        }

        $answersInput = $request->input('answers', []);
        if (!is_array($answersInput)) {
            return response()->json(['ok' => false, 'message' => 'فرمت پاسخ‌ها معتبر نیست.'], 422);
        }

        try {
            $response = DB::transaction(function () use ($survey, $context, $answersInput, $request) {
                $this->assertAnswerFormatsValid($survey->questions, $answersInput);
                $response = $this->findOrCreateEditableResponse($survey, $context['resolved_personnel'], false);
                $count = $this->persistAnswers($response, $survey->questions, $answersInput, $request);
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
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'داده‌های واردشده معتبر نیست.',
            ], 422);
        }

        return response()->json(['ok' => true, 'response_id' => $response->id]);
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $survey = Survey::where('public_token', $token)->with('questions.options')->firstOrFail();
        $context = $this->buildAccessContext($survey);
        if (!$context['audience_passed']) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->with('access_error', $context['access_error'] ?: 'دسترسی به ثبت پاسخ ندارید. لطفاً دوباره هویت خود را تأیید کنید.');
        }

        $answersInput = $request->input('answers', []);
        if (!is_array($answersInput)) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->withErrors(['answers' => 'پاسخ‌ها معتبر نیست.'])
                ->withInput();
        }

        [$activeResponse, $editLockMessage] = $this->resolveAccessibleResponse($survey, $context['resolved_personnel']);
        if ($editLockMessage) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->withErrors(['answers' => $editLockMessage])
                ->withInput();
        }
        if ($this->isResponseLimitReachedForNewSubmit($survey, $context['resolved_personnel'], $activeResponse)) {
            return redirect()
                ->route('surveys.public.show', ['token' => $token])
                ->withErrors(['answers' => 'ظرفیت پاسخ‌گویی این نظرسنجی تکمیل شده است.'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($survey, $context, $answersInput, $request) {
                $this->assertAnswerFormatsValid($survey->questions, $answersInput);
                $response = $this->findOrCreateEditableResponse($survey, $context['resolved_personnel'], true);
                $count = $this->persistAnswers($response, $survey->questions, $answersInput, $request);
                $this->assertRequiredQuestionsAnswered($survey->questions, $answersInput, $request);

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
                ->route('surveys.public.show', ['token' => $token])
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('surveys.public.show', ['token' => $token])
            ->with('survey_completed', true);
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
        return \App\Support\SurveyAudience::personnelMatches($personnel, $config);
    }

    private function buildAccessContext(Survey $survey): array
    {
        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);
        $identityMode = $audienceConfig['identity_mode'];
        $needsIdentity = $identityMode !== 'none';
        $requireSmsOtp = $this->requiresSmsOtp($audienceConfig);
        $accessError = null;
        $resolvedPersonnel = null;
        $pendingPersonnel = null;
        $showOtpStep = false;
        $audiencePassed = true;

        if ($needsIdentity) {
            $pendingPersonnel = PublicSurveyAccessSession::resolvePendingPersonnel($survey);
            $resolvedPersonnel = PublicSurveyAccessSession::resolvePersonnel($survey);

            if ($requireSmsOtp && $resolvedPersonnel && ! PublicSurveyAccessSession::isSmsVerified($survey)) {
                PublicSurveyAccessSession::clear($survey);
                $resolvedPersonnel = null;
            }

            if ($pendingPersonnel) {
                $showOtpStep = true;
                $audiencePassed = false;
            } elseif (! $resolvedPersonnel) {
                $audiencePassed = false;
            } else {
                $audiencePassed = $this->matchesAudienceFilters($resolvedPersonnel, $audienceConfig);
                if (! $audiencePassed) {
                    PublicSurveyAccessSession::clear($survey);
                    $resolvedPersonnel = null;
                    $accessError = 'شما مجاز به شرکت در این نظرسنجی نیستید.';
                }
            }
        }

        return [
            'identity_mode' => $identityMode,
            'needs_identity' => $needsIdentity,
            'require_sms_otp' => $requireSmsOtp,
            'show_otp_step' => $showOtpStep,
            'access_error' => $accessError,
            'resolved_personnel' => $resolvedPersonnel,
            'pending_personnel' => $pendingPersonnel,
            'audience_passed' => $audiencePassed,
        ];
    }

    private function buildAccessContextFromCredentials(Request $request, Survey $survey): array
    {
        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);
        $identityMode = $audienceConfig['identity_mode'];
        $needsIdentity = $identityMode !== 'none';
        $submittedPersonnelCode = $this->normalizeDigits(trim((string) $request->input('personnel_code', '')));
        $submittedNationalCode = $this->normalizeDigits(trim((string) $request->input('national_code', '')));
        $accessError = null;
        $resolvedPersonnel = null;
        $audiencePassed = true;

        if (! $needsIdentity) {
            return [
                'identity_mode' => $identityMode,
                'needs_identity' => false,
                'access_error' => null,
                'resolved_personnel' => null,
                'audience_passed' => true,
            ];
        }

        if ($submittedPersonnelCode === '' && $submittedNationalCode === '') {
            $audiencePassed = false;
            $accessError = 'اطلاعات پرسنلی را وارد کنید.';
        } else {
            $resolvedPersonnel = $this->resolvePersonnelByIdentity($identityMode, $submittedPersonnelCode, $submittedNationalCode);
            if (! $resolvedPersonnel) {
                $audiencePassed = false;
                $accessError = 'اطلاعات وارد شده معتبر نیست یا در فهرست پرسنل ثبت نشده است.';
            } else {
                $audiencePassed = $this->matchesAudienceFilters($resolvedPersonnel, $audienceConfig);
                if (! $audiencePassed) {
                    $accessError = 'شما مجاز به شرکت در این نظرسنجی نیستید.';
                }
            }
        }

        return [
            'identity_mode' => $identityMode,
            'needs_identity' => $needsIdentity,
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

    private function assertRequiredQuestionsAnswered($questions, array $answersInput, Request $request): void
    {
        $messages = [];
        foreach ($questions as $question) {
            if ($question->isStaticDisplay() || !$question->is_required) {
                continue;
            }
            $raw = $answersInput[$question->id] ?? null;
            if (!$this->isQuestionAnswered($question, $raw, $request, (int) $question->id)) {
                $messages['answers.' . $question->id] = 'تکمیل سوال «' . $question->title . '» الزامی است.';
            }
        }
        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function assertAnswerFormatsValid($questions, array $answersInput): void
    {
        $messages = [];
        foreach ($questions as $question) {
            if ($question->isStaticDisplay() || $question->type !== 'email') {
                continue;
            }

            $raw = $answersInput[$question->id] ?? null;
            $value = trim((string) (is_array($raw) ? ($raw['value'] ?? '') : ($raw ?? '')));
            if ($value === '') {
                continue;
            }

            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $messages['answers.' . $question->id] = 'آدرس ایمیل واردشده معتبر نیست. لطفاً یک ایمیل صحیح وارد کنید (مثال: name@example.com).';
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function isQuestionAnswered(SurveyQuestion $question, mixed $raw, Request $request, int $questionId): bool
    {
        if ($question->isStaticDisplay()) {
            return true;
        }

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
        if ($question->type === 'file_upload') {
            if ($request->hasFile('answers.' . $questionId . '.file')) {
                return true;
            }
            if (is_array($raw) && !empty($raw['current_file'])) {
                return true;
            }
            return false;
        }
        if (is_array($raw)) {
            return trim((string) ($raw['value'] ?? '')) !== '';
        }
        return trim((string) $raw) !== '';
    }

    private function persistAnswers(SurveyResponse $response, $questions, array $answersInput, Request $request): int
    {
        $saved = 0;
        foreach ($questions as $question) {
            if ($question->isStaticDisplay()) {
                continue;
            }

            $raw = $answersInput[$question->id] ?? null;
            $file = $request->file('answers.' . $question->id . '.file');
            $normalized = $this->normalizeAnswer($question, $raw, $file, $response);
            if ($normalized === null) {
                $existing = SurveyResponseAnswer::where('response_id', $response->id)->where('question_id', $question->id)->first();
                if ($existing && $question->type === 'file_upload') {
                    $oldPath = $existing->answer_json['file_path'] ?? null;
                    if (is_string($oldPath) && $oldPath !== '') {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
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

    private function normalizeAnswer(SurveyQuestion $question, mixed $raw, ?UploadedFile $uploadedFile, SurveyResponse $response): ?array
    {
        $base = [
            'option_id' => null,
            'answer_text' => null,
            'answer_number' => null,
            'answer_date' => null,
            'answer_json' => null,
        ];

        if ($raw === null || $raw === '' || $raw === []) {
            if ($question->type !== 'file_upload' || !$uploadedFile) {
                return null;
            }
        }

        if (in_array($question->type, ['multiple_choice', 'dropdown', 'yes_no', 'linear_scale'], true)) {
            $optionId = (int) (is_array($raw) ? ($raw['option_id'] ?? 0) : $raw);
            if ($optionId <= 0 || ! $this->optionBelongsToQuestion($question, $optionId)) {
                return null;
            }
            return array_merge($base, ['option_id' => $optionId]);
        }

        if ($question->type === 'rating') {
            $optionId = (int) (is_array($raw) ? ($raw['option_id'] ?? 0) : 0);
            if ($optionId > 0 && $this->optionBelongsToQuestion($question, $optionId)) {
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
            $optionIds = array_values(array_filter($optionIds, fn ($id) => $this->optionBelongsToQuestion($question, $id)));
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

        if ($question->type === 'file_upload') {
            $currentPath = is_array($raw) ? trim((string) ($raw['current_file'] ?? '')) : '';
            $currentName = is_array($raw) ? trim((string) ($raw['current_file_name'] ?? '')) : '';
            if (!$uploadedFile) {
                if ($currentPath === '' || ! $this->storedFileBelongsToResponse($response, $question, $currentPath)) {
                    return null;
                }

                return array_merge($base, [
                    'answer_json' => [
                        'file_path' => $currentPath,
                        'file_name' => $currentName,
                        'file_size' => null,
                        'mime_type' => null,
                    ],
                ]);
            }

            $extAllowedRaw = (string) ($question->settings['allowed_extensions'] ?? '');
            $allowed = collect(explode(',', str_replace('،', ',', $extAllowedRaw)))
                ->map(static fn ($x) => mb_strtolower(trim((string) $x)))
                ->filter()
                ->map(static fn ($x) => ltrim($x, '.'))
                ->values()
                ->all();
            $maxKb = (int) ($question->settings['max_file_size_kb'] ?? 0);
            if ($maxKb <= 0 || empty($allowed)) {
                throw ValidationException::withMessages([
                    'answers.' . $question->id => 'تنظیمات سوال فایل کامل نیست. حداکثر حجم و پسوندهای مجاز را در طراحی سوال تعیین کنید.',
                ]);
            }

            $ext = mb_strtolower((string) $uploadedFile->getClientOriginalExtension());
            if ($ext === '' || !in_array($ext, $allowed, true)) {
                throw ValidationException::withMessages([
                    'answers.' . $question->id => 'پسوند فایل مجاز نیست. فرمت‌های مجاز: ' . implode(', ', $allowed),
                ]);
            }
            if ($uploadedFile->getSize() > ($maxKb * 1024)) {
                throw ValidationException::withMessages([
                    'answers.' . $question->id => 'حجم فایل بیشتر از حد مجاز است (' . number_format($maxKb) . 'KB).',
                ]);
            }

            $path = $uploadedFile->store('survey-uploads/' . $question->survey_id . '/' . $response->id, 'public');
            if ($currentPath !== '') {
                Storage::disk('public')->delete($currentPath);
            }

            return array_merge($base, [
                'answer_text' => $uploadedFile->getClientOriginalName(),
                'answer_json' => [
                    'file_path' => $path,
                    'file_name' => $uploadedFile->getClientOriginalName(),
                    'file_size' => $uploadedFile->getSize(),
                    'mime_type' => $uploadedFile->getClientMimeType(),
                ],
            ]);
        }

        $value = is_array($raw) ? ($raw['value'] ?? null) : $raw;
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if ($question->type === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages([
                'answers.' . $question->id => 'آدرس ایمیل واردشده معتبر نیست. لطفاً یک ایمیل صحیح وارد کنید (مثال: name@example.com).',
            ]);
        }

        return array_merge($base, ['answer_text' => $value]);
    }

    private function normalizeAudienceConfig(mixed $value): array
    {
        return \App\Support\SurveyAudience::normalize($value);
    }

    private function requiresSmsOtp(array $audienceConfig): bool
    {
        return ($audienceConfig['identity_mode'] ?? 'none') !== 'none'
            && (bool) ($audienceConfig['require_sms_otp'] ?? false);
    }

    private function surveyIsAccessible(Survey $survey): bool
    {
        if ($survey->status !== 'active') {
            return false;
        }
        if ($survey->start_at && now()->lt($survey->start_at)) {
            return false;
        }
        if ($survey->end_at && now()->isAfter($survey->end_at->copy()->endOfDay())) {
            return false;
        }
        if ($survey->require_auth && ! Auth::check()) {
            return false;
        }

        return true;
    }

    private function optionBelongsToQuestion(SurveyQuestion $question, int $optionId): bool
    {
        if ($optionId <= 0) {
            return false;
        }
        if ($question->relationLoaded('options')) {
            return $question->options->contains('id', $optionId);
        }

        return $question->options()->whereKey($optionId)->exists();
    }

    private function storedFileBelongsToResponse(SurveyResponse $response, SurveyQuestion $question, string $path): bool
    {
        $existing = SurveyResponseAnswer::query()
            ->where('response_id', $response->id)
            ->where('question_id', $question->id)
            ->first();
        if (! $existing) {
            return false;
        }

        return is_string($existing->answer_json['file_path'] ?? null)
            && $existing->answer_json['file_path'] === $path;
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
