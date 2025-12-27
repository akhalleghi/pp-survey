<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\PersonnelController;
use App\Http\Controllers\Admin\UnitSupervisorController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\SurveyQuestionController;
use App\Http\Controllers\PublicSurveyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('surveys/public/{token}', [PublicSurveyController::class, 'show'])->name('surveys.public.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/captcha-refresh', [AuthController::class, 'refreshCaptcha'])->name('captcha.refresh');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::resource('units', UnitController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('positions', PositionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('personnel', PersonnelController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('personnel/bulk-import', [PersonnelController::class, 'bulkImport'])->name('personnel.bulk-import');
        Route::get('personnel/template/download', [PersonnelController::class, 'downloadTemplate'])->name('personnel.template');
        Route::resource('unit-supervisors', UnitSupervisorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('surveys/{survey}/settings', [SurveyController::class, 'edit'])->name('surveys.edit');
        Route::resource('surveys', SurveyController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('surveys/{survey}/generate-link', [SurveyController::class, 'generateLink'])->name('surveys.generate-link');
        Route::get('surveys/{survey}/questions', [SurveyQuestionController::class, 'index'])->name('surveys.questions.index');
        Route::post('surveys/{survey}/questions', [SurveyQuestionController::class, 'store'])->name('surveys.questions.store');
        Route::delete('surveys/{survey}/questions/{question}', [SurveyQuestionController::class, 'destroy'])->name('surveys.questions.destroy');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
        Route::post('settings/colors', [SettingsController::class, 'updateColors'])->name('settings.colors');
    });
});
