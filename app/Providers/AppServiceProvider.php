<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Support\AdminInboxNotifications;
use App\Support\AdminPermissions;
use App\Support\AppFonts;
use App\Support\AppSettings;
use App\Support\AppTextScale;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('appSettings', AppSettings::all());
        View::share('appFont', AppFonts::resolve());
        View::share('appFontStack', AppFonts::stack());
        View::share('appTextScale', AppTextScale::resolve());

        View::composer('admin.layouts.app', function ($view) {
            $admin = request()->attributes->get('current_admin');
            if (! $admin instanceof AdminUser) {
                $admin = null;
            }
            $view->with('admin', $admin);
            $view->with('navItems', AdminPermissions::navigationFor($admin));
            $headerNotifications = AdminInboxNotifications::collect($admin);
            $view->with('headerNotifications', $headerNotifications);
            $view->with('headerNotificationCount', count($headerNotifications));

            if ($admin && $admin->hasPermission(\App\Support\AdminPermissions::SETTINGS)) {
                $settingsTab = session('settings_active_tab', 'password');
                if ($settingsTab === 'typography') {
                    $settingsTab = 'appearance';
                }
                $view->with('settingsActiveTab', $settingsTab);
                $view->with('openSettingsModal', (bool) session('open_settings_modal', false));
            }
        });
    }
}
