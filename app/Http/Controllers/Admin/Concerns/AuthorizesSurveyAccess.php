<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\AdminUser;
use App\Models\Survey;

trait AuthorizesSurveyAccess
{
    protected function authorizeSurveyAccess(Survey $survey): void
    {
        $admin = current_admin();
        if (!$admin instanceof AdminUser) {
            abort(403);
        }
        if ($admin->isAdmin()) {
            return;
        }
        if ((int) $survey->created_by_admin_user_id === (int) $admin->id) {
            return;
        }
        abort(403, 'شما اجازه مدیریت این نظرسنجی را ندارید.');
    }
}
