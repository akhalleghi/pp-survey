<?php

namespace App\Support;

/**
 * اندازهٔ پایهٔ متن و مقیاس کلی رابط کاربری (بر پایهٔ rem).
 */
final class AppTextScale
{
    public const DEFAULT_ID = 'md';

    /**
     * @return array<string, array{id: string, label: string, root: string, factor: float}>
     */
    public static function registry(): array
    {
        return [
            'xs' => [
                'id' => 'xs',
                'label' => 'خیلی کوچک',
                'root' => '13px',
                'factor' => 0.8125,
            ],
            'sm' => [
                'id' => 'sm',
                'label' => 'کوچک',
                'root' => '14px',
                'factor' => 0.875,
            ],
            'md' => [
                'id' => 'md',
                'label' => 'متوسط',
                'root' => '16px',
                'factor' => 1.0,
            ],
            'lg' => [
                'id' => 'lg',
                'label' => 'بزرگ',
                'root' => '18px',
                'factor' => 1.125,
            ],
            'xl' => [
                'id' => 'xl',
                'label' => 'خیلی بزرگ',
                'root' => '20px',
                'factor' => 1.25,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_keys(self::registry());
    }

    /**
     * @return array{id: string, label: string, root: string, factor: float}
     */
    public static function resolve(?string $id = null): array
    {
        $registry = self::registry();
        $key = is_string($id) && isset($registry[$id]) ? $id : self::currentId();

        return $registry[$key];
    }

    public static function currentId(): string
    {
        $id = AppSettings::get('app_text_scale', self::DEFAULT_ID);

        return is_string($id) && isset(self::registry()[$id]) ? $id : self::DEFAULT_ID;
    }
}
