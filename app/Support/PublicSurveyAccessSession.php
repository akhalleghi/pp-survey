<?php

namespace App\Support;

use App\Models\Personnel;
use App\Models\Survey;

final class PublicSurveyAccessSession
{
    private const SESSION_ROOT = 'public_survey_access';

    public static function grant(Survey $survey, Personnel $personnel): void
    {
        session()->put(self::key($survey->id), [
            'personnel_id' => (int) $personnel->id,
            'granted_at' => now()->timestamp,
        ]);
        session()->regenerate(true);
    }

    public static function clear(Survey $survey): void
    {
        session()->forget(self::key($survey->id));
    }

    public static function personnelId(Survey $survey): ?int
    {
        $payload = session(self::key($survey->id));
        if (! is_array($payload)) {
            return null;
        }

        $id = (int) ($payload['personnel_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public static function resolvePersonnel(Survey $survey): ?Personnel
    {
        $id = self::personnelId($survey);
        if (! $id) {
            return null;
        }

        return Personnel::query()->find($id);
    }

    private static function key(int $surveyId): string
    {
        return self::SESSION_ROOT.'.'.$surveyId;
    }
}
