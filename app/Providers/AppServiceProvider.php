<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Support\AppSettings;

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

        View::composer('admin.layouts.app', function ($view) {
            $admin = request()->attributes->get('current_admin');
            if (!$admin instanceof AdminUser) {
                $admin = null;
            }
            $view->with('admin', $admin);
            $view->with('navItems', AdminPermissions::navigationFor($admin));
        });
    }
}
