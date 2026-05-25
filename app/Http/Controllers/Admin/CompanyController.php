<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $companies = Company::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.companies', [
            'companies' => $companies,
            'search' => $search,
            'typeLabels' => Company::typeLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createCompany', $this->rules());

        Company::create($validated);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'شرکت جدید با موفقیت ثبت شد.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validateWithBag('updateCompany', $this->rules($company));

        $company->update($validated);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'تغییرات شرکت ذخیره شد.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'شرکت حذف شد.');
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?Company $company = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'name')->ignore($company?->id),
            ],
            'type' => ['required', 'string', Rule::in(Company::typeKeys())],
        ];
    }
}
