<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(): View
    {
        $admin = current_admin();

        $surveyScope = Survey::query();
        if ($admin instanceof AdminUser && $admin->isSupervisor()) {
            $surveyScope->where('created_by_admin_user_id', $admin->id);
        }

        $surveyIds = (clone $surveyScope)->pluck('id');

        $totalSurveys = (clone $surveyScope)->count();
        $sumQuestions = (int) (clone $surveyScope)->sum('questions_count');
        $avgQuestions = $totalSurveys > 0 ? round($sumQuestions / $totalSurveys, 1) : 0.0;

        $statusRow = (clone $surveyScope)
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $byStatus = [
            'draft' => (int) ($statusRow['draft'] ?? 0),
            'pending_approval' => (int) ($statusRow['pending_approval'] ?? 0),
            'active' => (int) ($statusRow['active'] ?? 0),
            'closed' => (int) ($statusRow['closed'] ?? 0),
        ];

        $surveysActiveFlagOn = (clone $surveyScope)->where('is_active', true)->count();
        $surveysActiveFlagOff = (clone $surveyScope)->where('is_active', false)->count();

        $withPublicLink = (clone $surveyScope)->whereNotNull('public_token')->where('public_token', '!=', '')->count();
        $withoutPublicLink = max(0, $totalSurveys - $withPublicLink);

        $responseBase = SurveyResponse::query()->whereIn('survey_id', $surveyIds);
        $totalSubmitted = (clone $responseBase)->where('status', 'submitted')->count();
        $totalDraftResponses = (clone $responseBase)->where('status', 'draft')->count();

        $avgResponsesPerSurvey = $totalSurveys > 0 ? round($totalSubmitted / $totalSurveys, 1) : 0.0;

        $completionRate = ($totalSubmitted + $totalDraftResponses) > 0
            ? round(100 * $totalSubmitted / ($totalSubmitted + $totalDraftResponses), 1)
            : null;

        // پاسخ‌ها به تفکیک واحد (فعال‌ترین واحدها)
        $unitActivity = collect();
        if ($surveyIds->isNotEmpty()) {
            $unitActivity = SurveyResponse::query()
                ->whereIn('survey_responses.survey_id', $surveyIds)
                ->where('survey_responses.status', 'submitted')
                ->join('surveys', 'surveys.id', '=', 'survey_responses.survey_id')
                ->join('units', 'units.id', '=', 'surveys.unit_id')
                ->select('units.id', 'units.name', DB::raw('count(survey_responses.id) as response_count'))
                ->groupBy('units.id', 'units.name')
                ->orderByDesc('response_count')
                ->limit(12)
                ->get();
        }

        // روند ۳۰ روز اخیر (ثبت پاسخ نهایی)
        $trendStart = Carbon::now()->subDays(29)->startOfDay();
        $trendRaw = collect();
        if ($surveyIds->isNotEmpty()) {
            $dateExpr = $this->responseSubmittedDateExpression();
            $trendRaw = SurveyResponse::query()
                ->whereIn('survey_id', $surveyIds)
                ->where('status', 'submitted')
                ->whereNotNull('submitted_at')
                ->where('submitted_at', '>=', $trendStart)
                ->select(DB::raw($dateExpr.' as day'), DB::raw('count(*) as c'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('c', 'day');
        }

        $trendLabels = [];
        $trendData = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $trendLabels[] = Carbon::parse($d)->format('m/d');
            $trendData[] = (int) ($trendRaw[$d] ?? 0);
        }

        // نظرسنجی‌های ایجادشده به تفکیک ماه (۱۲ ماه اخیر)
        $monthsStart = Carbon::now()->startOfMonth()->subMonths(11);
        $monthlyRaw = collect();
        if ($totalSurveys > 0) {
            $monthExpr = $this->surveyCreatedMonthExpression();
            $monthlyRaw = (clone $surveyScope)
                ->where('created_at', '>=', $monthsStart)
                ->select(DB::raw($monthExpr.' as ym'), DB::raw('count(*) as c'))
                ->groupBy('ym')
                ->orderBy('ym')
                ->pluck('c', 'ym');
        }

        $monthlyLabels = [];
        $monthlyData = [];
        for ($m = 0; $m < 12; $m++) {
            $dt = (clone $monthsStart)->addMonths($m);
            $key = $dt->format('Y-m');
            $monthlyLabels[] = $dt->format('Y/m');
            $monthlyData[] = (int) ($monthlyRaw[$key] ?? 0);
        }

        $topSurveys = collect();
        if ($totalSurveys > 0) {
            $topSurveys = (clone $surveyScope)
                ->with(['unit:id,name'])
                ->withCount([
                    'responses as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
                    'responses as draft_count' => fn ($q) => $q->where('status', 'draft'),
                ])
                ->orderByDesc('submitted_count')
                ->limit(10)
                ->get(['id', 'title', 'status', 'unit_id']);
        }

        $org = null;
        if ($admin instanceof AdminUser && $admin->isSupervisor()) {
            $unitIds = $admin->supervisedUnitIds();
            $org = [
                'units' => count($unitIds),
                'personnel' => $unitIds !== []
                    ? Personnel::query()->whereIn('unit_id', $unitIds)->count()
                    : 0,
                'positions' => Position::count(),
            ];
        } else {
            $org = [
                'units' => Unit::count(),
                'personnel' => Personnel::count(),
                'positions' => Position::count(),
            ];
        }

        $chartColors = [
            'primary' => 'rgba(214, 17, 25, 0.85)',
            'primaryLight' => 'rgba(214, 17, 25, 0.2)',
            'slate' => 'rgba(15, 23, 42, 0.75)',
            'green' => 'rgba(22, 163, 74, 0.85)',
            'amber' => 'rgba(217, 119, 6, 0.85)',
            'blue' => 'rgba(37, 99, 235, 0.85)',
            'violet' => 'rgba(124, 58, 237, 0.85)',
            'teal' => 'rgba(13, 148, 136, 0.85)',
            'muted' => 'rgba(107, 114, 128, 0.75)',
        ];

        return view('admin.reports', [
            'admin' => $admin,
            'totalSurveys' => $totalSurveys,
            'byStatus' => $byStatus,
            'surveysActiveFlagOn' => $surveysActiveFlagOn,
            'surveysActiveFlagOff' => $surveysActiveFlagOff,
            'withPublicLink' => $withPublicLink,
            'withoutPublicLink' => $withoutPublicLink,
            'totalSubmitted' => $totalSubmitted,
            'totalDraftResponses' => $totalDraftResponses,
            'sumQuestions' => $sumQuestions,
            'avgQuestions' => $avgQuestions,
            'avgResponsesPerSurvey' => $avgResponsesPerSurvey,
            'completionRate' => $completionRate,
            'unitActivity' => $unitActivity,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
            'monthlyLabels' => $monthlyLabels,
            'monthlyData' => $monthlyData,
            'topSurveys' => $topSurveys,
            'org' => $org,
            'chartColors' => $chartColors,
        ]);
    }

    private function responseSubmittedDateExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => 'date(submitted_at)',
            default => 'DATE(submitted_at)',
        };
    }

    private function surveyCreatedMonthExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m', surveys.created_at)",
            default => 'DATE_FORMAT(surveys.created_at, "%Y-%m")',
        };
    }
}
