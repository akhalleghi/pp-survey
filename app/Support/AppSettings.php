<?php

namespace App\Support;

class AppSettings
{
    protected const FILE_NAME = 'app-settings.json';

    protected static function defaults(): array
    {
        return [
            'app_name' => 'سامانه نظرسنجی',
            'logo_path' => 'storage/logo.png',
            'colors' => self::defaultColors(),
        ];
    }

    protected static function defaultColors(): array
    {
        return [
            'primary' => '#D61119',
            'primary_dark' => '#ab0c12',
            'slate' => '#0F172A',
            'muted' => '#6B7280',
            'sidebar' => '#0c111d',
            'background' => '#f4f5f7',
            'accent_light' => '#ffe8e9',
            'accent_lighter' => '#f5f5f7',
            'text_primary' => '#111827',
            'welcome_background' => '#F9FAFB',
        ];
    }

    protected static function path(): string
    {
        return storage_path('app/' . self::FILE_NAME);
    }

    public static function all(): array
    {
        $defaults = self::defaults();
        $path = self::path();

        if (!file_exists($path)) {
            return $defaults;
        }

        try {
            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $defaults;
        }

        if (!is_array($data)) {
            return $defaults;
        }

        return array_merge($defaults, $data);
    }

    public static function update(array $values): void
    {
        $current = self::all();
        $updated = array_merge($current, array_filter($values, fn ($value) => $value !== null));

        if (!is_dir(dirname(self::path()))) {
            mkdir(dirname(self::path()), 0755, true);
        }

        file_put_contents(
            self::path(),
            json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function color(string $key, string $fallback = '#000000'): string
    {
        $colors = self::get('colors', self::defaultColors());

        return $colors[$key] ?? $fallback;
    }
}
