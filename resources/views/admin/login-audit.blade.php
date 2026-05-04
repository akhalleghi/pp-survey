@extends('admin.layouts.app')

@section('page-title', 'گزارش ورود')
@section('page-description', 'تاریخچهٔ تلاش‌های ورود به پنل مدیریت با آدرس شبکه و نتیجهٔ هر رویداد.')

@section('content')
    <style>
        .audit-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .audit-hero {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 28px;
            padding: clamp(1.2rem, 4vw, 1.75rem);
        }
        .audit-hero h2 {
            margin: 0 0 0.4rem;
            font-size: clamp(1.1rem, 2.5vw, 1.5rem);
        }
        .audit-hero p {
            margin: 0;
            color: var(--muted);
            line-height: 1.8;
            font-size: 0.92rem;
        }
        .audit-filters {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            padding: 1.25rem clamp(1rem, 3vw, 1.5rem);
        }
        .audit-filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .audit-filters label {
            display: block;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 0.35rem;
            color: var(--slate);
        }
        .audit-filters input,
        .audit-filters select {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.15);
            border-radius: 14px;
            padding: 0.65rem 0.85rem;
            font-family: inherit;
            font-size: 0.9rem;
            background: rgba(15, 23, 42, 0.02);
        }
        .audit-filters .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .audit-filters .btn-apply {
            border: none;
            border-radius: 14px;
            padding: 0.7rem 1.25rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .audit-filters .btn-clear {
            border: 1px dashed rgba(15, 23, 42, 0.25);
            background: transparent;
            border-radius: 14px;
            padding: 0.7rem 1.1rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--slate);
        }
        .audit-table-wrap {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            overflow: hidden;
        }
        .audit-table-wrap table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .audit-table-wrap th,
        .audit-table-wrap td {
            padding: 0.85rem 1rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .audit-table-wrap th {
            background: rgba(15, 23, 42, 0.04);
            font-weight: 700;
            color: var(--slate);
            font-size: 0.82rem;
        }
        .audit-table-wrap tr:last-child td {
            border-bottom: none;
        }
        .audit-table-wrap tbody tr:hover {
            background: rgba(214, 17, 25, 0.04);
        }
        .outcome-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .outcome-pill.ok {
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
        }
        .outcome-pill.fail {
            background: rgba(220, 38, 38, 0.1);
            color: #b91c1c;
        }
        .outcome-pill.neutral {
            background: rgba(100, 116, 139, 0.12);
            color: #475569;
        }
        .mono {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }
        .audit-empty {
            padding: 2.5rem 1.5rem;
            text-align: center;
            color: var(--muted);
        }
        .audit-pagination {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
        }
        .audit-unlock-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            padding: 1.25rem clamp(1rem, 3vw, 1.5rem);
        }
        .audit-unlock-card h3 {
            margin: 0 0 0.5rem;
            font-size: 1.05rem;
        }
        .audit-unlock-card > p {
            margin: 0 0 1rem;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.7;
        }
        .audit-unlock-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .audit-unlock-card th,
        .audit-unlock-card td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            text-align: right;
        }
        .audit-unlock-card .btn-unlock {
            border: none;
            border-radius: 12px;
            padding: 0.45rem 0.85rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.82rem;
            background: rgba(214, 17, 25, 0.1);
            color: var(--primary);
        }
        .audit-unlock-manual {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px dashed rgba(15, 23, 42, 0.12);
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
        }
        .audit-unlock-manual label {
            font-weight: 600;
            font-size: 0.88rem;
            display: block;
            margin-bottom: 0.35rem;
        }
        .audit-unlock-manual input {
            border: 1px solid rgba(15, 23, 42, 0.15);
            border-radius: 14px;
            padding: 0.65rem 0.85rem;
            min-width: 200px;
            font-family: inherit;
        }
        .audit-status-ok {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #15803d;
            border-radius: 18px;
            padding: 0.85rem 1.1rem;
            font-weight: 600;
        }
        @media (max-width: 720px) {
            .audit-table-wrap {
                overflow-x: auto;
            }
            .audit-table-wrap table {
                min-width: 640px;
            }
        }
    </style>

    <div class="audit-page">
        <div class="audit-hero">
            <h2>گزارش رویدادهای ورود</h2>
            <p>هر ردیف یک رویداد است: زمان به‌وقت محلی و تقویم شمسی، نام کاربری تلاش‌شده، نتیجه، آدرس IP و جزئیات.</p>
        </div>

        @if (session('audit_status'))
            <div class="audit-status-ok">{{ session('audit_status') }}</div>
        @endif

        @if ($errors->any())
            <div class="status-message" style="background: rgba(220, 38, 38, 0.08); border-color: rgba(220, 38, 38, 0.2); color: #991b1b;">
                <ul style="margin: 0; padding-right: 1.25rem;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="audit-filters">
            <form method="get" action="{{ route('admin.login-audit.index') }}">
                <div>
                    <label for="f-outcome">نتیجه</label>
                    <select id="f-outcome" name="outcome">
                        <option value="">همه</option>
                        @foreach ($outcomeOptions as $opt)
                            <option value="{{ $opt }}" @selected(request('outcome') === $opt)>
                                {{ \App\Models\AdminLoginLog::outcomeLabel($opt) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="f-user">نام کاربری</label>
                    <input id="f-user" type="text" name="username" value="{{ request('username') }}" placeholder="جستجو…" autocomplete="off">
                </div>
                <div>
                    <label for="f-from">از تاریخ (شمسی)</label>
                    <input id="f-from" type="text" name="from" value="{{ request('from') }}" placeholder="۱۴۰۳/۰۸/۰۱" inputmode="numeric" autocomplete="off" dir="ltr" style="text-align: left;">
                    @error('from')
                        <span style="display:block;color:#b91c1c;font-size:0.78rem;margin-top:0.35rem;">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="f-to">تا تاریخ (شمسی)</label>
                    <input id="f-to" type="text" name="to" value="{{ request('to') }}" placeholder="۱۴۰۳/۰۸/۳۰" inputmode="numeric" autocomplete="off" dir="ltr" style="text-align: left;">
                    @error('to')
                        <span style="display:block;color:#b91c1c;font-size:0.78rem;margin-top:0.35rem;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-apply">اعمال فیلتر</button>
                    <a href="{{ route('admin.login-audit.index') }}" class="btn-clear" style="display:inline-flex;align-items:center;">پاک کردن</a>
                </div>
            </form>
        </div>

        <div class="audit-unlock-card">
            <h3>مسدودی ورود (قفل موقت)</h3>
            <p>پس از عبور از حد مجاز تلاش ناموفق، ورود با همان نام کاربری تا پایان مدت تعیین‌شده در تنظیمات امنیتی مسدود می‌شود. از اینجا می‌توانید دستی مسدودی را قبل از اتمام زمان رفع کنید.</p>

            @if ($activeLocks->isEmpty())
                <p style="margin:0;color:var(--muted);font-size:0.9rem;">در حال حاضر حسابی در وضعیت قفل ورود نیست.</p>
            @else
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>نام کاربری</th>
                                <th>پایان مسدودی (شمسی)</th>
                                <th>تلاش ناموفق</th>
                                <th>آخرین تلاش ناموفق</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeLocks as $lock)
                                <tr>
                                    <td><strong>{{ $lock->username ?? '—' }}</strong></td>
                                    <td class="mono">{{ $lock->locked_until ? \App\Support\PersianCalendar::formatDateTime($lock->locked_until) : '—' }}</td>
                                    <td>{{ $lock->failed_attempts }}</td>
                                    <td class="mono">{{ $lock->last_failed_at ? \App\Support\PersianCalendar::formatDateTime($lock->last_failed_at) : '—' }}</td>
                                    <td>
                                        @if ($lock->username)
                                            <form method="post" action="{{ route('admin.login-audit.clear-lock') }}" style="display:inline;" onsubmit="return confirm('رفع مسدودی برای این نام کاربری؟');">
                                                @csrf
                                                <input type="hidden" name="unlock_username" value="{{ $lock->username }}">
                                                <button type="submit" class="btn-unlock">رفع مسدودی</button>
                                            </form>
                                        @else
                                            <span style="font-size:0.82rem;color:var(--muted);">نام کاربری ذخیره نشده؛ فرم پایین</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="audit-unlock-manual">
                <form method="post" action="{{ route('admin.login-audit.clear-lock') }}" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">
                    @csrf
                    <div>
                        <label for="manual-unlock-user">رفع مسدودی با نام کاربری</label>
                        <input id="manual-unlock-user" type="text" name="unlock_username" value="{{ old('unlock_username') }}" placeholder="نام کاربری دقیق" maxlength="64" autocomplete="off" class="{{ $errors->has('unlock_username') ? 'error' : '' }}" style="border-color: {{ $errors->has('unlock_username') ? 'rgba(220,38,38,0.5)' : '' }};">
                        @error('unlock_username')
                            <span style="display:block;color:#b91c1c;font-size:0.82rem;margin-top:0.35rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="btn-apply" style="padding:0.7rem 1.2rem;">ثبت</button>
                </form>
            </div>
        </div>

        <div class="audit-table-wrap">
            @if ($logs->isEmpty())
                <div class="audit-empty">رکوردی مطابق فیلترها یافت نشد.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>زمان</th>
                            <th>نام کاربری</th>
                            <th>نتیجه</th>
                            <th>IP</th>
                            <th>جزئیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $isOk = $log->outcome === \App\Models\AdminLoginLog::OUTCOME_SUCCESS;
                                $isLock = $log->outcome === \App\Models\AdminLoginLog::OUTCOME_LOCKED;
                                $pillClass = $isOk ? 'ok' : ($isLock ? 'neutral' : 'fail');
                            @endphp
                            <tr>
                                <td class="mono">{{ $log->created_at ? \App\Support\PersianCalendar::formatDateTime($log->created_at) : '—' }}</td>
                                <td>
                                    <strong>{{ $log->username }}</strong>
                                    @if ($log->adminUser)
                                        <span style="color: var(--muted); font-size: 0.8rem;"> (حساب #{{ $log->admin_user_id }})</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="outcome-pill {{ $pillClass }}">
                                        {{ \App\Models\AdminLoginLog::outcomeLabel($log->outcome) }}
                                    </span>
                                </td>
                                <td class="mono">{{ $log->ip_address ?? '—' }}</td>
                                <td style="max-width: 220px; font-size: 0.82rem; color: var(--muted);">
                                    @if ($log->detail)
                                        {{ $log->detail }}
                                    @elseif ($log->user_agent)
                                        <span title="{{ $log->user_agent }}">{{ \Illuminate\Support\Str::limit($log->user_agent, 48) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="audit-pagination">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
