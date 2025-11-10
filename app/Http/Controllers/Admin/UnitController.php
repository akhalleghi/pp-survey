<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $units = Unit::query()
            ->latest()
            ->paginate(10);

        return view('admin.units', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createUnit', [
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
        ]);

        Unit::create($validated);

        return redirect()
            ->route('admin.units.index')
            ->with('status', 'واحد جدید با موفقیت ثبت شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return redirect()
            ->route('admin.units.index')
            ->with('status', 'واحد حذف شد.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validateWithBag('updateUnit', [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->ignore($unit->id),
            ],
        ]);

        $unit->update($validated);

        return redirect()
            ->route('admin.units.index')
            ->with('status', 'تغییرات واحد ذخیره شد.');
    }
}
