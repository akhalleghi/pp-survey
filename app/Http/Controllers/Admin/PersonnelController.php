<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
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
            ->with('status', 'پرسنل جدید با موفقیت اضافه شد.');
    }

    /**
     * Update the specified resource in storage.
     */
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
            ->with('status', 'اطلاعات پرسنل با موفقیت ویرایش شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Personnel $personnel): RedirectResponse
    {
        $personnel->delete();

        return redirect()
            ->route('admin.personnel.index')
            ->with('status', 'پرسنل موردنظر حذف شد.');
    }
}
