<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

require_once app_path('Support/SimpleXLSX.php');
require_once app_path('Support/SimpleXLSXGen.php');

class PersonnelController extends Controller
{
    public function index(): View
    {
        $personnel = Personnel::query()
            ->with(['unit', 'position'])
            ->latest()
            ->paginate(10);

        $units = Unit::query()->orderBy('name')->get();
        $positions = Position::query()->orderBy('name')->get();
        $genders = Personnel::GENDERS;

        return view('admin.personnel', compact('personnel', 'units', 'positions', 'genders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createPersonnel', [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'personnel_code' => ['required', 'string', 'max:255', 'unique:personnel,personnel_code'],
            'mobile' => ['required', 'string', 'max:20'],
            'position_id' => ['required', 'exists:positions,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'gender' => ['required', Rule::in(array_keys(Personnel::GENDERS))],
            'national_code' => ['required', 'string', 'max:32', 'unique:personnel,national_code'],
            'birth_date' => ['required', 'date'],
        ]);

        Personnel::create($validated);

        return redirect()
            ->route('admin.personnel.index')
            ->with('status', 'اطلاعات پرسنل جدید با موفقیت ثبت شد.');
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePersonnel', [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'personnel_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('personnel', 'personnel_code')->ignore($personnel->id),
            ],
            'mobile' => ['required', 'string', 'max:20'],
            'position_id' => ['required', 'exists:positions,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'gender' => ['required', Rule::in(array_keys(Personnel::GENDERS))],
            'national_code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('personnel', 'national_code')->ignore($personnel->id),
            ],
            'birth_date' => ['required', 'date'],
        ]);

        $personnel->update($validated);

