<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

final class SystemBackupService
{
    public const FORMAT_VERSION = 1;

    public const DISK_SUBDIR = 'backups';

  /** @var string الگوی نام فایل پشتیبان (فقط حروف، عدد، زیرخط و خط تیره) */
    public const FILENAME_PATTERN = '/^backup_[0-9]{8}_[0-9]{6}\.zip$/';

    public function directory(): string
    {
        $dir = storage_path('app/'.self::DISK_SUBDIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return $dir;
    }

    /**
     * @return list<array{filename: string, size: int, size_human: string, created_at: string, created_at_ts: int}>
     */
    public function list(): array
    {
        $items = [];
        foreach (glob($this->directory().DIRECTORY_SEPARATOR.'backup_*.zip') ?: [] as $path) {
            $name = basename($path);
            if (! $this->isRestorableFilename($name)) {
                continue;
            }
            $mtime = filemtime($path) ?: 0;
            $size = filesize($path) ?: 0;
            $items[] = [
                'filename' => $name,
                'size' => $size,
                'size_human' => $this->humanSize($size),
                'created_at' => $this->formatJalaliTimestamp($mtime),
                'created_at_ts' => $mtime,
            ];
        }

        usort($items, fn (array $a, array $b) => $b['created_at_ts'] <=> $a['created_at_ts']);

        return $items;
    }

    /**
     * @return array{filename: string, size: int, size_human: string, created_at: string}
     */
    public function create(): array
    {
        $filename = 'backup_'.date('Ymd_His').'.zip';
        $target = $this->directory().DIRECTORY_SEPARATOR.$filename;

        $zip = new ZipArchive;
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('امکان ایجاد فایل فشرده پشتیبان وجود ندارد.');
        }

        $manifest = [
            'format_version' => self::FORMAT_VERSION,
            'app_name' => config('app.name'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'db_connection' => config('database.default'),
            'created_at' => now()->toIso8601String(),
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->addDatabaseToZip($zip);
        $this->addAppSettingsToZip($zip);
        $this->addPublicStorageToZip($zip);

        $zip->close();

        $size = filesize($target) ?: 0;

        return [
            'filename' => $filename,
            'size' => $size,
            'size_human' => $this->humanSize($size),
            'created_at' => $this->formatJalaliTimestamp(time()),
        ];
    }

    public function resolvePath(string $filename): string
    {
        if (! $this->isRestorableFilename($filename)) {
            throw new RuntimeException('نام فایل پشتیبان معتبر نیست.');
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;
        if (! is_file($path)) {
            throw new RuntimeException('فایل پشتیبان یافت نشد.');
        }

        return $path;
    }

    public function delete(string $filename): void
    {
        $path = $this->resolvePath($filename);
        if (! @unlink($path)) {
            throw new RuntimeException('حذف فایل پشتیبان انجام نشد.');
        }
    }

    /**
     * بازیابی از فایل zip روی دیسک (مسیر مطلق).
     */
    public function restore(string $zipPath): void
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException('فایل پشتیبان برای بازیابی یافت نشد.');
        }

        $this->validateArchive($zipPath);

        $safetyName = 'pre_restore_'.date('Ymd_His').'.zip';
        $safetyPath = $this->directory().DIRECTORY_SEPARATOR.$safetyName;
        try {
            $this->createSnapshotAt($safetyPath);
        } catch (\Throwable $e) {
            throw new RuntimeException('قبل از بازیابی، ایجاد نسخه امنیتی خودکار ممکن نشد: '.$e->getMessage());
        }

        $tempDir = storage_path('app/backup-restore-'.Str::random(16));
        File::ensureDirectoryExists($tempDir);

        $wasDown = app()->isDownForMaintenance();

        try {
            if (! $wasDown) {
                Artisan::call('down', ['--retry' => 60]);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('باز کردن فایل پشتیبان ناموفق بود.');
            }
            $this->extractZipSafely($zip, $tempDir);
            $zip->close();

            $this->restoreDatabaseFromExtracted($tempDir);
            $this->restoreAppSettingsFromExtracted($tempDir);
            $this->restorePublicStorageFromExtracted($tempDir);
        } finally {
            File::deleteDirectory($tempDir);
            if (! $wasDown) {
                Artisan::call('up');
            }
        }
    }

    public function isValidFilename(string $filename): bool
    {
        return (bool) preg_match(self::FILENAME_PATTERN, $filename)
            || (bool) preg_match('/^pre_restore_[0-9]{8}_[0-9]{6}\.zip$/', $filename);
    }

    public function isRestorableFilename(string $filename): bool
    {
        return (bool) preg_match(self::FILENAME_PATTERN, $filename);
    }

    private function formatJalaliTimestamp(int $timestamp): string
    {
        try {
            return Jalalian::fromCarbon(\Carbon\Carbon::createFromTimestamp($timestamp))->format('Y/m/d H:i');
        } catch (\Throwable) {
            return date('Y/m/d H:i', $timestamp);
        }
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' بایت';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' کیلوبایت';
        }

        return round($bytes / 1048576, 2).' مگابایت';
    }

