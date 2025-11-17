<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $admin = AdminUser::find(session('admin_id'));
        $stats = [
            'units' => Unit::count(),
            'positions' => Position::count(),
            'personnel' => Personnel::count(),
        ];

        return view('admin.dashboard', compact('admin', 'stats'));
    }
}
