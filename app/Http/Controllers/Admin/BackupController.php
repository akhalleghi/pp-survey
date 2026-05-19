<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly SystemBackupService $backups,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'backups' => $this->backups->list(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'confirmed' => ['accepted'],
        ], [
            'confirmed.accepted' => 'برای ایجاد پشتیبان باید تأیید کنید.',
        ]);

        try {
            $created = $this->backups->create();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'ایجاد پشتیبان ناموفق بود: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'پشتیبان جدید با موفقیت ایجاد شد.',
            'backup' => $created,
            'backups' => $this->backups->list(),
        ]);
    }

    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        try {
            $path = $this->backups->resolvePath($filename);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function destroy(Request $request, string $filename): JsonResponse
    {
        $request->validate([
            'confirmed' => ['accepted'],
        ], [
            'confirmed.accepted' => 'برای حذف پشتیبان باید تأیید کنید.',
        ]);

        try {
            $this->backups->delete($filename);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'فایل پشتیبان حذف شد.',
            'backups' => $this->backups->list(),
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'confirmed' => ['accepted'],
            'source_type' => ['required', 'in:server,upload'],
            'filename' => ['required_if:source_type,server', 'string', 'max:80'],
            'backup_file' => ['required_if:source_type,upload', 'file', 'mimes:zip', 'max:524288'],
        ], [
            'confirmed.accepted' => 'برای بازیابی باید تأیید کنید.',
            'backup_file.mimes' => 'فایل باید با پسوند zip باشد.',
            'backup_file.max' => 'حداکثر حجم فایل آپلود ۵۱۲ مگابایت است.',
        ]);

        $tempPath = null;

        try {
            if ($request->input('source_type') === 'server') {
                $zipPath = $this->backups->resolvePath((string) $request->input('filename'));
            } else {
                $uploaded = $request->file('backup_file');
                if (! $uploaded) {
                    throw new \RuntimeException('فایل پشتیبان ارسال نشد.');
                }
                $storedName = 'upload_'.date('Ymd_His').'_'.uniqid('', true).'.zip';
                $stored = $uploaded->storeAs('backup-uploads', $storedName, 'local');
                $tempPath = Storage::disk('local')->path($stored);
                $zipPath = $tempPath;
            }

            $this->backups->restore($zipPath);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'بازیابی ناموفق بود: '.$e->getMessage(),
            ], 500);
        } finally {
            if ($tempPath && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'بازیابی با موفقیت انجام شد. در صورت نیاز صفحه را یک‌بار تازه‌سازی کنید.',
            'backups' => $this->backups->list(),
        ]);
    }
}
