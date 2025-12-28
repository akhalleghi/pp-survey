<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $allowedStatuses = ['active', 'draft', 'closed'];
        $statusFilter = in_array($request->query('status'), $allowedStatuses, true) ? $request->query('status') : null;

        $surveys = Survey::with('unit')
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
            'responses' => Survey::sum('responses_count'),
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
            'require_auth' => true,
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
            'notification_emails' => [],
        ]);

        return redirect()
            ->route('admin.surveys.index')
            ->with('status', 'نظرسنجی جدید با موفقیت ثبت شد.');
    }

    public function edit(Survey $survey): View
    {
        $audiencePresets = ['همه کاربران', 'واحد سازمانی', 'واحد فروش', 'واحد مالی', 'واحد منابع انسانی', 'فقط مدیران'];
        $statusOptions = ['draft' => 'در حال آماده سازی', 'active' => 'فعال', 'closed' => 'بسته شده'];
        $resultVisibilityOptions = ['private' => 'خصوصی', 'public' => 'عمومی', 'after_close' => 'پس از بسته شدن'];

        $backgroundImages = collect(glob(public_path('bg-images/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE))
            ->map(fn ($path) => basename($path))
            ->values()
            ->all();

        return view('admin.surveys-settings', compact('survey', 'audiencePresets', 'statusOptions', 'resultVisibilityOptions', 'backgroundImages'));
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

        $request->merge([
            'start_at' => $normalizeDateInput($request->input('start_at')),
            'end_at' => $normalizeDateInput($request->input('end_at')),
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
            'audience_filters' => ['nullable', 'array'],
            'audience_filters.*' => ['string', 'max:255'],
            'thank_you_message' => ['nullable', 'string', 'max:255'],
            'notification_emails' => ['nullable', 'string', 'max:1000'],
            'background_preset' => ['nullable', Rule::in($backgroundPresetOptions)],
            'background_upload' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $notificationEmails = [];
        if (!empty($validated['notification_emails'])) {
            $notificationEmails = array_filter(array_map('trim', explode(',', $validated['notification_emails'])));
        }
        $invalidEmails = array_filter($notificationEmails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) === false);
        if (!empty($invalidEmails)) {
            return back()
                ->withErrors(['notification_emails' => 'فرمت ایمیل ها معتبر نیست.'])
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
            'audience_filters' => $validated['audience_filters'] ?? [],
            'thank_you_message' => $validated['thank_you_message'] ?? null,
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
            ->with('status', '???? ??????? ????? ??.');
    }

}