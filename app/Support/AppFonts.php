<?php

namespace App\Support;

/**
 * فونت‌های محلی قابل انتخاب برای کل سامانه.
 */
final class AppFonts
{
    public const DEFAULT_ID = 'vazirmatn';

    /**
     * @return array<string, array{id: string, label: string, family: string, css: string, sample: string}>
     */
    public static function registry(): array
    {
        return [
            'vazirmatn' => [
                'id' => 'vazirmatn',
                'label' => 'وزیرمتن',
                'family' => 'Vazirmatn',
                'css' => 'fonts/vazirmatn/vazirmatn.css',
                'sample' => 'نمونه متن فارسی — وزیرمتن ۱۲۳۴۵۶۷۸۹۰',
            ],
            'anjoman' => [
                'id' => 'anjoman',
                'label' => 'انجمن',
                'family' => 'Anjoman',
                'css' => 'fonts/Anjoman/anjoman.css',
                'sample' => 'نمونه متن فارسی — انجمن ۱۲۳۴۵۶۷۸۹۰',
            ],
            'estedad' => [
                'id' => 'estedad',
                'label' => 'استعداد',
                'family' => 'Estedad',
                'css' => 'fonts/Estedad/estedad.css',
                'sample' => 'نمونه متن فارسی — استعداد ۱۲۳۴۵۶۷۸۹۰',
            ],
            'iranyekan' => [
                'id' => 'iranyekan',
                'label' => 'ایران یکان',
                'family' => 'IRANYekan',
                'css' => 'fonts/IRANYekan/iranyekan.css',
                'sample' => 'نمونه متن فارسی — ایران‌یکان ۱۲۳۴۵۶۷۸۹۰',
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
     * @return array{id: string, label: string, family: string, css: string, sample: string}
     */
    public static function resolve(?string $id = null): array
    {
        $registry = self::registry();
        $key = is_string($id) && isset($registry[$id]) ? $id : self::currentId();

        $font = $registry[$key];
        $cssPath = public_path($font['css']);
        if (! is_file($cssPath)) {
            return $registry[self::DEFAULT_ID];
        }

        return $font;
    }

    public static function currentId(): string
    {
        $id = AppSettings::get('app_font', self::DEFAULT_ID);

        return is_string($id) && isset(self::registry()[$id]) ? $id : self::DEFAULT_ID;
    }

    public static function family(?string $id = null): string
    {
        return self::resolve($id)['family'];
    }

    public static function cssAsset(?string $id = null): string
    {
        return self::resolve($id)['css'];
    }

    public static function stack(?string $id = null): string
    {
        return "'".self::family($id)."', system-ui, -apple-system, 'Segoe UI', sans-serif";
    }
}
