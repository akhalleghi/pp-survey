<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\Unit;
use App\Models\UnitSupervisor;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitSupervisorController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $unitFilter = $request->query('unit');

        $supervisors = UnitSupervisor::query()
            ->with(['unit', 'personnel', 'linkedAdminUser'])
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

        $permissionLabels = AdminPermissions::supervisorPortalAssignableDefinitions();
        $defaultPortalPermissions = AdminPermissions::defaultSupervisorPortalAssignablePermissions();

        return view('admin.unit-supervisors', compact(
            'supervisors',
            'units',
            'personnel',
            'filters',
            'permissionLabels',
            'defaultPortalPermissions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createSupervisor', array_merge($this->supervisorCoreRules($request, null), $this->portalRules($request, null)));

        $existingLogin = AdminUser::query()
            ->where('personnel_code', $validated['personnel_code'])
            ->where('role', AdminUser::ROLE_SUPERVISOR)
            ->first();
        if (!empty($validated['portal_username']) && !$existingLogin && empty($validated['portal_password'])) {
            return back()
                ->withErrors(['portal_password' => 'برای ایجاد حساب پنل، رمز عبور را وارد کنید.'], 'createSupervisor')
                ->withInput();
        }

        DB::transaction(function () use ($validated, $request) {
            UnitSupervisor::create([
                'personnel_code' => $validated['personnel_code'],
                'unit_id' => $validated['unit_id'],
            ]);

            $this->syncPortalAccountForPersonnel($validated['personnel_code'], $request, $validated);
        });

        return redirect()
            ->route('admin.unit-supervisors.index')
            ->with('status', 'ناظر جدید با موفقیت ثبت شد.');
    }

    public function update(Request $request, UnitSupervisor $unitSupervisor): RedirectResponse
    {
        $validated = $request->validateWithBag('updateSupervisor', array_merge($this->supervisorCoreRules($request, $unitSupervisor), $this->portalRules($request, $unitSupervisor)));

        $existingLogin = AdminUser::query()
            ->where('personnel_code', $validated['personnel_code'])
            ->where('role', AdminUser::ROLE_SUPERVISOR)
            ->first();
        if (!empty($validated['portal_username']) && !$existingLogin && empty($validated['portal_password'])) {
            return back()
                ->withErrors(['portal_password' => 'برای ایجاد حساب پنل، رمز عبور را وارد کنید.'], 'updateSupervisor')
                ->withInput();
        }

        DB::transaction(function () use ($validated, $request, $unitSupervisor) {
            $unitSupervisor->update([
                'personnel_code' => $validated['personnel_code'],
                'unit_id' => $validated['unit_id'],
            ]);

            $this->syncPortalAccountForPersonnel($validated['personnel_code'], $request, $validated);
        });

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

    /**
     * @return array<string, mixed>
     */
    private function supervisorCoreRules(Request $request, ?UnitSupervisor $existing): array
    {
        return [
            'personnel_code' => [
                'required',
                'string',
                Rule::exists('personnel', 'personnel_code'),
                Rule::unique('unit_supervisors', 'personnel_code')
                    ->where(fn ($q) => $q->where('unit_id', $request->input('unit_id')))
                    ->ignore($existing?->id),
            ],
            'unit_id' => ['required', 'exists:units,id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function portalRules(Request $request, ?UnitSupervisor $existing): array
    {
        $linkedId = null;
        if ($existing && $existing->admin_user_id) {
            $linkedId = $existing->admin_user_id;
        } elseif ($request->filled('personnel_code')) {
            $linkedId = UnitSupervisor::where('personnel_code', $request->input('personnel_code'))
                ->whereNotNull('admin_user_id')
                ->value('admin_user_id');
        }

        $usernameUnique = Rule::unique('admin_users', 'username');
        if ($linkedId) {
            $usernameUnique->ignore($linkedId);
        }

        return [
            'portal_username' => [
                'nullable',
                'string',
                'min:3',
                'max:64',
                $usernameUnique,
            ],
            'portal_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'portal_permissions' => ['nullable', 'array'],
            'portal_permissions.*' => ['string', Rule::in(AdminPermissions::allKeys())],
            'portal_active' => ['nullable', 'boolean'],
            'requires_survey_publish_approval' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncPortalAccountForPersonnel(string $personnelCode, Request $request, array $validated): void
    {
        $username = isset($validated['portal_username']) ? trim((string) $validated['portal_username']) : '';
        $password = $validated['portal_password'] ?? null;
        $perms = AdminUser::normalizePermissionsInput($validated['portal_permissions'] ?? []);
        if ($perms === []) {
            $perms = AdminPermissions::defaultSupervisorPermissions();
        }
        if (! in_array(AdminPermissions::DASHBOARD, $perms, true)) {
            $perms[] = AdminPermissions::DASHBOARD;
        }
        $perms = array_values(array_unique($perms));

        $existingLogin = AdminUser::query()
            ->where('personnel_code', $personnelCode)
            ->where('role', AdminUser::ROLE_SUPERVISOR)
            ->first();

        if ($username === '' && !$existingLogin && !$password) {
            return;
        }

        if ($username === '' && $existingLogin) {
            $existingLogin->update([
                'permissions' => $perms,
                'is_active' => $request->boolean('portal_active'),
                'requires_survey_publish_approval' => $request->boolean('requires_survey_publish_approval'),
            ]);
            UnitSupervisor::where('personnel_code', $personnelCode)->update(['admin_user_id' => $existingLogin->id]);

            return;
        }

        if ($username === '') {
            return;
        }

        $personnel = Personnel::where('personnel_code', $personnelCode)->first();
        $displayName = $personnel ? trim($personnel->first_name . ' ' . $personnel->last_name) : $username;

        $user = $existingLogin ?? new AdminUser([
            'role' => AdminUser::ROLE_SUPERVISOR,
            'personnel_code' => $personnelCode,
        ]);

        $user->username = $username;
        $user->name = $displayName ?: $username;
        $user->permissions = $perms;
        $user->role = AdminUser::ROLE_SUPERVISOR;
        $user->personnel_code = $personnelCode;
        $user->is_active = $request->boolean('portal_active');
        $user->requires_survey_publish_approval = $request->boolean('requires_survey_publish_approval');

        if (!empty($password)) {
            $user->password = $password;
        }

        $user->save();

        UnitSupervisor::where('personnel_code', $personnelCode)->update(['admin_user_id' => $user->id]);
    }
}
