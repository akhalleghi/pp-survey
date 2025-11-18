<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Unit;
use App\Models\UnitSupervisor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitSupervisorController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $unitFilter = $request->query('unit');

        $supervisors = UnitSupervisor::query()
            ->with(['unit', 'personnel'])
            ->when($unitFilter, fn ($query) => $query->where('unit_id', $unitFilter))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('personnel_code', 'like', "%{$search}%")
                        ->orWhereHas('personnel', function ($personQuery) use ($search) {
                            $personQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->appends([
                'search' => $search,
                'unit' => $unitFilter,
            ]);

        $units = Unit::query()->orderBy('name')->get();
        $personnel = Personnel::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $filters = [
            'search' => $search,
            'unit' => $unitFilter,
        ];

        return view('admin.unit-supervisors', compact('supervisors', 'units', 'personnel', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createSupervisor', [
            'personnel_code' => [
                'required',
                'string',
                Rule::exists('personnel', 'personnel_code'),
                Rule::unique('unit_supervisors', 'personnel_code'),
            ],
            'unit_id' => ['required', 'exists:units,id'],
        ]);

        UnitSupervisor::create($validated);

        return redirect()
            ->route('admin.unit-supervisors.index')
            ->with('status', 'ناظر جدید با موفقیت ثبت شد.');
    }

    public function update(Request $request, UnitSupervisor $unitSupervisor): RedirectResponse
    {
        $validated = $request->validateWithBag('updateSupervisor', [
            'personnel_code' => [
                'required',
                'string',
                Rule::exists('personnel', 'personnel_code'),
                Rule::unique('unit_supervisors', 'personnel_code')->ignore($unitSupervisor->id),
            ],
            'unit_id' => ['required', 'exists:units,id'],
        ]);

        $unitSupervisor->update($validated);

        return redirect()
            ->route('admin.unit-supervisors.index')
            ->with('status', 'اطلاعات ناظر با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(UnitSupervisor $unitSupervisor): RedirectResponse
    {
        $unitSupervisor->delete();

        return redirect()
            ->route('admin.unit-supervisors.index')
            ->with('status', 'ناظر انتخابی حذف شد.');
    }
}
