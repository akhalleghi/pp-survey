<?php

namespace App\Services\Sms;

use App\Models\Personnel;
use App\Models\Survey;
use App\Support\SmsTargetingMode;
use App\Support\SurveyAudience;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class SmsRecipientResolver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    public function resolve(array $payload): array
    {
        $mode = (string) ($payload['targeting_mode'] ?? '');
        $survey = null;

        if (! empty($payload['survey_id'])) {
            $survey = Survey::query()->find((int) $payload['survey_id']);
            if (! $survey) {
                throw ValidationException::withMessages(['survey_id' => 'نظرسنجی انتخاب‌شده یافت نشد.']);
            }
        }

        $recipients = match ($mode) {
            SmsTargetingMode::SURVEY_ELIGIBLE => $this->resolveSurveyEligible($survey),
            SmsTargetingMode::ALL_PERSONNEL => $this->resolveAllPersonnel(),
            SmsTargetingMode::CUSTOM_FILTERS => $this->resolveCustomFilters($payload),
            SmsTargetingMode::SELECTED_PERSONNEL => $this->resolveSelectedPersonnel($payload),
            SmsTargetingMode::FREE_NUMBERS => $this->resolveFreeNumbers($payload),
            default => throw ValidationException::withMessages(['targeting_mode' => 'نحوه ارسال معتبر نیست.']),
        };

        return $this->dedupeAndValidate($recipients);
    }

    /**
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function resolveSurveyEligible(?Survey $survey): array
    {
        if (! $survey) {
            throw ValidationException::withMessages(['survey_id' => 'برای این حالت، انتخاب نظرسنجی الزامی است.']);
        }

        $survey = Survey::query()
            ->whereKey($survey->id)
            ->first(['id', 'audience_filters']);

        if (! $survey) {
            throw ValidationException::withMessages(['survey_id' => 'نظرسنجی یافت نشد.']);
        }

        $config = SurveyAudience::normalize($survey->audience_filters);

        $query = $this->personnelWithMobileQuery();
        SurveyAudience::applyToQuery($query, $config);

        return $this->personnelToRecipients(
            $query
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
        );
    }

    /**
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function resolveAllPersonnel(): array
    {
        return $this->personnelToRecipients(
            $this->personnelWithMobileQuery()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function resolveCustomFilters(array $payload): array
    {
        $config = SurveyAudience::fromRequestInput([
            'modes' => $payload['audience_modes'] ?? [],
            'unit_ids' => $payload['audience_unit_ids'] ?? [],
            'genders' => $payload['audience_genders'] ?? [],
            'position_ids' => $payload['audience_position_ids'] ?? [],
            'personnel_ids' => $payload['audience_personnel_ids'] ?? [],
        ]);

        if (($config['modes'] ?? []) === []) {
            throw ValidationException::withMessages(['audience_modes' => 'حداقل یک معیار فیلتر را انتخاب کنید.']);
        }

        return $this->personnelToRecipients(
            $this->personnelWithMobileQuery()
                ->tap(fn ($q) => SurveyAudience::applyToQuery($q, $config))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function resolveSelectedPersonnel(array $payload): array
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($payload['personnel_ids'] ?? []))));
        if ($ids === []) {
            throw ValidationException::withMessages(['personnel_ids' => 'حداقل یک پرسنل را انتخاب کنید.']);
        }

        $found = $this->personnelWithMobileQuery()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $missing = array_values(array_diff($ids, $found->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'personnel_ids' => 'برخی پرسنل انتخاب‌شده یافت نشدند یا شماره موبایل معتبر ندارند.',
            ]);
        }

        $ordered = collect($ids)->map(fn (int $id) => $found->get($id))->filter();

        return $this->personnelToRecipients($ordered);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function resolveFreeNumbers(array $payload): array
    {
        $raw = (string) ($payload['free_numbers'] ?? '');
        $lines = preg_split('/\R+/', $raw) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $mobile = $this->normalizeMobileOrFail($line);
            $out[] = [
                'personnel_id' => null,
                'mobile' => $mobile,
                'name' => null,
                'personnel' => null,
            ];
        }

        if ($out === []) {
            throw ValidationException::withMessages(['free_numbers' => 'حداقل یک شماره موبایل وارد کنید.']);
        }

        return $out;
    }

    /**
     * @param  Collection<int, Personnel>|iterable<Personnel>  $personnel
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function personnelToRecipients(iterable $personnel): array
    {
        $out = [];
        foreach ($personnel as $person) {
            $mobile = $this->normalizeMobileOrFail((string) $person->mobile);
            $out[] = [
                'personnel_id' => (int) $person->id,
                'mobile' => $mobile,
                'name' => SmsMessageComposer::fullName($person),
                'personnel' => $person,
            ];
        }

        if ($out === []) {
            throw ValidationException::withMessages([
                'targeting_mode' => 'هیچ گیرنده‌ای با شماره موبایل معتبر یافت نشد.',
            ]);
        }

        return $out;
    }

    /**
     * @param  list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>  $recipients
     * @return list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>
     */
    private function dedupeAndValidate(array $recipients): array
    {
        $max = max(1, (int) config('sms.campaign_max_recipients', 500));
        $seen = [];
        $out = [];

        foreach ($recipients as $row) {
            $key = $row['mobile'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        if (count($out) > $max) {
            throw ValidationException::withMessages([
                'targeting_mode' => "حداکثر {$max} گیرنده در هر ارسال مجاز است. تعداد فعلی: ".count($out),
            ]);
        }

        return $out;
    }

    private function personnelWithMobileQuery()
    {
        return Personnel::query()
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '');
    }

    private function normalizeMobileOrFail(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', SmsPanelService::normalizeMobile($mobile)) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }
        if (! preg_match('/^9\d{9}$/', $digits)) {
            throw ValidationException::withMessages([
                'free_numbers' => "شماره «{$mobile}» معتبر نیست.",
            ]);
        }

        return $digits;
    }

    /**
     * @param  list<array{personnel_id: int|null, mobile: string, name: string|null, personnel: Personnel|null}>  $recipients
     */
    public static function checksum(array $recipients): string
    {
        $pairs = array_map(
            static fn (array $r) => ($r['personnel_id'] ?? 'x').':'.$r['mobile'],
            $recipients
        );
        sort($pairs);

        return hash('sha256', implode('|', $pairs));
    }
}