        return redirect()
            ->route('admin.personnel.index')
            ->with('status', 'ویرایش اطلاعات پرسنل با موفقیت انجام شد.');
    }

    public function destroy(Personnel $personnel): RedirectResponse
    {
        $personnel->delete();

        return redirect()
            ->route('admin.personnel.index')
            ->with('status', 'پرسنل موردنظر حذف شد.');
    }

    public function downloadTemplate()
    {
        $rows = [
            ['first_name', 'last_name', 'personnel_code', 'mobile', 'position_id', 'unit_id', 'gender', 'national_code', 'birth_date'],
            ['Ali', 'Ahmadi', "'00123", '09120000000', 1, 2, 1, '1234567890', '1400/01/01'],
        ];

        $filename = 'personnel-template.xlsx';
        $tempPath = storage_path('app/tmp/'.$filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        SimpleXLSXGen::fromArray($rows)->saveAs($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function bulkImport(Request $request): RedirectResponse
    {
        $request->validateWithBag('bulkPersonnel', [
            'import_file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $uploadedFile = $request->file('import_file');
        $fullPath = $uploadedFile?->getRealPath();

        if (!$fullPath || !file_exists($fullPath)) {
            return back()
                ->withInput(['form' => 'bulk'])
                ->withErrors(['import_file' => 'فایل بارگذاری شده قابل دسترسی نیست. لطفاً دوباره تلاش کنید.'], 'bulkPersonnel');
        }

        $xlsx = SimpleXLSX::parse($fullPath);

        if (!$xlsx) {
            return back()
                ->withInput(['form' => 'bulk'])
                ->withErrors(['import_file' => 'فایل اکسل باید با فرمت XLSX و ساختار صحیح باشد. جزئیات: '.SimpleXLSX::parseError()], 'bulkPersonnel');
        }

        $rows = $xlsx->rows();
        if (count($rows) <= 1) {
            return back()
                ->withInput(['form' => 'bulk'])
                ->withErrors(['import_file' => 'فایل انتخاب شده داده‌ای برای پردازش ندارد.'], 'bulkPersonnel');
        }

        $header = array_map(fn ($value) => $this->normalizeHeader($value), $rows[0]);
        $requiredColumns = [
            'first_name',
            'last_name',
            'personnel_code',
            'mobile',
            'position_id',
            'unit_id',
            'gender',
            'national_code',
            'birth_date',
        ];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $header, true)) {
                return back()
                    ->withInput(['form' => 'bulk'])
                    ->withErrors(['import_file' => "ستون {$column} در فایل آپلود شده یافت نشد."], 'bulkPersonnel');
            }
        }

        $columnIndex = array_flip($header);
        $created = $updated = $skipped = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $rowNumber => $row) {
            $excelRow = $rowNumber + 2;
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $payload = [];
            foreach ($requiredColumns as $column) {
                $payload[$column] = trim((string) ($row[$columnIndex[$column]] ?? ''));
            }
            $payload = $this->normalizePayload($payload);

            $validator = validator($payload, [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'personnel_code' => ['required', 'string', 'max:255'],
                'mobile' => ['required', 'string', 'max:20'],
                'position_id' => ['required'],
                'unit_id' => ['required'],
                'gender' => ['required'],
                'national_code' => ['required', 'string', 'max:32'],
                'birth_date' => ['required'],
            ], [], ['birth_date' => 'تاریخ تولد']);

            if ($validator->fails()) {
                $errors[] = "ردیف {$excelRow}: ".implode('، ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $unitId = $this->normalizeId($payload['unit_id']);
            $unit = $unitId ? Unit::find($unitId) : null;
            if (!$unit) {
                $errors[] = "ردیف {$excelRow}: واحد با شناسه «{$payload['unit_id']}» یافت نشد.";
                $skipped++;
                continue;
            }

            $positionId = $this->normalizeId($payload['position_id']);
            $position = $positionId ? Position::find($positionId) : null;
            if (!$position) {
                $errors[] = "ردیف {$excelRow}: سمت با شناسه «{$payload['position_id']}» یافت نشد.";
                $skipped++;
                continue;
            }

            $gender = $this->normalizeGender($payload['gender']);
            if (!$gender) {
                $errors[] = "ردیف {$excelRow}: مقدار جنسیت «{$payload['gender']}» معتبر نیست.";
                $skipped++;
                continue;
            }

            $birthDate = $this->normalizeDate($payload['birth_date']);
            if (!$birthDate) {
                $errors[] = "ردیف {$excelRow}: تاریخ تولد «{$payload['birth_date']}» معتبر نیست.";
                $skipped++;
                continue;
            }

            $personnel = Personnel::firstOrNew(['personnel_code' => $payload['personnel_code']]);
            $isNew = !$personnel->exists;
            $personnel->fill([
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'mobile' => $payload['mobile'],
                'position_id' => $position->id,
                'unit_id' => $unit->id,
                'gender' => $gender,
                'national_code' => $payload['national_code'],
                'birth_date' => $birthDate,
            ]);
            $personnel->save();

            $isNew ? $created++ : $updated++;
        }

        $status = "بارگذاری یکجا انجام شد: {$created} ردیف جدید، {$updated} ردیف ویرایش و {$skipped} ردیف نادیده گرفته شد.";

        return redirect()
            ->route('admin.personnel.index')
            ->with('status', $status)
            ->with('bulk_errors', $errors);
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeader(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        if (Str::contains($value, 'birth')) {
            return 'birth_date';
        }
        if (Str::contains($value, 'unit')) {
            return 'unit_id';
        }
        if (Str::contains($value, 'position')) {
            return 'position_id';
        }

        $value = str_replace(['(', ')'], '', $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value ?? '') ?? '';

        return trim($value, '_');
    }

    private function normalizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $payload[$key] = $this->normalizeDigits($value);
        }

        return $payload;
    }

    private function normalizeDigits(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $map = ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];

        return strtr(trim($value), $map);
    }

    private function normalizeId(?string $value): ?int
    {
        $value = $this->normalizeDigits($value);
        if ($value === '') {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function normalizeGender(?string $value): ?string
    {
        $value = $this->normalizeDigits($value);
        $mapNumeric = ['1' => 'male', '2' => 'female', '3' => 'other'];
        if (isset($mapNumeric[$value])) {
            return $mapNumeric[$value];
        }

        $value = Str::lower(trim($value));
        $map = [
            'male' => 'male',
            'مرد' => 'male',
            'female' => 'female',
            'زن' => 'female',
            'other' => 'other',
            'سایر' => 'other',
        ];

        return $map[$value] ?? null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = str_replace(['.', '\\'], '/', $this->normalizeDigits($value));

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $value)) {
            [$jy, $jm, $jd] = array_map('intval', explode('/', $value));
            [$gy, $gm, $gd] = $this->jalaliToGregorian($jy, $jm, $jd);

            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }

        return null;
    }

    private function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy -= 979;
        $gy = 1600;
        $days = (365 * $jy) + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4) + $jd - 1;
        $jMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $gMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        for ($i = 0; $i < $jm - 1; $i++) {
            $days += $jMonth[$i];
        }

        $gy += 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gm = 0;
        while ($gm < 12) {
            $leapAdd = ($gm === 1 && $this->isLeapGregorian($gy)) ? 1 : 0;
            $monthLength = $gMonth[$gm] + $leapAdd;
            if ($days < $monthLength) {
                break;
            }
            $days -= $monthLength;
            $gm++;
        }

        $gd = $days + 1;

        return [$gy, $gm + 1, $gd];
    }

    private function isLeapGregorian(int $year): bool
    {
        return (($year % 4 === 0) && ($year % 100 !== 0)) || ($year % 400 === 0);
    }
}