    private function validateArchive(string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('فایل ارسالی یک آرشیو ZIP معتبر نیست.');
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        $zip->close();

        if ($manifestRaw === false) {
            throw new RuntimeException('فایل پشتیبان فاقد manifest.json است و قابل بازیابی نیست.');
        }

        try {
            $manifest = json_decode($manifestRaw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('manifest.json پشتیبان نامعتبر است.');
        }

        $version = (int) ($manifest['format_version'] ?? 0);
        if ($version !== self::FORMAT_VERSION) {
            throw new RuntimeException('نسخه قالب پشتیبان با سامانه سازگار نیست.');
        }

        $dbConn = (string) ($manifest['db_connection'] ?? '');
        if ($dbConn !== '' && $dbConn !== config('database.default')) {
            throw new RuntimeException(
                'این پشتیبان برای نوع پایگاه داده «'.$dbConn.'» است؛ سامانه فعلی «'.config('database.default').'» است.'
            );
        }
    }

    private function createSnapshotAt(string $targetPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ایجاد نسخه امنیتی ناموفق بود.');
        }

        $manifest = [
            'format_version' => self::FORMAT_VERSION,
            'type' => 'pre_restore_safety',
            'created_at' => now()->toIso8601String(),
            'db_connection' => config('database.default'),
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->addDatabaseToZip($zip);
        $this->addAppSettingsToZip($zip);
        $this->addPublicStorageToZip($zip);
        $zip->close();
    }

    private function addDatabaseToZip(ZipArchive $zip): void
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $dbFile = config('database.connections.sqlite.database');
            if (! is_file($dbFile)) {
                throw new RuntimeException('فایل پایگاه داده SQLite یافت نشد.');
            }
            $zip->addFile($dbFile, 'database/database.sqlite');

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $sql = $this->dumpMysqlToSql();
            $zip->addFromString('database/dump.sql', $sql);

            return;
        }

