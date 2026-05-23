<?php

namespace App\Support;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

final class SurveyAudience
{
    /**
     * @return array{
     *     identity_mode: string,
     *     modes: list<string>,
     *     unit_ids: list<int>,
     *     genders: list<string>,
     *     position_ids: list<int>,
     *     personnel_ids: list<int>
     * }
     */
    public static function normalize(mixed $value): array
    {
        $fallback = [
            'identity_mode' => 'none',
            'modes' => [],
            'unit_ids' => [],
            'genders' => [],
            'position_ids' => [],
            'personnel_ids' => [],
        ];

        if (! is_array($value)) {
            return $fallback;
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        if ($isList) {
            return $fallback;
        }

        $identityMode = $value['identity_mode'] ?? 'none';

        $unitIds = array_values(array_map('intval', (array) ($value['unit_ids'] ?? [])));
        $genders = array_values(array_filter(
            (array) ($value['genders'] ?? $value['gender'] ?? []),
            static fn ($gender) => in_array($gender, ['male', 'female', 'other'], true)
        ));
        $positionIds = array_values(array_map('intval', (array) ($value['position_ids'] ?? [])));
        $personnelIds = array_values(array_map('intval', (array) ($value['personnel_ids'] ?? [])));

        $modes = array_values(array_filter(
            (array) ($value['modes'] ?? []),
            static fn ($mode) => in_array($mode, ['unit', 'gender', 'position', 'personnel'], true)
        ));

        if ($modes === []) {
            if ($unitIds !== []) {
                $modes[] = 'unit';
            }
            if ($genders !== []) {
                $modes[] = 'gender';
            }
            if ($positionIds !== []) {
                $modes[] = 'position';
            }
            if ($personnelIds !== []) {
                $modes[] = 'personnel';
            }
        }

        return [
            'identity_mode' => in_array($identityMode, ['none', 'personnel_code', 'national_code', 'either'], true)
                ? $identityMode
                : 'none',
            'modes' => $modes,
            'unit_ids' => $unitIds,
            'genders' => $genders,
            'position_ids' => $positionIds,
            'personnel_ids' => $personnelIds,
        ];
    }

    /**
     * آیا برای این نظرسنجی فیلتر مخاطب فعال است؟
     *
     * @param  array<string, mixed>  $config
     */
    public static function hasActiveFilters(array $config): bool
    {
        $config = self::normalize($config);

        return ($config['modes'] ?? []) !== [];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function applyToQuery(Builder $query, array $config): Builder
    {
        $modes = $config['modes'] ?? [];
        if ($modes === []) {
            return $query;
        }

        if (in_array('unit', $modes, true)) {
            $unitIds = $config['unit_ids'] ?? [];
            $query->when(
                $unitIds !== [],
                fn (Builder $q) => $q->whereIn('unit_id', $unitIds),
                fn (Builder $q) => $q->whereRaw('0 = 1')
            );
        }

        if (in_array('position', $modes, true)) {
            $positionIds = $config['position_ids'] ?? [];
            $query->when(
                $positionIds !== [],
                fn (Builder $q) => $q->whereIn('position_id', $positionIds),
                fn (Builder $q) => $q->whereRaw('0 = 1')
            );
        }

        if (in_array('gender', $modes, true)) {
            $genders = $config['genders'] ?? [];
            $query->when(
                $genders !== [],
                fn (Builder $q) => $q->whereIn('gender', $genders),
                fn (Builder $q) => $q->whereRaw('0 = 1')
            );
        }

        if (in_array('personnel', $modes, true)) {
            $personnelIds = $config['personnel_ids'] ?? [];
            $query->when(
                $personnelIds !== [],
                fn (Builder $q) => $q->whereIn('id', $personnelIds),
                fn (Builder $q) => $q->whereRaw('0 = 1')
            );
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function personnelMatches(Personnel $personnel, array $config): bool
    {
        $modes = $config['modes'] ?? [];
        if ($modes === []) {
            return true;
        }

        if (in_array('unit', $modes, true) && ! in_array((int) $personnel->unit_id, $config['unit_ids'] ?? [], true)) {
            return false;
        }

        if (in_array('position', $modes, true) && ! in_array((int) $personnel->position_id, $config['position_ids'] ?? [], true)) {
            return false;
        }

        if (in_array('gender', $modes, true) && ! in_array((string) $personnel->gender, $config['genders'] ?? [], true)) {
            return false;
        }

        if (in_array('personnel', $modes, true) && ! in_array((int) $personnel->id, $config['personnel_ids'] ?? [], true)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function fromRequestInput(array $input): array
    {
        return self::normalize([
            'identity_mode' => $input['identity_mode'] ?? 'none',
            'modes' => $input['modes'] ?? [],
            'unit_ids' => $input['unit_ids'] ?? [],
            'genders' => $input['genders'] ?? [],
            'position_ids' => $input['position_ids'] ?? [],
            'personnel_ids' => $input['personnel_ids'] ?? [],
        ]);
    }

    /**
     * توضیح فارسی فیلتر مخاطب برای نمایش به ادمین.
     *
     * @param  array<string, mixed>  $config
     */
    public static function describeFilters(array $config): string
    {
        $config = self::normalize($config);

        if (! self::hasActiveFilters($config)) {
            return 'بدون محدودیت مخاطب — همهٔ پرسنل دارای شماره موبایل';
        }

        $parts = [];

        if (in_array('unit', $config['modes'], true)) {
            $names = Unit::query()->whereIn('id', $config['unit_ids'])->orderBy('name')->pluck('name');
            $parts[] = 'واحد سازمانی: '.($names->isNotEmpty() ? $names->implode('، ') : '—');
        }

        if (in_array('gender', $config['modes'], true)) {
            $labels = array_map(
                static fn (string $g) => Personnel::GENDERS[$g] ?? $g,
                $config['genders'] ?? []
            );
            $parts[] = 'جنسیت: '.($labels !== [] ? implode('، ', $labels) : '—');
        }

        if (in_array('position', $config['modes'], true)) {
            $names = Position::query()->whereIn('id', $config['position_ids'])->orderBy('name')->pluck('name');
            $parts[] = 'سمت: '.($names->isNotEmpty() ? $names->implode('، ') : '—');
        }

        if (in_array('personnel', $config['modes'], true)) {
            $count = count($config['personnel_ids'] ?? []);
            $parts[] = 'افراد مشخص: '.$count.' نفر';
        }

        return implode(' | ', $parts);
    }
}
