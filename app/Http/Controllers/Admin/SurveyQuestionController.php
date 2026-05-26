<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\AuthorizesSurveyAccess;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SurveyQuestionController extends Controller
{
    use AuthorizesSurveyAccess;

    private const OPTION_BASED_TYPES = [
        'multiple_choice',
        'checkboxes',
        'dropdown',
        'rating',
        'yes_no',
        'linear_scale',
    ];

    private const SUPPORTED_TYPES = [
        'short_text',
        'long_text',
        'static_text_short',
        'static_text_long',
        'multiple_choice',
        'checkboxes',
        'dropdown',
        'rating',
        'number',
        'email',
        'date',
        'phone',
        'url',
        'yes_no',
        'linear_scale',
        'file_upload',
    ];

    public function index(Survey $survey): View
    {
        $this->authorizeSurveyAccess($survey);

        $survey->load(['questions.options', 'questions.answers']);
        $questionTypes = $this->questionTypes();

        return view('admin.surveys-questions', compact('survey', 'questionTypes'));
    }

    public function store(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        $validated = $this->validateQuestionPayload($request);

        $position = (int) $survey->questions()->max('position') + 1;

        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'position' => $position,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_required' => in_array($validated['type'], SurveyQuestion::staticDisplayTypes(), true)
                ? false
                : $request->boolean('is_required'),
            'settings' => $validated['settings'] ?? [],
        ]);

        $this->syncQuestionOptions($question, $validated['options'] ?? []);

        $survey->update([
            'questions_count' => $survey->questions()->count(),
        ]);

        return redirect()->route('admin.surveys.questions.index', $survey);
    }

    public function edit(Survey $survey, SurveyQuestion $question): View
    {
        $this->authorizeSurveyAccess($survey);

        if ($question->survey_id !== $survey->id) {
            abort(404);
        }

        if (!$this->canEditQuestion($question)) {
            return redirect()
                ->route('admin.surveys.questions.index', $survey)
                ->with('status', 'این سوال قبلاً پاسخ دریافت کرده و قابل ویرایش نیست.');
        }

        $question->load('options');
        $questionTypes = $this->questionTypes();

        return view('admin.surveys-questions-edit', compact('survey', 'question', 'questionTypes'));
    }

    public function update(Request $request, Survey $survey, SurveyQuestion $question): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        if ($question->survey_id !== $survey->id) {
            abort(404);
        }

        if (!$this->canEditQuestion($question)) {
            return redirect()
                ->route('admin.surveys.questions.index', $survey)
                ->with('status', 'این سوال قبلاً پاسخ دریافت کرده و قابل ویرایش نیست.');
        }

        $validated = $this->validateQuestionPayload($request);

        $question->update([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_required' => in_array($validated['type'], SurveyQuestion::staticDisplayTypes(), true)
                ? false
                : $request->boolean('is_required'),
            'settings' => $validated['settings'] ?? [],
        ]);

        $this->syncQuestionOptions($question, $validated['options'] ?? []);

        return redirect()
            ->route('admin.surveys.questions.index', $survey)
            ->with('status', 'سوال با موفقیت ویرایش شد.');
    }

    public function destroy(Survey $survey, SurveyQuestion $question): RedirectResponse
    {
        $this->authorizeSurveyAccess($survey);

        if ($question->survey_id !== $survey->id) {
            abort(404);
        }

        $question->delete();
        $survey->update([
            'questions_count' => $survey->questions()->count(),
        ]);

        return redirect()->route('admin.surveys.questions.index', $survey);
    }

    private function questionTypes(): array
    {
        return [
            'short_text' => ['label' => 'متن کوتاه', 'has_options' => false, 'is_display_only' => false],
            'long_text' => ['label' => 'متن بلند', 'has_options' => false, 'is_display_only' => false],
            'static_text_short' => ['label' => 'متن ثابت کوتاه', 'has_options' => false, 'is_display_only' => true],
            'static_text_long' => ['label' => 'متن ثابت بلند', 'has_options' => false, 'is_display_only' => true],
            'multiple_choice' => ['label' => 'چندگزینه ای', 'has_options' => true, 'is_display_only' => false],
            'checkboxes' => ['label' => 'چندگزینه ای چندانتخابی', 'has_options' => true, 'is_display_only' => false],
            'dropdown' => ['label' => 'لیست کشویی', 'has_options' => true, 'is_display_only' => false],
            'rating' => ['label' => 'درجه بندی سفارشی', 'has_options' => true, 'is_display_only' => false],
            'number' => ['label' => 'عدد', 'has_options' => false, 'is_display_only' => false],
            'email' => ['label' => 'ایمیل', 'has_options' => false, 'is_display_only' => false],
            'date' => ['label' => 'تاریخ', 'has_options' => false, 'is_display_only' => false],
            'phone' => ['label' => 'شماره تماس', 'has_options' => false, 'is_display_only' => false],
            'url' => ['label' => 'آدرس وب', 'has_options' => false, 'is_display_only' => false],
            'yes_no' => ['label' => 'بله / خیر', 'has_options' => true, 'is_display_only' => false],
            'linear_scale' => ['label' => 'مقیاس خطی', 'has_options' => true, 'is_display_only' => false],
            'file_upload' => ['label' => 'آپلود فایل', 'has_options' => false, 'is_display_only' => false],
        ];
    }

    private function validateQuestionPayload(Request $request): array
    {
        $typesWithOptions = self::OPTION_BASED_TYPES;
        $type = $request->input('type');

        if (!in_array($type, $typesWithOptions, true)) {
            $request->merge(['options' => []]);
        }

        $rules = [
            'type' => ['required', Rule::in(self::SUPPORTED_TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['nullable', 'string', 'max:255'],
            'options.*.value' => ['nullable', 'string', 'max:255'],
        ];

        if ($type === 'static_text_short') {
            $rules['title'] = ['required', 'string', 'max:500'];
            $rules['description'] = ['nullable', 'string', 'max:1000'];
        } elseif ($type === 'static_text_long') {
            $rules['title'] = ['nullable', 'string', 'max:255'];
            $rules['description'] = ['required', 'string', 'max:5000'];
        }

        if (in_array($type, $typesWithOptions, true)) {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*.label'] = ['required', 'string', 'max:255'];
            $rules['options.*.value'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $validated['title'] = trim(strip_tags((string) $validated['title']));
        if (array_key_exists('description', $validated) && $validated['description'] !== null) {
            $validated['description'] = trim(strip_tags((string) $validated['description']));
            if ($validated['description'] === '') {
                $validated['description'] = null;
            }
        }

        if (in_array($type, SurveyQuestion::staticDisplayTypes(), true)) {
            $validated['settings'] = [];
            $validated['options'] = [];
            if ($type === 'static_text_long' && $validated['title'] === '') {
                $validated['title'] = 'متن راهنما';
            }
        }

        if ($type === 'file_upload') {
            $settings = $validated['settings'] ?? [];
            $maxKb = (int) ($settings['max_file_size_kb'] ?? 0);
            if ($maxKb <= 0) {
                throw ValidationException::withMessages([
                    'settings.max_file_size_kb' => 'حداکثر حجم فایل (کیلوبایت) الزامی است.',
                ]);
            }

            $extRaw = trim((string) ($settings['allowed_extensions'] ?? ''));
            if ($extRaw === '') {
                throw ValidationException::withMessages([
                    'settings.allowed_extensions' => 'حداقل یک پسوند فایل مجاز را وارد کنید.',
                ]);
            }

            $extList = collect(explode(',', str_replace('،', ',', $extRaw)))
                ->map(static fn ($ext) => mb_strtolower(trim((string) $ext)))
                ->filter()
                ->map(static fn ($ext) => ltrim($ext, '.'))
                ->values()
                ->all();

            if (empty($extList)) {
                throw ValidationException::withMessages([
                    'settings.allowed_extensions' => 'فرمت پسوندهای مجاز صحیح نیست.',
                ]);
            }

            $validated['settings']['max_file_size_kb'] = $maxKb;
            $validated['settings']['allowed_extensions'] = implode(',', array_values(array_unique($extList)));
        }

        if (in_array($type, $typesWithOptions, true)) {
            $values = collect($validated['options'] ?? [])
                ->pluck('value')
                ->map(static fn ($value) => trim((string) $value))
                ->filter();
            if ($values->count() !== $values->unique()->count()) {
                throw ValidationException::withMessages([
                    'options' => 'مقدار گزینه‌ها نباید تکراری باشد.',
                ]);
            }
        }

        return $validated;
    }

    private function syncQuestionOptions(SurveyQuestion $question, array $options): void
    {
        $question->options()->delete();
        foreach ($options as $index => $option) {
            if (empty($option['label'])) {
                continue;
            }
            SurveyQuestionOption::create([
                'question_id' => $question->id,
                'position' => $index + 1,
                'label' => $option['label'],
                'value' => $option['value'] ?? null,
            ]);
        }
    }

    private function canEditQuestion(SurveyQuestion $question): bool
    {
        return $question->answers()->exists() === false;
    }
}
