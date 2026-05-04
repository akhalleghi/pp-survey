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
            'is_required' => $request->boolean('is_required'),
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
            'is_required' => $request->boolean('is_required'),
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
            'short_text' => ['label' => 'متن کوتاه', 'has_options' => false],
            'long_text' => ['label' => 'متن بلند', 'has_options' => false],
            'multiple_choice' => ['label' => 'چندگزینه ای', 'has_options' => true],
            'checkboxes' => ['label' => 'چندگزینه ای چندانتخابی', 'has_options' => true],
            'dropdown' => ['label' => 'لیست کشویی', 'has_options' => true],
            'rating' => ['label' => 'درجه بندی سفارشی', 'has_options' => true],
            'number' => ['label' => 'عدد', 'has_options' => false],
            'email' => ['label' => 'ایمیل', 'has_options' => false],
            'date' => ['label' => 'تاریخ', 'has_options' => false],
            'phone' => ['label' => 'شماره تماس', 'has_options' => false],
            'url' => ['label' => 'آدرس وب', 'has_options' => false],
            'yes_no' => ['label' => 'بله / خیر', 'has_options' => true],
            'linear_scale' => ['label' => 'مقیاس خطی', 'has_options' => true],
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

        if (in_array($type, $typesWithOptions, true)) {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*.label'] = ['required', 'string', 'max:255'];
            $rules['options.*.value'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

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
