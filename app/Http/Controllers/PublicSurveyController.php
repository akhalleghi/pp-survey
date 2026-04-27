<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $audienceConfig = $this->normalizeAudienceConfig($survey->audience_filters);
        $identityMode = $audienceConfig['identity_mode'];
        $needsIdentity = $identityMode !== 'none';
        $submittedPersonnelCode = $this->normalizeDigits(trim((string) $request->query('personnel_code', '')));
        $submittedNationalCode = $this->normalizeDigits(trim((string) $request->query('national_code', '')));
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
                        $accessError = 'شما در گروه مجاز برای این نظرسنجی قرار ندارید.';
                    }
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

        $showIntroStep = filled($survey->intro_text);
        $showAccessGate = $needsIdentity;

        return view('surveys.public-show', compact(
            'survey',
            'showIntroStep',
            'showAccessGate',
            'audiencePassed',
            'accessError',
            'identityMode',
            'submittedPersonnelCode',
            'submittedNationalCode'
        ));
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
