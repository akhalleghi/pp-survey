<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Services\AdminLoginSecurityService;
use App\Support\PersianCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoginAuditController extends Controller
{
    public function index(Request $request): View
    {
        AdminLoginSecurityService::pruneOldLogsIfNeeded();

        $outcomeOptions = [
            AdminLoginLog::OUTCOME_SUCCESS,
            AdminLoginLog::OUTCOME_FAILED_CAPTCHA,
            AdminLoginLog::OUTCOME_FAILED_PASSWORD,
            AdminLoginLog::OUTCOME_FAILED_INACTIVE,
            AdminLoginLog::OUTCOME_FAILED_NO_ACCESS,
            AdminLoginLog::OUTCOME_USER_NOT_FOUND,
            AdminLoginLog::OUTCOME_LOCKED,
        ];

        $validator = Validator::make($request->all(), [
            'outcome' => ['nullable', 'string', Rule::in($outcomeOptions)],
            'username' => ['nullable', 'string', 'max:64'],
            'from' => ['nullable', 'string', 'max:32'],
            'to' => ['nullable', 'string', 'max:32'],
        ]);

        $validator->after(function ($v) use ($request): void {
            if ($request->filled('from') && PersianCalendar::parseDateStart($request->input('from')) === null) {
                $v->errors()->add('from', 'تاریخ «از» معتبر نیست. مثال: ۱۴۰۳/۰۸/۱۵');
            }
            if ($request->filled('to') && PersianCalendar::parseDateEnd($request->input('to')) === null) {
                $v->errors()->add('to', 'تاریخ «تا» معتبر نیست. مثال: ۱۴۰۳/۰۸/۲۰');
            }

            $fromC = PersianCalendar::parseDateStart($request->input('from'));
            $toC = PersianCalendar::parseDateEnd($request->input('to'));
            if ($fromC && $toC && $fromC->greaterThan($toC)) {
                $v->errors()->add('to', 'تاریخ پایان باید در همان روز یا بعد از تاریخ شروع باشد.');
            }
        });

        $validator->validate();

        $query = AdminLoginLog::query()
            ->with('adminUser:id,username')
            ->orderByDesc('created_at');

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->string('outcome')->toString());
        }

        if ($request->filled('username')) {
            $term = '%'.$request->string('username')->trim()->toString().'%';
            $query->where('username', 'like', $term);
        }

        $fromCarbon = PersianCalendar::parseDateStart($request->input('from'));
        if ($fromCarbon) {
            $query->where('created_at', '>=', $fromCarbon);
        }

        $toCarbon = PersianCalendar::parseDateEnd($request->input('to'));
        if ($toCarbon) {
            $query->where('created_at', '<=', $toCarbon);
        }

        $logs = $query->paginate(35)->withQueryString();
        $activeLocks = AdminLoginSecurityService::currentlyLockedStates();

        return view('admin.login-audit', compact('logs', 'outcomeOptions', 'activeLocks'));
    }

    public function clearLock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'unlock_username' => ['required', 'string', 'max:64'],
        ], [
            'unlock_username.required' => 'نام کاربری را وارد کنید.',
        ]);

        $username = trim($validated['unlock_username']);
        AdminLoginSecurityService::clearThrottle($username);

        return redirect()
            ->route('admin.login-audit.index')
            ->with('audit_status', 'مسدودی ورود برای «'.$username.'» رفع شد.');
    }
}
