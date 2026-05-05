<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\AuthorizesSurveyAccess;
use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseAnswer;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SurveyController extends Controller
{
    use AuthorizesSurveyAccess;

    public function index(Request $request): View
    {
        $admin = current_admin();
        $search = $request->query('search');
        $allowedStatuses = ['active', 'draft', 'closed', 'pending_approval'];
        $statusFilter = in_array($request->query('status'), $allowedStatuses, true) ? $request->query('status') : null;

        $surveysQuery = Survey::with(['unit', 'creator', 'publishRequestedBy'])
            ->withCount([
                'responses as submitted_responses_count' => fn ($q) => $q->where('status', 'submitted'),
                'responses as responses_records_count',
            ])
            ->when($admin && $admin->isSupervisor(), fn ($q) => $q->where('created_by_admin_user_id', $admin->id))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhereHas('unit', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest();

        $surveys = $surveysQuery->paginate(10)->withQueryString();

        $unitsQuery = Unit::query()->orderBy('name');
        if ($admin && $admin->isSupervisor()) {
            $unitsQuery->whereIn('id', $admin->supervisedUnitIds());
        }
        $units = $unitsQuery->get(['id', 'name']);
        $audiencePresets = ['همه کاربران', 'براساس واحد', 'براساس جنسیت', 'براساس سمت', 'براساس مدرک تحصیلی', 'انتخابی توسط ادمین'];

        if ($admin && $admin->isSupervisor()) {
            $scope = Survey::query()->where('created_by_admin_user_id', $admin->id);
            $avgQuestions = (clone $scope)->avg('questions_count') ?? 0;
            $surveyIds = (clone $scope)->pluck('id');
            $metrics = [
                'active' => (clone $scope)->where('status', 'active')->count(),
                'pending_approval' => (clone $scope)->where('status', 'pending_approval')->count(),
                'responses' => SurveyResponse::whereIn('survey_id', $surveyIds)->where('status', 'submitted')->count(),
                'avg_questions' => round((float) $avgQuestions, 1),
                'closed' => (clone $scope)->where('status', 'closed')->count(),
            ];
        } else {
            $avgQuestions = Survey::avg('questions_count') ?? 0;
            $metrics = [
                'active' => Survey::where('status', 'active')->count(),
                'pending_approval' => Survey::where('status', 'pending_approval')->count(),
                'responses' => SurveyResponse::where('status', 'submitted')->count(),
                'avg_questions' => round($avgQuestions, 1),
                'closed' => Survey::where('status', 'closed')->count(),
            ];
        }

        return view('admin.surveys', compact('surveys', 'units', 'audiencePresets', 'search', 'statusFilter', 'metrics', 'admin'));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = current_admin();
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
        if ($admin instanceof AdminUser && $admin->isSupervisor()) {
            $allowedUnits = $admin->supervisedUnitIds();
            $rules['unit_id'] = ['required', Rule::in($allowedUnits)];
        }

        $validated = $request->validateWithBag('createSurvey', $rules);

        Survey::create([
            'title' => $validated['title'],
            'unit_id' => $validated['unit_id'] ?? null,
            'created_by_admin_user_id' => $admin?->id,
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
            'questions_count' => 0,
            'responses_count' => 0,
            'response_window_hours' => 48,
            'response_limit' => null,
            'response_edit_window_hours' => null,
            'is_active' => false,
            'is_anonymous' => true,
            'require_auth' => false,
            'track_location' => false,
            'prevent_multiple_submissions' => true,
            'allow_edit' => true,
            'allow_partial' => true,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'show_results_after_submit' => false,
            'result_visibility' => 'private',
            'audience_filters' => [],
            'tags' => [],
            'start_at' => null,
            'end_at' => null,
            'thank_you_message' => null,
            'intro_text' => null,
            'notification_emails' => [],
        ]);

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'نظرسنجی جدید با موفقیت ثبت شد.');
    }

    public function edit(Survey $survey): View
    {
        $this->authorizeSurveyAccess($survey);

        $admin = current_admin();

        $audiencePresets = [
            'unit' => 'براساس واحد',
            'gender' => 'براساس جنسیت',
            'position' => 'براساس سمت',
            'personnel' => 'انتخابی توسط ادمین',
        ];
        $survey->load('creator');
        $statusOptions = [
            'draft' => 'در حال آماده سازی',
            'pending_approval' => 'در انتظار تایید مدیر',
            'active' => 'فعال',
            'closed' => 'بسته شده',
        ];
        $supervisorPublishRestricted = $admin instanceof AdminUser
            && $admin->isSupervisor()
            && $survey->creator
            && $survey->creator->isSupervisor()
            && $survey->creator->requires_survey_publish_approval
            && (int) $survey->created_by_admin_user_id === (int) $admin->id;
        if ($supervisorPublishRestricted) {
            unset($statusOptions['active']);
            if ($survey->status !== 'pending_approval') {
                unset($statusOptions['pending_approval']);
            }
        }
        $resultVisibilityOptions = ['private' => 'خصوصی', 'public' => 'عمومی', 'after_close' => 'پس از بسته شدن'];
        $identityModeOptions = [
            'none' => 'بدون احراز هویت پرسنلی',
            'personnel_code' => 'فقط کد پرسنلی',
            'national_code' => 'فقط کد ملی',
            'either' => 'کد پرسنلی یا کد ملی',
        ];
        $genderOptions = [
            'male' => 'مرد',
            'female' => 'زن',
            'other' => 'سایر',
        ];

        $backgroundImages = collect(glob(public_path('bg-images/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE))
            ->map(fn ($path) => basename($path))
            ->values()
            ->all();
        $unitsQuery = Unit::query()->orderBy('name');
        if ($admin instanceof AdminUser && $admin->isSupervisor()) {
            $unitsQuery->whereIn('id', $admin->supervisedUnitIds());
        }
        $units = $unitsQuery->get(['id', 'name']);
        $positions = Position::query()->orderBy('name')->get(['id', 'name']);
        $personnelOptions = Personnel::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'personnel_code', 'national_code']);

        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);
        $publicThemeForForm = array_merge(Survey::defaultPublicTheme(), $survey->public_theme ?? []);
        if (old('public_theme')) {
            $publicThemeForForm = array_merge($publicThemeForForm, old('public_theme'));
        }

        return view(
            'admin.surveys-settings',
            compact(
                'survey',
                'audiencePresets',
                'statusOptions',
                'resultVisibilityOptions',
                'identityModeOptions',
                'genderOptions',
                'backgroundImages',
                'units',
                'positions',
                'personnelOptions',
                'audienceConfig',
                'publicThemeForForm',
                'supervisorPublishRestricted'
            )
        );
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        $backgroundPresets = collect(glob(public_path('bg-images/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE))
            ->map(fn ($path) => basename($path))
            ->values()
            ->all();
        $backgroundPresetOptions = array_merge(['none'], $backgroundPresets);

        $normalizeDateInput = function (?string $value): ?string {
            if (!$value) {
                return null;
            }
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            $normalized = strtr($trimmed, [
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
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
                return $normalized;
            }
            if (preg_match('/^(\d{3,4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $normalized, $matches)) {
                $jy = (int) $matches[1];
                $jm = (int) $matches[2];
                $jd = (int) $matches[3];
                if ($jy < 1000 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
                    return null;
                }
                [$gy, $gm, $gd] = jalali_to_gregorian($jy, $jm, $jd);
                return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
            }

            return null;
        };

        $blankToNull = static fn ($v) => ($v === '' || $v === null) ? null : $v;

        $request->merge([
            'start_at' => $normalizeDateInput($request->input('start_at')),
            'end_at' => $normalizeDateInput($request->input('end_at')),
            'response_limit' => $blankToNull($request->input('response_limit')),
            'response_edit_window_hours' => $blankToNull($request->input('response_edit_window_hours')),
        ]);

        $publicThemeRuleKeys = collect(Survey::defaultPublicTheme())
            ->mapWithKeys(fn ($_, $key) => ['public_theme.' . $key => ['nullable', 'string', 'max:80']])
            ->all();

        $survey->load('creator');
        $acting = current_admin();
        $supervisorPublishRestricted = $acting instanceof AdminUser
            && $acting->isSupervisor()
            && $survey->creator
            && $survey->creator->isSupervisor()
            && $survey->creator->requires_survey_publish_approval
            && (int) $survey->created_by_admin_user_id === (int) $acting->id;
        $allowedStatuses = $supervisorPublishRestricted
            ? ['draft', 'closed', 'pending_approval']
            : ['draft', 'active', 'closed', 'pending_approval'];

        $validated = $request->validateWithBag('updateSurvey', array_merge([
            'title' => ['required', 'string', 'max:255'],
            'response_window_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'response_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in($allowedStatuses)],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'response_edit_window_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'track_location' => ['nullable', 'boolean'],
            'prevent_multiple_submissions' => ['nullable', 'boolean'],
            'allow_edit' => ['nullable', 'boolean'],
            'allow_partial' => ['nullable', 'boolean'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'show_results_after_submit' => ['nullable', 'boolean'],
            'result_visibility' => ['required', Rule::in(['private', 'public', 'after_close'])],
            'is_anonymous' => ['nullable', 'boolean'],
            'require_auth' => ['nullable', 'boolean'],
            'audience_modes' => ['nullable', 'array'],
            'audience_modes.*' => ['string', Rule::in(['unit', 'gender', 'position', 'personnel'])],
            'access_identity_mode' => ['required', Rule::in(['none', 'personnel_code', 'national_code', 'either'])],
            'audience_unit_ids' => ['nullable', 'array'],
            'audience_unit_ids.*' => ['integer', 'exists:units,id'],
            'audience_genders' => ['nullable', 'array'],
            'audience_genders.*' => ['string', Rule::in(['male', 'female', 'other'])],
            'audience_position_ids' => ['nullable', 'array'],
            'audience_position_ids.*' => ['integer', 'exists:positions,id'],
            'audience_personnel_ids' => ['nullable', 'array'],
            'audience_personnel_ids.*' => ['integer', 'exists:personnel,id'],
            'thank_you_message' => ['nullable', 'string', 'max:255'],
            'intro_text' => ['nullable', 'string', 'max:8000'],
            'notification_emails' => ['nullable', 'string', 'max:1000'],
            'background_preset' => ['nullable', Rule::in($backgroundPresetOptions)],
            'background_upload' => ['nullable', 'file', 'image', 'max:5120'],
            'public_theme' => ['nullable', 'array'],
        ], $publicThemeRuleKeys));

        $selectedModes = collect($validated['audience_modes'] ?? [])
            ->unique()
            ->values()
            ->all();
        if (!empty($selectedModes) && ($validated['access_identity_mode'] ?? 'none') === 'none') {
            return back()
                ->withErrors(['access_identity_mode' => 'برای اعمال فیلتر مخاطب، نوع احراز هویت پرسنلی را مشخص کنید.'], 'updateSurvey')
                ->withInput();
        }

        $audienceFilters = [
            'identity_mode' => $validated['access_identity_mode'] ?? 'none',
            'modes' => $selectedModes,
            'unit_ids' => in_array('unit', $selectedModes, true) ? array_values(array_unique(array_map('intval', $validated['audience_unit_ids'] ?? []))) : [],
            'genders' => in_array('gender', $selectedModes, true) ? array_values(array_unique($validated['audience_genders'] ?? [])) : [],
            'position_ids' => in_array('position', $selectedModes, true) ? array_values(array_unique(array_map('intval', $validated['audience_position_ids'] ?? []))) : [],
            'personnel_ids' => in_array('personnel', $selectedModes, true) ? array_values(array_unique(array_map('intval', $validated['audience_personnel_ids'] ?? []))) : [],
        ];

        $actingAdmin = current_admin();
        if ($actingAdmin instanceof AdminUser && $actingAdmin->isSupervisor()) {
            $allowedUnits = $actingAdmin->supervisedUnitIds();
            foreach ($audienceFilters['unit_ids'] ?? [] as $uid) {
                if (!in_array((int) $uid, $allowedUnits, true)) {
                    return back()
                        ->withErrors(['audience_unit_ids' => 'فقط واحدهای تحت سرپرستی شما را می‌توانید در مخاطب انتخاب کنید.'], 'updateSurvey')
                        ->withInput();
                }
            }
        }

        $notificationEmails = [];
        if (!empty($validated['notification_emails'])) {
            $notificationEmails = array_filter(array_map('trim', explode(',', $validated['notification_emails'])));
        }
        $invalidEmails = array_filter($notificationEmails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) === false);
        if (!empty($invalidEmails)) {
            return back()
                ->withErrors(['notification_emails' => 'فرمت ایمیل‌ها معتبر نیست.'], 'updateSurvey')
                ->withInput();
        }

        $defaults = Survey::defaultPublicTheme();
        $themeIn = $validated['public_theme'] ?? [];
        $publicTheme = [];
        foreach ($defaults as $key => $def) {
            $v = isset($themeIn[$key]) ? trim(strip_tags((string) $themeIn[$key])) : '';
            $publicTheme[$key] = ($v !== '' && strlen($v) <= 80) ? $v : $def;
        }

        $survey->update([
            'title' => $validated['title'],
            'response_window_hours' => $validated['response_window_hours'],
            'response_limit' => $validated['response_limit'] ?? null,
            'response_edit_window_hours' => $validated['response_edit_window_hours'] ?? null,
            'status' => $validated['status'],
            'is_active' => $validated['status'] === 'active',
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'require_auth' => $request->boolean('require_auth'),
            'track_location' => $request->boolean('track_location'),
            'prevent_multiple_submissions' => $request->boolean('prevent_multiple_submissions'),
            'allow_edit' => $request->boolean('allow_edit'),
            'allow_partial' => $request->boolean('allow_partial'),
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_options' => $request->boolean('shuffle_options'),
            'show_results_after_submit' => $request->boolean('show_results_after_submit'),
            'result_visibility' => $validated['result_visibility'],
            'audience_filters' => $audienceFilters,
            'thank_you_message' => $validated['thank_you_message'] ?? null,
            'intro_text' => $validated['intro_text'] ?? null,
            'notification_emails' => $notificationEmails,
            'public_theme' => $publicTheme,
        ]);

        if ($request->hasFile('background_upload')) {
            $file = $request->file('background_upload');
            $destination = public_path('bg-images/custom');
            if (!is_dir($destination)) {
                mkdir($destination, 0775, true);
            }
            $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->move($destination, $fileName);
            $survey->update([
                'background_image' => 'bg-images/custom/' . $fileName,
            ]);
        } else {
            $preset = $validated['background_preset'] ?? null;
            if ($preset === 'none') {
                $survey->update(['background_image' => null]);
            } elseif ($preset && in_array($preset, $backgroundPresets, true)) {
                $survey->update(['background_image' => 'bg-images/' . $preset]);
            }
        }

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'تنظیمات نظرسنجی ذخیره شد.');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        if ($survey->responses()->exists()) {
            return redirect()
                ->route('admin.surveys.index')
                ->with('error', 'این نظرسنجی دارای پاسخ است و قابل حذف نیست.');
        }

        $survey->delete();

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'نظرسنجی حذف شد.');
    }

    public function generateLink(Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        $admin = current_admin();
        $survey->load('creator');

        if (!$survey->public_token) {
            $survey->update([
                'public_token' => Survey::generateUniquePublicToken(),
            ]);
        }

        if ($admin instanceof AdminUser && $admin->isAdmin()) {
            return redirect()
                ->route('admin.surveys.index')
                ->with('status', 'لینک عمومی نظرسنجی آماده است.');
        }

        $creator = $survey->creator;
        $needsApproval = $creator
            && $creator->isSupervisor()
            && $creator->requires_survey_publish_approval;

        if ($needsApproval && $admin instanceof AdminUser && (int) $survey->created_by_admin_user_id === (int) $admin->id) {
            if ($survey->status === 'pending_approval') {
                return redirect()
                    ->route('admin.surveys.index')
                    ->with('status', 'این نظرسنجی قبلاً برای تایید مدیر ارسال شده است.');
            }
            if ($survey->status === 'active') {
                return redirect()
                    ->route('admin.surveys.index')
                    ->with('status', 'نظرسنجی از قبل فعال است.');
            }
            if ($survey->status === 'closed') {
                return redirect()
                    ->route('admin.surveys.index')
                    ->with('error', 'نظرسنجی بسته‌شده را نمی‌توان برای انتشار ارسال کرد.');
            }

            $survey->update([
                'status' => 'pending_approval',
                'is_active' => false,
                'publish_requested_by_admin_user_id' => $admin->id,
                'publish_rejection_reason' => null,
            ]);

            return redirect()
                ->route('admin.surveys.index')
                ->with('status', 'نظرسنجی برای تایید نهایی مدیر ارسال شد. پس از تایید، فعال و در دسترس قرار می‌گیرد.');
        }

        if ($survey->status === 'closed') {
            return redirect()
                ->route('admin.surveys.index')
                ->with('error', 'نظرسنجی بسته‌شده را نمی‌توان فعال کرد.');
        }

        $survey->update([
            'status' => 'active',
            'is_active' => true,
            'publish_requested_by_admin_user_id' => null,
        ]);

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'لینک عمومی آماده است و نظرسنجی فعال شد.');
    }

    public function approvePublish(Survey $survey): RedirectResponse
    {
        $admin = current_admin();
        if (!$admin instanceof AdminUser || !$admin->isAdmin()) {
            abort(403);
        }

        if ($survey->status !== 'pending_approval') {
            return redirect()
                ->route('admin.surveys.index')
                ->with('error', 'فقط نظرسنجی‌های در انتظار تایید قابل تأیید هستند.');
        }

        $survey->update([
            'status' => 'active',
            'is_active' => true,
            'publish_rejection_reason' => null,
        ]);

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'انتشار نظرسنجی توسط مدیر تایید شد و اکنون فعال است.');
    }

    public function rejectPublish(Request $request, Survey $survey): RedirectResponse
    {
        $admin = current_admin();
        if (!$admin instanceof AdminUser || !$admin->isAdmin()) {
            abort(403);
        }

        if ($survey->status !== 'pending_approval') {
            return redirect()
                ->route('admin.surveys.index')
                ->with('error', 'این نظرسنجی در حالت انتظار تایید نیست.');
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => ['required', 'string', 'min:1', 'max:2000'],
        ], [
            'rejection_reason.required' => 'نوشتن دلیل رد الزامی است.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.surveys.index')
                ->withErrors($validator)
                ->withInput()
                ->with('reject_publish_survey_id', $survey->id)
                ->with('reject_publish_action_url', route('admin.surveys.reject-publish', $survey));
        }

        $survey->update([
            'status' => 'draft',
            'is_active' => false,
            'publish_requested_by_admin_user_id' => null,
            'publish_rejection_reason' => $validator->validated()['rejection_reason'],
        ]);

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'انتشار رد شد؛ نظرسنجی به حالت پیش‌نویس برگشت و دلیل برای ناظر ثبت شد.');
    }

    public function report(Survey $survey): View
    {
        $this->authorizeSurveyAccess($survey);

        $survey->load(['unit', 'questions.options']);

        $responses = SurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->where('status', 'submitted')
            ->with([
                'personnel.unit',
                'personnel.position',
                'answers.question.options',
                'answers.option',
            ])
            ->latest('submitted_at')
            ->paginate(20);

        $chartBlocks = $this->buildSurveyReportCharts($survey);

        return view('admin.surveys-report', compact('survey', 'responses', 'chartBlocks'));
    }

    public function exportReportExcel(Survey $survey): BinaryFileResponse
    {
        $this->authorizeSurveyAccess($survey);

        $survey->load(['unit', 'questions.options']);
        $responses = SurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->where('status', 'submitted')
            ->with([
                'personnel.unit',
                'personnel.position',
                'answers.question.options',
                'answers.option',
            ])
            ->latest('submitted_at')
            ->get();

        $questions = $survey->questions->sortBy('position')->values();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('گزارش نظرسنجی');
        $sheet->setRightToLeft(true);

        $headers = [
            'شناسه پاسخ',
            'نام پاسخ‌دهنده',
            'وضعیت',
            'کد پرسنلی',
            'کد ملی',
            'واحد',
            'سمت',
            'زمان ثبت',
        ];
        foreach ($questions as $question) {
            $headers[] = $question->title;
        }

        foreach ($headers as $index => $header) {
            $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        $row = 2;
        foreach ($responses as $response) {
            $answersByQuestionId = $response->answers->keyBy('question_id');
            $baseCells = [
                $response->id,
                $response->respondent_name ?: ($response->personnel ? trim($response->personnel->first_name . ' ' . $response->personnel->last_name) : 'ناشناس'),
                $response->status === 'submitted' ? 'ثبت نهایی' : 'پیش‌نویس',
                $response->personnel?->personnel_code ?: '-',
                $response->personnel?->national_code ?: '-',
                $response->personnel?->unit?->name ?: '-',
                $response->personnel?->position?->name ?: '-',
                $response->submitted_at ? jalali_date($response->submitted_at, 'Y/m/d H:i') : '-',
            ];

            foreach ($baseCells as $index => $value) {
                $cell = Coordinate::stringFromColumnIndex($index + 1) . $row;
                $sheet->setCellValue($cell, (string) $value);
            }

            $col = count($baseCells) + 1;
            foreach ($questions as $question) {
                /** @var SurveyResponseAnswer|null $answer */
                $answer = $answersByQuestionId->get($question->id);
                $cell = Coordinate::stringFromColumnIndex($col) . $row;
                $sheet->setCellValue($cell, $answer ? $this->resolveAnswerDisplayValue($answer) : '-');
                $col++;
            }
            $row++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $lastRow = max(1, $row - 1);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
        ]);

        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        for ($column = 1; $column <= count($headers); $column++) {
            $columnLetter = Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $fileName = 'survey-report-' . ($survey->id) . '-' . now()->format('Ymd-His') . '.xlsx';
        $tempPath = storage_path('app/' . Str::random(24) . '-' . $fileName);
        (new Xlsx($spreadsheet))->save($tempPath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    public function editResponse(Survey $survey, SurveyResponse $response): View
    {
        $this->authorizeSurveyAccess($survey);

        if ($response->survey_id !== $survey->id || $response->status !== 'submitted') {
            abort(404);
        }

        $survey->load(['questions.options']);
        $response->load([
            'personnel.unit',
            'personnel.position',
            'answers.question.options',
            'answers.option',
        ]);

        $existingAnswers = [];
        foreach ($response->answers as $answer) {
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

        return view('admin.surveys-report-edit', compact('survey', 'response', 'existingAnswers'));
    }

    public function updateResponse(Request $request, Survey $survey, SurveyResponse $response): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        if ($response->survey_id !== $survey->id || $response->status !== 'submitted') {
            abort(404);
        }

        $survey->load(['questions.options']);
        $answersInput = $request->input('answers', []);
        if (!is_array($answersInput)) {
            return back()->withErrors(['answers' => 'فرمت پاسخ‌ها معتبر نیست.'])->withInput();
        }

        DB::transaction(function () use ($response, $survey, $answersInput) {
            $saved = 0;
            foreach ($survey->questions as $question) {
                $raw = $answersInput[$question->id] ?? null;
                $file = request()->file('answers.' . $question->id . '.file');
                $normalized = $this->normalizeResponseAnswer($question, $raw, $file, $response);
                if ($normalized === null) {
                    $existing = SurveyResponseAnswer::where('response_id', $response->id)
                        ->where('question_id', $question->id)
                        ->first();
                    if ($existing && $question->type === 'file_upload') {
                        $old = $existing->answer_json['file_path'] ?? null;
                        if (is_string($old) && $old !== '') {
                            Storage::disk('public')->delete($old);
                        }
                    }
                    SurveyResponseAnswer::where('response_id', $response->id)
                        ->where('question_id', $question->id)
                        ->delete();
                    continue;
                }
                SurveyResponseAnswer::updateOrCreate(
                    ['response_id' => $response->id, 'question_id' => $question->id],
                    $normalized
                );
                $saved++;
            }

            $response->update([
                'answers_count' => $saved,
                'last_seen_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.surveys.report', $survey)
            ->with('status', 'پاسخ انتخاب‌شده با موفقیت ویرایش شد.');
    }

    public function destroyResponse(Survey $survey, SurveyResponse $response): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        if ($response->survey_id !== $survey->id || $response->status !== 'submitted') {
            abort(404);
        }

        $response->load('answers');
        foreach ($response->answers as $answer) {
            $old = $answer->answer_json['file_path'] ?? null;
            if (is_string($old) && $old !== '') {
                Storage::disk('public')->delete($old);
            }
        }

        $response->delete();
        $survey->update([
            'responses_count' => SurveyResponse::where('survey_id', $survey->id)
                ->where('status', 'submitted')
                ->count(),
        ]);

        return redirect()
            ->route('admin.surveys.report', $survey)
            ->with('status', 'پاسخ با موفقیت حذف شد.');
    }

    public function downloadResponseFile(Survey $survey, SurveyResponse $response, SurveyQuestion $question)
    {
        $this->authorizeSurveyAccess($survey);
        if ($response->survey_id !== $survey->id || $question->survey_id !== $survey->id) {
            abort(404);
        }

        $answer = SurveyResponseAnswer::where('response_id', $response->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $path = $answer->answer_json['file_path'] ?? null;
        if (!is_string($path) || $path === '' || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $name = (string) ($answer->answer_json['file_name'] ?? basename($path));

        return Storage::disk('public')->download($path, $name);
    }

    private function normalizeResponseAnswer($question, mixed $raw, ?UploadedFile $uploadedFile, SurveyResponse $response): ?array
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

        if (in_array($question->type, ['multiple_choice', 'dropdown', 'rating', 'yes_no', 'linear_scale'], true)) {
            $optionId = (int) (is_array($raw) ? ($raw['option_id'] ?? 0) : $raw);
            if ($optionId <= 0) {
                return null;
            }

            return array_merge($base, ['option_id' => $optionId]);
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

        if ($question->type === 'file_upload') {
            $currentPath = is_array($raw) ? trim((string) ($raw['current_file'] ?? '')) : '';
            $currentName = is_array($raw) ? trim((string) ($raw['current_file_name'] ?? '')) : '';
            $removeCurrent = is_array($raw) && !empty($raw['remove_file']);

            if (!$uploadedFile) {
                if ($removeCurrent || $currentPath === '') {
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
                    'answers.' . $question->id => 'تنظیمات سوال فایل کامل نیست.',
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

        return array_merge($base, ['answer_text' => $value]);
    }

    private function resolveAnswerDisplayValue(SurveyResponseAnswer $answer): string
    {
        if ($answer->option) {
            return (string) $answer->option->label;
        }

        if (!empty($answer->answer_json['option_ids'])) {
            $optionLabels = collect($answer->question?->options ?? [])
                ->whereIn('id', $answer->answer_json['option_ids'])
                ->pluck('label')
                ->values()
                ->all();

            return !empty($optionLabels) ? implode('، ', $optionLabels) : 'چندگزینه‌ای';
        }

        if (
            in_array($answer->question?->type, ['multiple_choice', 'dropdown', 'checkboxes'], true) &&
            !is_null($answer->answer_number) &&
            $answer->question?->options?->isNotEmpty()
        ) {
            $rawNumber = (int) $answer->answer_number;
            $byId = $answer->question->options->firstWhere('id', $rawNumber);
            if ($byId) {
                return (string) $byId->label;
            }
            $byPosition = $answer->question->options->firstWhere('position', $rawNumber);
            return (string) ($byPosition?->label ?? $rawNumber);
        }

        if ($answer->question?->type === 'date') {
            if ($answer->answer_date) {
                return (string) jalali_date($answer->answer_date, 'Y/m/d');
            }
            if (filled($answer->answer_text)) {
                return (string) jalali_date($answer->answer_text, 'Y/m/d');
            }
        }

        if (filled($answer->answer_text)) {
            $text = trim((string) $answer->answer_text);
            if (preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}/', $text)) {
                return (string) jalali_date($text, 'Y/m/d');
            }
            return $text;
        }

        if ($answer->question?->type === 'file_upload') {
            $fileName = (string) ($answer->answer_json['file_name'] ?? '');
            return $fileName !== '' ? $fileName : 'فایل';
        }

        if (!is_null($answer->answer_number)) {
            if ($answer->question?->type === 'rating') {
                return 'امتیاز ' . $answer->answer_number . ' از 5';
            }
            return (string) $answer->answer_number;
        }

        if ($answer->answer_date) {
            return (string) jalali_date($answer->answer_date, 'Y/m/d');
        }

        return '-';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSurveyReportCharts(Survey $survey): array
    {
        $questions = $survey->questions->sortBy('position')->values();
        if ($questions->isEmpty()) {
            return [];
        }

        $answers = SurveyResponseAnswer::query()
            ->whereHas('response', function ($q) use ($survey) {
                $q->where('survey_id', $survey->id)->where('status', 'submitted');
            })
            ->get();

        $byQuestion = $answers->groupBy('question_id');
        $blocks = [];

        foreach ($questions as $question) {
            try {
                $qAnswers = $byQuestion->get($question->id, collect());
                $block = $this->aggregateQuestionForChart($question, $qAnswers);
                if ($block !== null) {
                    $blocks[] = $block;
                }
            } catch (\Throwable $e) {
                Log::warning('survey report chart skip: ' . $e->getMessage(), [
                    'survey_id' => $survey->id,
                    'question_id' => $question->id,
                ]);
            }
        }

        return $blocks;
    }

    private function aggregateQuestionForChart(SurveyQuestion $question, Collection $answers): ?array
    {
        return match ($question->type) {
            'multiple_choice', 'dropdown', 'yes_no', 'linear_scale' => $this->chartBlockSingleOptionQuestion($question, $answers),
            'checkboxes' => $this->chartBlockCheckboxes($question, $answers),
            'rating' => $this->chartBlockRating($question, $answers),
            'number' => $this->chartBlockNumber($question, $answers),
            'date' => $this->chartBlockDate($question, $answers),
            default => null,
        };
    }

    private function chartBlockSingleOptionQuestion(SurveyQuestion $question, Collection $answers): ?array
    {
        $options = $question->options->sortBy('position')->values();
        if ($options->isEmpty()) {
            return null;
        }

        $counts = [];
        foreach ($options as $opt) {
            $counts[$opt->id] = 0;
        }
        foreach ($answers as $a) {
            $oid = (int) $a->option_id;
            if ($oid > 0 && array_key_exists($oid, $counts)) {
                $counts[$oid]++;
            }
        }

        $labels = $options->map(fn ($o) => $this->truncateChartLabel((string) $o->label))->all();
        $data = $options->map(fn ($o) => $counts[$o->id])->all();

        return $this->finalizeChartSpec($question, $labels, $data, null);
    }

    private function chartBlockCheckboxes(SurveyQuestion $question, Collection $answers): ?array
    {
        $options = $question->options->sortBy('position')->values();
        if ($options->isEmpty()) {
            return null;
        }

        $counts = [];
        foreach ($options as $opt) {
            $counts[$opt->id] = 0;
        }
        foreach ($answers as $a) {
            $ids = $a->answer_json['option_ids'] ?? [];
            foreach ((array) $ids as $oid) {
                $oid = (int) $oid;
                if ($oid > 0 && array_key_exists($oid, $counts)) {
                    $counts[$oid]++;
                }
            }
        }

        $labels = $options->map(fn ($o) => $this->truncateChartLabel((string) $o->label))->all();
        $data = $options->map(fn ($o) => $counts[$o->id])->all();

        return $this->finalizeChartSpec($question, $labels, $data, 'تعداد دفعات انتخاب هر گزینه (چند انتخابی)');
    }

    private function chartBlockRating(SurveyQuestion $question, Collection $answers): ?array
    {
        $options = $question->options->sortBy('position')->values();
        if ($options->isEmpty()) {
            $values = $answers->pluck('answer_number')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
            if ($values->isEmpty()) {
                return null;
            }
            $counts = $values->countBy(fn ($v) => (string) $v)->sortKeys();
            $labels = $counts->keys()->map(fn ($k) => 'مقدار ' . $k)->values()->all();
            $data = $counts->values()->all();

            return $this->finalizeChartSpec($question, $labels, $data, 'توزیع مقدار عددی');
        }

        $counts = [];
        foreach ($options as $opt) {
            $counts[$opt->id] = 0;
        }
        $numericExtra = [];
        foreach ($answers as $a) {
            if ($a->option_id && array_key_exists((int) $a->option_id, $counts)) {
                $counts[(int) $a->option_id]++;
            } elseif ($a->answer_number !== null) {
                $k = (string) $a->answer_number;
                $numericExtra[$k] = ($numericExtra[$k] ?? 0) + 1;
            }
        }

        $labels = $options->map(fn ($o) => $this->truncateChartLabel((string) $o->label))->all();
        $data = $options->map(fn ($o) => $counts[$o->id])->all();
        ksort($numericExtra, SORT_NATURAL);
        foreach ($numericExtra as $k => $c) {
            $labels[] = 'مقدار ' . $k;
            $data[] = $c;
        }

        return $this->finalizeChartSpec($question, $labels, $data, null);
    }

    private function chartBlockNumber(SurveyQuestion $question, Collection $answers): ?array
    {
        $values = $answers->pluck('answer_number')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $uniqueN = $values->unique()->count();
        if ($uniqueN <= 15) {
            $counts = $values->countBy(fn ($v) => (string) $v)->sortKeys();
            $labels = $counts->keys()->map(fn ($k) => $this->truncateChartLabel($k))->values()->all();
            $data = $counts->values()->all();

            return $this->finalizeChartSpec($question, $labels, $data, 'تعداد پاسخ به تفکیک مقدار');
        }

        $min = (float) $sorted->first();
        $max = (float) $sorted->last();
        if ($min === $max) {
            return $this->finalizeChartSpec(
                $question,
                [(string) $min],
                [$values->count()],
                'همه پاسخ‌ها یک مقدار'
            );
        }

        $bins = array_fill(0, 8, 0);
        $span = $max - $min;
        $step = $span / 8;
        foreach ($values as $v) {
            $i = (int) floor(($v - $min) / $step);
            if ($i > 7) {
                $i = 7;
            }
            if ($i < 0) {
                $i = 0;
            }
            $bins[$i]++;
        }
        $labels = [];
        for ($b = 0; $b < 8; $b++) {
            $lo = $min + $b * $step;
            $hi = $b === 7 ? $max : $min + ($b + 1) * $step;
            $labels[] = $this->truncateChartLabel(
                sprintf('%.2f – %.2f', $lo, $hi)
            );
        }

        return $this->finalizeChartSpec($question, $labels, $bins, '۸ بازهٔ مساوی بین کمینه و بیشینه');
    }

    private function chartBlockDate(SurveyQuestion $question, Collection $answers): ?array
    {
        $byDay = [];
        foreach ($answers as $a) {
            $key = null;
            if ($a->answer_date) {
                $key = $a->answer_date->format('Y-m-d');
            } elseif (filled($a->answer_text)) {
                $t = trim((string) $a->answer_text);
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $t, $m)) {
                    $key = $m[1];
                }
            }
            if ($key) {
                $byDay[$key] = ($byDay[$key] ?? 0) + 1;
            }
        }
        if ($byDay === []) {
            return null;
        }
        ksort($byDay);
        $labels = [];
        $data = [];
        foreach ($byDay as $ymd => $c) {
            $labels[] = function_exists('jalali_date')
                ? (string) jalali_date($ymd, 'Y/m/d')
                : $ymd;
            $data[] = $c;
        }

        return $this->finalizeChartSpec($question, $labels, $data, 'تعداد پاسخ به تفکیک روز');
    }

    /**
     * @param  list<string>  $labels
     * @param  list<int|float>  $data
     * @return array<string, mixed>|null
     */
    private function finalizeChartSpec(SurveyQuestion $question, array $labels, array $data, ?string $subtitle): ?array
    {
        $n = count($labels);
        if ($n === 0 || $n !== count($data)) {
            return null;
        }

        $sum = array_sum($data);
        $colors = $this->chartPalette($n);

        $kind = 'bar';
        $indexAxis = 'x';
        if ($question->type === 'date') {
            $kind = 'line';
        } elseif ($sum > 0 && $n >= 2 && $n <= 10 && in_array($question->type, [
            'multiple_choice', 'dropdown', 'checkboxes', 'yes_no', 'linear_scale', 'rating',
        ], true)) {
            $kind = 'doughnut';
        } elseif ($n > 7 && $kind === 'bar') {
            $indexAxis = 'y';
        }

        return [
            'question_id' => $question->id,
            'title' => $this->truncateChartLabel((string) $question->title, 120),
            'question_type' => $question->type,
            'kind' => $kind,
            'index_axis' => $indexAxis,
            'labels' => $labels,
            'data' => array_map(fn ($v) => (float) $v, $data),
            'colors' => $colors,
            'subtitle' => $subtitle,
            'has_data' => $sum > 0,
            'total_in_chart' => $sum,
        ];
    }

    /**
     * @return list<string>
     */
    private function chartPalette(int $n): array
    {
        $base = [
            'rgba(214, 17, 25, 0.88)',
            'rgba(13, 116, 133, 0.88)',
            'rgba(15, 118, 110, 0.88)',
            'rgba(79, 70, 229, 0.85)',
            'rgba(217, 119, 6, 0.88)',
            'rgba(37, 99, 235, 0.85)',
            'rgba(147, 51, 234, 0.82)',
            'rgba(220, 38, 38, 0.78)',
            'rgba(8, 145, 178, 0.88)',
            'rgba(22, 163, 74, 0.85)',
            'rgba(234, 88, 12, 0.85)',
            'rgba(59, 130, 246, 0.85)',
        ];
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = $base[$i % count($base)];
        }

        return $out;
    }

    private function truncateChartLabel(string $label, int $max = 48): string
    {
        $label = trim($label);
        if ($label === '') {
            return '-';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($label) > $max) {
            return mb_substr($label, 0, $max - 1) . '…';
        }
        if (strlen($label) > $max) {
            return substr($label, 0, $max - 1) . '…';
        }

        return $label;
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

        // Backward compatibility: old format was a list of labels.
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

}