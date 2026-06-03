<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Personnel;

trait ScopesSupervisorOrgAccess
{
    /**
     * null = بدون محدودیت (مدیر اصلی)، [] = بدون واحد تحت نظارت.
     *
     * @return list<int>|null
     */
    protected function supervisorUnitScope(): ?array
    {
        $admin = current_admin();
        if (! $admin || $admin->isAdmin()) {
            return null;
        }

        return $admin->supervisedUnitIds();
    }

    protected function authorizePersonnelInSupervisorScope(Personnel $personnel): void
    {
        $scope = $this->supervisorUnitScope();
        if ($scope === null) {
            return;
        }
        if ($scope === [] || ! in_array((int) $personnel->unit_id, $scope, true)) {
            abort(403, 'دسترسی به این پرسنل مجاز نیست.');
        }
    }

    protected function assertUnitIdInSupervisorScope(int $unitId): void
    {
        $scope = $this->supervisorUnitScope();
        if ($scope === null) {
            return;
        }
        if ($scope === [] || ! in_array($unitId, $scope, true)) {
            abort(403, 'انتخاب این واحد برای شما مجاز نیست.');
        }
    }
}
