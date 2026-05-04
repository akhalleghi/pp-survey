@extends('admin.layouts.app')

@section('page-title', 'گزارشات جامع نظرسنجی')
@section('page-description', 'شاخص‌ها و نمودارها بر اساس دادهٔ لحظه‌ای سامانه؛ با هر بار باز کردن این صفحه به‌روز می‌شوند.')

@section('content')
    @php
        $c = $chartColors ?? [];
    @endphp
    <style>
        .reports-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .reports-hero {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 28px;
            padding: clamp(1.2rem, 4vw, 2rem);
        }

        .reports-hero h2 {
            margin: 0 0 0.5rem;
            font-size: clamp(1.15rem, 3vw, 1.75rem);
        }

        .reports-hero p {
            margin: 0;
            color: var(--muted);
            line-height: 1.85;
            font-size: 0.95rem;
        }

        .reports-hero .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.75rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .reports-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .reports-kpi-grid .stat-card span {
            line-height: 1.5;
        }

        .reports-section {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            padding: 1.25rem clamp(1rem, 3vw, 1.75rem);
        }

        .reports-section h3 {
            margin: 0 0 0.35rem;
            font-size: 1.1rem;
        }

        .reports-section > .lead {
            margin: 0 0 1.25rem;
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.7;
        }

        .reports-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));
            gap: 1.25rem;
        }

        .report-chart-box {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem 1.1rem;
            background: rgba(15, 23, 42, 0.02);
        }

        .report-chart-box h4 {
            margin: 0 0 0.35rem;
            font-size: 0.95rem;
        }

        .report-chart-box .sub {
            margin: 0 0 0.75rem;
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.5;
        }

        .chart-canvas-wrap {
            position: relative;
            height: 260px;
            width: 100%;
        }

        .chart-canvas-wrap.tall {
            height: 300px;
        }

        .chart-empty {
            margin: 0;
            padding: 2rem 0.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--muted);
            border: 1px dashed rgba(15, 23, 42, 0.12);
            border-radius: 12px;
        }

        .top-surveys-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .top-surveys-table th,
        .top-surveys-table td {
            padding: 0.65rem 0.5rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .top-surveys-table th {
            color: var(--muted);
            font-weight: 600;
            font-size: 0.8rem;
        }

        .top-surveys-table a {
            color: var(--primary);
            font-weight: 600;
        }

        .org-mini {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .org-mini span {
            font-size: 0.88rem;
            color: var(--muted);
        }

        .org-mini strong {
            color: var(--slate);
        }
    </style>

    <div class="reports-page">
        <section class="reports-hero">
            <h2>داشبورد تحلیلی نظرسنجی‌ها</h2>
            <p>
                تمام اعداد و نمودارهای این صفحه <strong>به‌صورت خودکار</strong> از پایگاه داده خوانده می‌شوند؛ ذخیره یا به‌روزرسانی دستی
                ندارند و با هر بار بارگذاری صفحه، آخرین وضعیت را نشان می‌دهند.
            </p>
            @if ($admin && $admin->isSupervisor())
                <p style="margin-top:0.65rem;">
                    <strong>حوزهٔ شما:</strong> فقط نظرسنجی‌هایی که خودتان ایجاد کرده‌اید و داده‌های مرتبط با آن‌ها در گزارش لحاظ شده است.
                </p>
            @endif
            <div class="badge-live">
                <span aria-hidden="true">●</span>
                دادهٔ لحظه‌ای — بدون کش
            </div>
        </section>

        <section class="reports-section">
            <h3>سازمان (زمینه)</h3>
            <p class="lead">فقط برای درک مقیاس؛ شاخص‌های اصلی نظرسنجی در بخش‌های بعدی است.</p>
            <div class="org-mini">
                <span><strong>{{ number_format($org['units'] ?? 0) }}</strong> واحد {{ $admin && $admin->isSupervisor() ? 'تحت سرپرستی' : 'ثبت‌شده' }}</span>
                <span><strong>{{ number_format($org['personnel'] ?? 0) }}</strong> پرسنل</span>
                <span><strong>{{ number_format($org['positions'] ?? 0) }}</strong> سمت تعریف‌شده</span>
            </div>
        </section>

        <div class="stats-grid reports-kpi-grid">
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 7h14v4H5zM5 13h14v4H5zM5 19h9" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($totalSurveys) }}</h3>
                    <span>کل نظرسنجی‌ها (در حوزهٔ گزارش)</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($byStatus['active'] ?? 0) }}</h3>
                    <span>نظرسنجی با وضعیت «فعال»</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($byStatus['closed'] ?? 0) }}</h3>
                    <span>بسته‌شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 6h16M4 10h16M4 14h10M4 18h6" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($totalSubmitted) }}</h3>
                    <span>کل پاسخ‌های ثبت‌نهایی تا اکنون</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($totalDraftResponses) }}</h3>
                    <span>پیش‌نویس پاسخ (هنوز ارسال نشده)</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M7 8h10M7 12h6m-6 4h10M5 5v14h14" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($sumQuestions) }}</h3>
                    <span>جمع سوالات طراحی‌شده (شمارندهٔ ذخیره‌شده)</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($avgResponsesPerSurvey, 1) }}</h3>
                    <span>میانگین پاسخ ثبت‌نهایی به ازای هر نظرسنجی</span>
                </div>
            </div>
            @if ($completionRate !== null)
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h3>{{ $completionRate }}٪</h3>
                        <span>سهم پاسخ نهایی از همه پرونده‌های پاسخ (ثبت + پیش‌نویس)</span>
                    </div>
                </div>
            @endif
        </div>

        <section class="reports-section">
            <h3>نمودارها</h3>
            <p class="lead">
                همه نمودارها از همان فیلتر حوزهٔ گزارش (کل سامانه یا نظرسنجی‌های شما) تغذیه می‌شوند.
            </p>

            @if ($totalSurveys === 0)
                <p class="chart-empty">هنوز نظرسنجی‌ای در این حوزه ثبت نشده؛ پس از ایجاد نظرسنجی، نمودارها پر می‌شوند.</p>
            @else
                <div class="reports-charts">
                    <div class="report-chart-box">
                        <h4>وضعیت نظرسنجی‌ها</h4>
                        <p class="sub">پیش‌نویس، در انتظار تأیید، فعال، بسته — نسبت به هم</p>
                        <div class="chart-canvas-wrap" dir="ltr">
                            <canvas id="chartStatus" aria-label="نمودار وضعیت"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-box">
                        <h4>پرچم فعال بودن لینک</h4>
                        <p class="sub">تنظیم «فعال بودن» در پنل (مستقل از وضعیت چرخهٔ انتشار)</p>
                        <div class="chart-canvas-wrap" dir="ltr">
                            <canvas id="chartActiveFlag" aria-label="نمودار فعال بودن"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-box">
                        <h4>لینک عمومی</h4>
                        <p class="sub">نظرسنجی‌هایی که توکن لینک عمومی دارند در برابر بدون لینک</p>
                        <div class="chart-canvas-wrap" dir="ltr">
                            <canvas id="chartPublicLink" aria-label="نمودار لینک عمومی"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-box">
                        <h4>فعال‌ترین واحدها (بر اساس پاسخ ثبت‌شده)</h4>
                        <p class="sub">سهم واحدها از حجم پاسخ‌های نهایی — تا ۱۲ واحد برتر</p>
                        @if ($unitActivity->isEmpty())
                            <p class="chart-empty">هنوز پاسخ ثبت‌شده‌ای برای نمودار واحد وجود ندارد.</p>
                        @else
                            <div class="chart-canvas-wrap tall" dir="ltr">
                                <canvas id="chartUnits" aria-label="نمودار واحدها"></canvas>
                            </div>
                        @endif
                    </div>
                    <div class="report-chart-box">
                        <h4>روند پاسخ در ۳۰ روز اخیر</h4>
                        <p class="sub">تعداد پاسخ‌های ثبت‌نهایی به تفکیک روز</p>
                        <div class="chart-canvas-wrap" dir="ltr">
                            <canvas id="chartTrend" aria-label="روند روزانه"></canvas>
                        </div>
                    </div>
                    <div class="report-chart-box">
                        <h4>ایجاد نظرسنجی در ۱۲ ماه اخیر</h4>
                        <p class="sub">تعداد نظرسنجی ثبت‌شده در هر ماه</p>
                        <div class="chart-canvas-wrap" dir="ltr">
                            <canvas id="chartMonthly" aria-label="نمودار ماهانه"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        @if ($totalSurveys > 0 && $topSurveys->isNotEmpty())
            <section class="reports-section">
                <h3>پرپاسخ‌ترین نظرسنجی‌ها</h3>
                <p class="lead">مرتب‌شده بر اساس تعداد پاسخ ثبت‌نهایی.</p>
                <div style="overflow-x:auto;">
                    <table class="top-surveys-table">
                        <thead>
                            <tr>
                                <th>نظرسنجی</th>
                                <th>واحد</th>
                                <th>وضعیت</th>
                                <th>پاسخ نهایی</th>
                                <th>پیش‌نویس</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topSurveys as $s)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.surveys.report', $s) }}">{{ $s->title }}</a>
                                    </td>
                                    <td>{{ $s->unit?->name ?? '—' }}</td>
                                    <td>
                                        @php
                                            $labels = [
                                                'draft' => 'آماده‌سازی',
                                                'pending_approval' => 'انتظار تأیید',
                                                'active' => 'فعال',
                                                'closed' => 'بسته',
                                            ];
                                        @endphp
                                        {{ $labels[$s->status] ?? $s->status }}
                                    </td>
                                    <td>{{ number_format($s->submitted_count) }}</td>
                                    <td>{{ number_format($s->draft_count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    @if ($totalSurveys > 0)
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
        <script>
            (function () {
                var C = @json($c);
                var byStatus = @json($byStatus);
                var trendLabels = @json($trendLabels);
                var trendData = @json($trendData);
                var monthlyLabels = @json($monthlyLabels);
                var monthlyData = @json($monthlyData);
                var unitNames = @json($unitActivity->pluck('name'));
                var unitCounts = @json($unitActivity->pluck('response_count'));
                var onFlag = {{ (int) $surveysActiveFlagOn }};
                var offFlag = {{ (int) $surveysActiveFlagOff }};
                var withLink = {{ (int) $withPublicLink }};
                var withoutLink = {{ (int) $withoutPublicLink }};

                function pie(id, labels, data, colors) {
                    var el = document.getElementById(id);
                    if (!el || typeof Chart === 'undefined') return;
                    new Chart(el.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: colors,
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', rtl: true },
                                tooltip: { rtl: true }
                            }
                        }
                    });
                }

                function init() {
                    pie('chartStatus',
                        ['آماده‌سازی', 'انتظار تأیید مدیر', 'فعال', 'بسته'],
                        [byStatus.draft, byStatus.pending_approval, byStatus.active, byStatus.closed],
                        ['rgba(107,114,128,0.85)', 'rgba(217,119,6,0.9)', 'rgba(22,163,74,0.9)', 'rgba(15,23,42,0.65)']
                    );
                    pie('chartActiveFlag',
                        ['پرچم فعال', 'پرچم غیرفعال'],
                        [onFlag, offFlag],
                        ['rgba(22,163,74,0.9)', 'rgba(148,163,184,0.85)']
                    );
                    pie('chartPublicLink',
                        ['دارای لینک عمومی', 'بدون لینک عمومی'],
                        [withLink, withoutLink],
                        ['rgba(37,99,235,0.88)', 'rgba(203,213,225,0.9)']
                    );

                    if (unitNames.length && document.getElementById('chartUnits')) {
                        new Chart(document.getElementById('chartUnits').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: unitNames,
                                datasets: [{
                                    label: 'پاسخ ثبت‌شده',
                                    data: unitCounts,
                                    backgroundColor: 'rgba(214,17,25,0.75)',
                                    borderColor: 'rgba(214,17,25,0.95)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { rtl: true }
                                },
                                scales: {
                                    x: { beginAtZero: true, ticks: { precision: 0 } },
                                    y: { ticks: { font: { size: 11 } } }
                                }
                            }
                        });
                    }

                    var tEl = document.getElementById('chartTrend');
                    if (tEl) {
                        new Chart(tEl.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: trendLabels,
                                datasets: [{
                                    label: 'پاسخ نهایی',
                                    data: trendData,
                                    borderColor: C.primary || 'rgba(214,17,25,0.95)',
                                    backgroundColor: C.primaryLight || 'rgba(214,17,25,0.12)',
                                    fill: true,
                                    tension: 0.25,
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { rtl: true }
                                },
                                scales: {
                                    x: { ticks: { maxRotation: 45, font: { size: 9 } } },
                                    y: { beginAtZero: true, ticks: { precision: 0 } }
                                }
                            }
                        });
                    }

                    var mEl = document.getElementById('chartMonthly');
                    if (mEl) {
                        new Chart(mEl.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: monthlyLabels,
                                datasets: [{
                                    label: 'تعداد نظرسنجی جدید',
                                    data: monthlyData,
                                    backgroundColor: 'rgba(124,58,237,0.55)',
                                    borderColor: 'rgba(124,58,237,0.95)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { rtl: true }
                                },
                                scales: {
                                    x: { ticks: { maxRotation: 45, font: { size: 10 } } },
                                    y: { beginAtZero: true, ticks: { precision: 0 } }
                                }
                            }
                        });
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    @endif
@endsection
