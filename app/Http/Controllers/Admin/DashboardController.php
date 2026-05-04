<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\Unit;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $admin = current_admin();

        if ($admin instanceof AdminUser && $admin->isSupervisor()) {
            $unitIds = $admin->supervisedUnitIds();
            $scope = Survey::query()->where('created_by_admin_user_id', $admin->id);
            $surveyIds = (clone $scope)->pluck('id');
            $stats = [
                'units' => count($unitIds),
                'positions' => 0,
                'personnel' => 0,
                'active_surveys' => (clone $scope)->where('status', 'active')->count(),
                'my_responses' => SurveyResponse::whereIn('survey_id', $surveyIds)->where('status', 'submitted')->count(),
            ];

            return view('admin.dashboard', compact('admin', 'stats'));
        }

        $stats = [
            'units' => Unit::count(),
            'positions' => Position::count(),
            'personnel' => Personnel::count(),
        ];

        return view('admin.dashboard', compact('admin', 'stats'));
    }
}