        throw new RuntimeException('نوع پایگاه داده «'.$driver.'» برای پشتیبان‌گیری پشتیبانی نمی‌شود.');
    }

    private function addAppSettingsToZip(ZipArchive $zip): void
    {
        $path = storage_path('app/app-settings.json');
        if (is_file($path)) {
            $zip->addFile($path, 'config/app-settings.json');
        }
    }

    private function addPublicStorageToZip(ZipArchive $zip): void
    {
        $root = storage_path('app/public');
        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            $relative = 'files/public/'.str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $zip->addFile($file->getPathname(), $relative);
        }
    }

    private function dumpMysqlToSql(): string
    {
        $conn = config('database.connections.'.config('database.default'));
        $host = $conn['host'] ?? '127.0.0.1';
        $port = $conn['port'] ?? '3306';
        $database = $conn['database'] ?? '';
        $username = $conn['username'] ?? '';
        $password = $conn['password'] ?? '';

        $mysqldump = $this->findMysqldumpBinary();
        if ($mysqldump !== null) {
            $process = new Process([
                $mysqldump,
                '--host='.$host,
                '--port='.$port,
                '--user='.$username,
                '--default-character-set=utf8mb4',
                '--single-transaction',
                '--routines',
                '--triggers',
                $database,
            ], null, ['MYSQL_PWD' => (string) $password]);
            $process->setTimeout(600);
            $process->run();

            if ($process->isSuccessful()) {
                return $process->getOutput();
            }
        }

        return $this->dumpMysqlViaPhp();
    }

    private function findMysqldumpBinary(): ?string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp2\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp3\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path)) {
                return $path;
            }
        }

        $which = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>nul' : 'which mysqldump 2>/dev/null'));

        return $which !== '' && is_file($which) ? $which : null;
    }

    private function dumpMysqlViaPhp(): string
    {
        $lines = ["SET NAMES utf8mb4;", "SET FOREIGN_KEY_CHECKS=0;"];
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.'.config('database.default').'.database');
        $key = 'Tables_in_'.$dbName;

        foreach ($tables as $row) {
            $table = $row->{$key} ?? null;
            if (! is_string($table) || $table === '') {
                continue;
            }

            $create = DB::selectOne('SHOW CREATE TABLE `'.str_replace('`', '``', $table).'`');
            $createSql = $create->{'Create Table'} ?? '';
            $lines[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
            $lines[] = $createSql.';';

            DB::table($table)->orderByRaw('1')->chunk(200, function ($chunk) use ($table, &$lines) {
                foreach ($chunk as $record) {
                    $cols = [];
                    $vals = [];
                    foreach ((array) $record as $col => $val) {
                        $cols[] = '`'.$col.'`';
                        $vals[] = $this->sqlValue($val);
                    }
                    $lines[] = 'INSERT INTO `'.$table.'` ('.implode(', ', $cols).') VALUES ('.implode(', ', $vals).');';
                }
            });
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines);
    }

    private function sqlValue(mixed $val): string
    {
        if ($val === null) {
            return 'NULL';
        }
        if (is_bool($val)) {
            return $val ? '1' : '0';
        }
        if (is_int($val) || is_float($val)) {
            return (string) $val;
        }

        return DB::getPdo()->quote((string) $val);
    }

    private function restoreDatabaseFromExtracted(string $tempDir): void
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $source = $tempDir.'/database/database.sqlite';
            if (! is_file($source)) {
                throw new RuntimeException('فایل database.sqlite در پشتیبان یافت نشد.');
            }
            $target = config('database.connections.sqlite.database');
            DB::disconnect();
            if (is_file($target)) {
                @unlink($target);
            }
            if (! copy($source, $target)) {
                throw new RuntimeException('کپی فایل SQLite هنگام بازیابی ناموفق بود.');
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $dump = $tempDir.'/database/dump.sql';
            if (! is_file($dump)) {
                throw new RuntimeException('فایل dump.sql در پشتیبان یافت نشد.');
            }
            $this->importMysqlDump(file_get_contents($dump) ?: '');

            return;
        }

        throw new RuntimeException('بازیابی برای نوع پایگاه داده فعلی پشتیبانی نمی‌شود.');
    }

    private function importMysqlDump(string $sql): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $buffer = '';
        $inString = false;
        $escape = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $buffer .= $ch;

            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($ch === '\\') {
                    $escape = true;
                } elseif ($ch === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($ch === "'") {
                $inString = true;

                continue;
            }

            if ($ch === ';') {
                $statement = trim($buffer);
                $buffer = '';
                if ($statement !== '' && ! str_starts_with($statement, '--')) {
                    DB::unprepared($statement);
                }
            }
        }

        $tail = trim($buffer);
        if ($tail !== '' && ! str_starts_with($tail, '--')) {
            DB::unprepared($tail);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function restoreAppSettingsFromExtracted(string $tempDir): void
    {
        $source = $tempDir.'/config/app-settings.json';
        if (! is_file($source)) {
            return;
        }

        $target = storage_path('app/app-settings.json');
        File::ensureDirectoryExists(dirname($target));
        if (! copy($source, $target)) {
            throw new RuntimeException('بازیابی تنظیمات سامانه ناموفق بود.');
        }
    }

    private function extractZipSafely(ZipArchive $zip, string $destination): void
    {
        File::ensureDirectoryExists($destination);
        $destinationReal = realpath($destination);
        if ($destinationReal === false) {
            throw new RuntimeException('ایجاد پوشهٔ موقت بازیابی ناموفق بود.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false || $entry === '') {
                continue;
            }

            $entry = str_replace('\\', '/', $entry);
            if (str_contains($entry, "\0") || str_starts_with($entry, '/') || preg_match('#^[a-zA-Z]:/#', $entry) === 1) {
                throw new RuntimeException('فایل پشتیبان حاوی مسیر نامعتبر است.');
            }

            $parts = array_values(array_filter(explode('/', $entry), static fn (string $p): bool => $p !== '' && $p !== '.'));
            foreach ($parts as $part) {
                if ($part === '..') {
                    throw new RuntimeException('فایل پشتیبان حاوی مسیر نامعتبر است.');
                }
            }

            if (str_ends_with($entry, '/')) {
                continue;
            }

            $relative = implode(DIRECTORY_SEPARATOR, $parts);
            $target = $destinationReal.DIRECTORY_SEPARATOR.$relative;
            $parent = dirname($target);
            File::ensureDirectoryExists($parent);
            $parentReal = realpath($parent);
            if ($parentReal === false || ! str_starts_with($parentReal, $destinationReal)) {
                throw new RuntimeException('فایل پشتیبان حاوی مسیر نامعتبر است.');
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                throw new RuntimeException('خواندن محتوای فایل پشتیبان ناموفق بود.');
            }
            if (file_put_contents($target, $contents) === false) {
                throw new RuntimeException('استخراج فایل پشتیبان ناموفق بود.');
            }
        }
    }

    private function restorePublicStorageFromExtracted(string $tempDir): void
    {
        $sourceRoot = $tempDir.'/files/public';
        if (! is_dir($sourceRoot)) {
            return;
        }

        $targetRoot = storage_path('app/public');
        File::ensureDirectoryExists($targetRoot);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($sourceRoot) + 1);
            $dest = $targetRoot.DIRECTORY_SEPARATOR.$relative;
            File::ensureDirectoryExists(dirname($dest));
            copy($file->getPathname(), $dest);
        }
    }
}
