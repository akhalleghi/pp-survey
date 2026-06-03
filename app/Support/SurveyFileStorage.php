<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ذخیرهٔ فایل‌های پاسخ نظرسنجی روی دیسک خصوصی (غیرقابل دسترسی مستقیم از وب).
 */
final class SurveyFileStorage
{
    public const DISK = 'local';

    private const PATH_PREFIX = 'survey-uploads/';

    /** @var array<string, list<string>> */
    private const EXTENSION_MIME_MAP = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    public static function storeUpload(UploadedFile $file, int $surveyId, int $responseId, array $allowedExtensions, int $maxKb): string
    {
        self::assertAllowedUpload($file, $allowedExtensions, $maxKb);

        return $file->store(self::PATH_PREFIX.$surveyId.'/'.$responseId, self::DISK);
    }

    /**
     * @param  list<string>  $allowedExtensions
     */
    public static function assertAllowedUpload(UploadedFile $file, array $allowedExtensions, int $maxKb): void
    {
        if ($maxKb <= 0 || $allowedExtensions === []) {
            throw ValidationException::withMessages([
                'file' => 'تنظیمات سوال فایل کامل نیست.',
            ]);
        }

        $ext = mb_strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '' || ! in_array($ext, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'پسوند فایل مجاز نیست.',
            ]);
        }

        if ($file->getSize() > ($maxKb * 1024)) {
            throw ValidationException::withMessages([
                'file' => 'حجم فایل بیشتر از حد مجاز است.',
            ]);
        }

        $mime = (string) $file->getMimeType();
        $allowedMimes = self::EXTENSION_MIME_MAP[$ext] ?? [];
        if ($allowedMimes !== [] && $mime !== '' && $mime !== 'application/octet-stream' && ! in_array($mime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => 'نوع فایل با پسوند ارسالی همخوانی ندارد.',
            ]);
        }
    }

    public static function exists(string $path): bool
    {
        if (self::isPrivatePath($path)) {
            return Storage::disk(self::DISK)->exists($path);
        }

        return Storage::disk('public')->exists($path);
    }

    public static function delete(string $path): void
    {
        if ($path === '') {
            return;
        }
        if (self::isPrivatePath($path) || Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);

            return;
        }
        Storage::disk('public')->delete($path);
    }

    public static function download(string $path, string $downloadName): StreamedResponse
    {
        if (self::isPrivatePath($path) || Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->download($path, $downloadName);
        }

        return Storage::disk('public')->download($path, $downloadName);
    }

    public static function isPrivatePath(string $path): bool
    {
        return str_starts_with($path, self::PATH_PREFIX);
    }

    public static function pathBelongsToResponse(string $path, int $surveyId, int $responseId): bool
    {
        $expectedPrefix = self::PATH_PREFIX.$surveyId.'/'.$responseId.'/';

        return str_starts_with($path, $expectedPrefix) || str_starts_with($path, 'survey-uploads/'.$surveyId.'/'.$responseId.'/');
    }
}
