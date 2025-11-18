<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $positions = Position::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.positions', [
            'positions' => $positions,
            'search' => $search,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createPosition', [
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
        ]);

        Position::create($validated);

        return redirect()
            ->route('admin.positions.index')
            ->with('status', 'سمت جدید با موفقیت ثبت شد.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePosition', [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->ignore($position->id),
            ],
        ]);

        $position->update($validated);

        return redirect()
            ->route('admin.positions.index')
            ->with('status', 'تغییرات سمت ذخیره شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): RedirectResponse
    {
        $position->delete();

        return redirect()
            ->route('admin.positions.index')
            ->with('status', 'سمت حذف شد.');
    }
}
