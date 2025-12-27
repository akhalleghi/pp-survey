<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SurveyQuestionController extends Controller
{
    public function index(Survey $survey): View
    {
        $survey->load(['questions.options']);

        $questionTypes = [
            'short_text' => ['label' => 'متن کوتاه', 'has_options' => false],
            'long_text' => ['label' => 'متن بلند', 'has_options' => false],
            'multiple_choice' => ['label' => 'چندگزینه ای', 'has_options' => true],
            'checkboxes' => ['label' => 'چندگزینه ای چندانتخابی', 'has_options' => true],
            'dropdown' => ['label' => 'لیست کشویی', 'has_options' => true],
            'rating' => ['label' => 'درجه بندی', 'has_options' => false],
            'number' => ['label' => 'عدد', 'has_options' => false],
            'email' => ['label' => 'ایمیل', 'has_options' => false],
            'date' => ['label' => 'تاریخ', 'has_options' => false],
        ];

        return view('admin.surveys-questions', compact('survey', 'questionTypes'));
    }

    public function store(Request $request, Survey $survey): RedirectResponse
    {
        $typesWithOptions = ['multiple_choice', 'checkboxes', 'dropdown'];
        $type = $request->input('type');

        if (!in_array($type, $typesWithOptions, true)) {
            $request->merge(['options' => []]);
        }

        $rules = [
            'type' => ['required', Rule::in(['short_text', 'long_text', 'multiple_choice', 'checkboxes', 'dropdown', 'rating', 'number', 'email', 'date'])],
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
        }

        $validated = $request->validate($rules);

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

        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $index => $option) {
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

        $survey->update([
            'questions_count' => $survey->questions()->count(),
        ]);

        return redirect()->route('admin.surveys.questions.index', $survey);
    }

    public function destroy(Survey $survey, SurveyQuestion $question): RedirectResponse
    {
        if ($question->survey_id !== $survey->id) {
            abort(404);
        }

        $question->delete();
        $survey->update([
            'questions_count' => $survey->questions()->count(),
        ]);

        return redirect()->route('admin.surveys.questions.index', $survey);
    }
}
