<?php

namespace App\Services\Sms;

use App\Models\Personnel;
use App\Models\Survey;

final class SmsMessageComposer
{
    public const DEFAULT_COMPANY = 'شرکت جهان فولاد سیرجان';

    public static function defaultSurveyTemplate(Survey $survey, string $publicUrl): string
    {
        return implode("\n", [
            'آقای/خانم {name}',
            'این پیامک جهت شرکت در نظرسنجی «'.$survey->title.'» برای شما ارسال گردیده است.',
            'لطفا ما را در تکمیل این پرسشنامه همراهی نمایید.',
            'لینک شرکت در نظرسنجی:',
            $publicUrl,
            self::DEFAULT_COMPANY,
        ]);
    }

    public static function honorificFor(Personnel $personnel): string
    {
        return match ($personnel->gender) {
            'female' => 'خانم',
            'male' => 'آقای',
            default => 'آقای/خانم',
        };
    }

    public static function fullName(Personnel $personnel): string
    {
        return trim($personnel->first_name.' '.$personnel->last_name);
    }

    public static function personalize(string $template, Personnel $personnel, ?Survey $survey, string $publicUrl): string
    {
        $name = self::fullName($personnel);
        $honorific = self::honorificFor($personnel);

        $body = str_replace(
            ['{name}', '{honorific}', '{title}', '{link}', '{survey}'],
            [$name, $honorific, $survey?->title ?? '', $publicUrl, $survey?->title ?? ''],
            $template
        );

        if (str_contains($body, 'آقای/خانم') && $honorific !== 'آقای/خانم') {
            $body = str_replace('آقای/خانم', $honorific, $body);
        }

        return trim($body);
    }

    public static function personalizeFree(string $template, string $publicUrl, ?Survey $survey): string
    {
        return trim(str_replace(
            ['{name}', '{honorific}', '{title}', '{link}', '{survey}'],
            ['', '', $survey?->title ?? '', $publicUrl, $survey?->title ?? ''],
            $template
        ));
    }
}
