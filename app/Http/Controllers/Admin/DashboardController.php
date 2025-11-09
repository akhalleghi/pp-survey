<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $admin = AdminUser::find(session('admin_id'));

        return view('admin.dashboard', compact('admin'));
    }
}
