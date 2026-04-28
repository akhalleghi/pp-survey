<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseAnswer;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $allowedStatuses = ['active', 'draft', 'closed'];
        $statusFilter = in_array($request->query('status'), $allowedStatuses, true) ? $request->query('status') : null;

        $surveys = Survey::with('unit')
            ->withCount([
                'responses as submitted_responses_count' => fn ($q) => $q->where('status', 'submitted'),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhereHas('unit', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $units = Unit::orderBy('name')->get(['id', 'name']);
        $audiencePresets = ['همه کاربران', 'براساس واحد', 'براساس جنسیت', 'براساس سمت', 'براساس مدرک تحصیلی', 'انتخابی توسط ادمین'];
        $avgQuestions = Survey::avg('questions_count') ?? 0;
        $metrics = [
            'active' => Survey::where('status', 'active')->count(),
            'responses' => SurveyResponse::where('status', 'submitted')->count(),
            'avg_questions' => round($avgQuestions, 1),
            'closed' => Survey::where('status', 'closed')->count(),
        ];

        return view('admin.surveys', compact('surveys', 'units', 'audiencePresets', 'search', 'statusFilter', 'metrics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createSurvey', [
            'title' => ['required', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Survey::create([
            'title' => $validated['title'],
            'unit_id' => $validated['unit_id'] ?? null,
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
        $audiencePresets = [
            'unit' => 'براساس واحد',
            'gender' => 'براساس جنسیت',
            'position' => 'براساس سمت',
            'personnel' => 'انتخابی توسط ادمین',
        ];
        $statusOptions = ['draft' => 'در حال آماده سازی', 'active' => 'فعال', 'closed' => 'بسته شده'];
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
        $units = Unit::query()->orderBy('name')->get(['id', 'name']);
        $positions = Position::query()->orderBy('name')->get(['id', 'name']);
        $personnelOptions = Personnel::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'personnel_code', 'national_code']);

        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);

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
                'audienceConfig'
            )
        );
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
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

        $validated = $request->validateWithBag('updateSurvey', [
            'response_window_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'response_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
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
        ]);

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

        $survey->update([
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
        $survey->delete();

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'نظرسنجی حذف شد.');
    }

    public function generateLink(Survey $survey): RedirectResponse
    {
        if (!$survey->public_token) {
            $survey->update([
                'public_token' => Str::random(40),
            ]);
        }

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'لینک عمومی نظرسنجی آماده است.');
    }

    public function report(Survey $survey): View
    {
        $survey->load('unit');

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

        return view('admin.surveys-report', compact('survey', 'responses'));
    }

    public function exportReportExcel(Survey $survey): BinaryFileResponse
    {
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
            ];
        }

        return view('admin.surveys-report-edit', compact('survey', 'response', 'existingAnswers'));
    }

    public function updateResponse(Request $request, Survey $survey, SurveyResponse $response): RedirectResponse
    {
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
                $normalized = $this->normalizeResponseAnswer($question, $raw);
                if ($normalized === null) {
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
        if ($response->survey_id !== $survey->id || $response->status !== 'submitted') {
            abort(404);
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

    private function normalizeResponseAnswer($question, mixed $raw): ?array
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

        return [
            'identity_mode' => in_array($value['identity_mode'] ?? 'none', ['none', 'personnel_code', 'national_code', 'either'], true)
                ? $value['identity_mode']
                : 'none',
            'modes' => array_values(array_filter((array) ($value['modes'] ?? []), fn ($mode) => in_array($mode, ['unit', 'gender', 'position', 'personnel'], true))),
            'unit_ids' => array_values(array_map('intval', (array) ($value['unit_ids'] ?? []))),
            'genders' => array_values(array_filter((array) ($value['genders'] ?? []), fn ($gender) => in_array($gender, ['male', 'female', 'other'], true))),
            'position_ids' => array_values(array_map('intval', (array) ($value['position_ids'] ?? []))),
            'personnel_ids' => array_values(array_map('intval', (array) ($value['personnel_ids'] ?? []))),
        ];
    }

}