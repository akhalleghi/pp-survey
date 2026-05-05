<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginAuditController;
use App\Http\Controllers\Admin\PersonnelController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\SurveyQuestionController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitSupervisorController;
use App\Http\Controllers\PublicSurveyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('surveys/public/{token}', [PublicSurveyController::class, 'show'])->name('surveys.public.show');
Route::post('surveys/public/{token}/draft', [PublicSurveyController::class, 'saveDraft'])->name('surveys.public.draft');
Route::post('surveys/public/{token}/submit', [PublicSurveyController::class, 'submit'])->name('surveys.public.submit');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/captcha-refresh', [AuthController::class, 'refreshCaptcha'])->name('captcha.refresh');

    Route::middleware(['admin.auth', 'admin.session_idle'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('admin.permission:org.units')->group(function () {
            Route::resource('units', UnitController::class)->only(['index', 'store', 'update', 'destroy']);
        });

        Route::middleware('admin.permission:org.positions')->group(function () {
            Route::resource('positions', PositionController::class)->only(['index', 'store', 'update', 'destroy']);
        });

        Route::middleware('admin.permission:org.personnel')->group(function () {
            Route::resource('personnel', PersonnelController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('personnel/bulk-import', [PersonnelController::class, 'bulkImport'])->name('personnel.bulk-import');
            Route::get('personnel/template/download', [PersonnelController::class, 'downloadTemplate'])->name('personnel.template');
        });

        Route::middleware('admin.permission:org.supervisors')->group(function () {
            Route::resource('unit-supervisors', UnitSupervisorController::class)->only(['index', 'store', 'update', 'destroy']);
        });

        Route::middleware('admin.permission:settings')->group(function () {
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
            Route::post('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
            Route::post('settings/colors', [SettingsController::class, 'updateColors'])->name('settings.colors');
            Route::post('settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
            Route::get('login-audit', [LoginAuditController::class, 'index'])->name('login-audit.index');
            Route::post('login-audit/clear-lock', [LoginAuditController::class, 'clearLock'])->name('login-audit.clear-lock');
        });

        Route::middleware('admin.permission:reports')->group(function () {
            Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        });

        Route::middleware('admin.permission:surveys')->group(function () {
            Route::get('surveys/{survey}/settings', [SurveyController::class, 'edit'])->name('surveys.edit');
            Route::resource('surveys', SurveyController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('surveys/{survey}/generate-link', [SurveyController::class, 'generateLink'])->name('surveys.generate-link');
            Route::post('surveys/{survey}/approve-publish', [SurveyController::class, 'approvePublish'])->name('surveys.approve-publish');
            Route::post('surveys/{survey}/reject-publish', [SurveyController::class, 'rejectPublish'])->name('surveys.reject-publish');
            Route::get('surveys/{survey}/report', [SurveyController::class, 'report'])->name('surveys.report');
            Route::get('surveys/{survey}/report/export/excel', [SurveyController::class, 'exportReportExcel'])->name('surveys.report.export.excel');
            Route::get('surveys/{survey}/report/{response}/edit', [SurveyController::class, 'editResponse'])->name('surveys.report.responses.edit');
            Route::put('surveys/{survey}/report/{response}', [SurveyController::class, 'updateResponse'])->name('surveys.report.responses.update');
            Route::delete('surveys/{survey}/report/{response}', [SurveyController::class, 'destroyResponse'])->name('surveys.report.responses.destroy');
            Route::get('surveys/{survey}/report/{response}/files/{question}/download', [SurveyController::class, 'downloadResponseFile'])->name('surveys.report.responses.files.download');
            Route::get('surveys/{survey}/questions', [SurveyQuestionController::class, 'index'])->name('surveys.questions.index');
            Route::post('surveys/{survey}/questions', [SurveyQuestionController::class, 'store'])->name('surveys.questions.store');
            Route::get('surveys/{survey}/questions/{question}/edit', [SurveyQuestionController::class, 'edit'])->name('surveys.questions.edit');
            Route::put('surveys/{survey}/questions/{question}', [SurveyQuestionController::class, 'update'])->name('surveys.questions.update');
            Route::delete('surveys/{survey}/questions/{question}', [SurveyQuestionController::class, 'destroy'])->name('surveys.questions.destroy');
        });
    });
});
