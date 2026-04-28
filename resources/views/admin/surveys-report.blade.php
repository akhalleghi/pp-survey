@extends('admin.layouts.app')

@section('page-title', 'گزارش نظرسنجی')
@section('page-description', 'مشاهده پاسخ‌های ثبت‌شده کاربران برای این نظرسنجی.')

@section('content')
    <style>
        .report-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .report-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 20px;
            padding: 1rem 1.1rem;
        }
        .report-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .report-head h2 {
            margin: 0 0 0.25rem;
            font-size: 1.2rem;
        }
        .report-head p {
            margin: 0;
            color: var(--muted);
        }
        .report-actions {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-wrap: wrap;
        }
        .report-actions a {
            text-decoration: none;
            border-radius: 12px;
            padding: 0.55rem 0.9rem;
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        .report-actions a.excel {
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
            border: 1px solid rgba(22, 163, 74, 0.24);
        }
        .table-wrap {
            overflow-x: auto;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
            table-layout: fixed;
        }
        .report-table th,
        .report-table td {
            padding: 0.62rem 0.55rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            vertical-align: top;
            font-size: 0.82rem;
        }
        .report-table thead th {
            color: var(--muted);
            background: rgba(15, 23, 42, 0.03);
            font-weight: 700;
        }
        .report-table .col-id { width: 58px; }
        .report-table .col-user { width: 160px; }
        .report-table .col-identity { width: 150px; }
        .report-table .col-org { width: 145px; }
        .report-table .col-time { width: 125px; }
        .report-table .col-answers { width: auto; }
        .report-table .col-actions { width: 130px; }
        .response-meta {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }
        .response-name {
            font-size: 0.84rem;
            font-weight: 700;
        }
        .muted-sm {
            color: var(--muted);
            font-size: 0.75rem;
            line-height: 1.45;
        }
        .answers-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }
        .answer-item {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 999px;
            padding: 0.25rem 0.5rem;
            background: rgba(15, 23, 42, 0.02);
            line-height: 1.3;
            max-width: 100%;
        }
        .answer-item strong {
            display: inline;
            margin: 0;
            font-size: 0.73rem;
            color: var(--slate);
        }
        .answer-item span {
            font-size: 0.73rem;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
            display: inline-block;
            vertical-align: bottom;
        }
        .mobile-responses {
            display: none;
            gap: 0.8rem;
        }
        .mobile-response-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 14px;
            padding: 0.7rem;
            background: rgba(15, 23, 42, 0.015);
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }
        .mobile-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.4rem;
        }
        .mobile-grid .cell {
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.04);
            padding: 0.42rem 0.48rem;
            line-height: 1.4;
        }
        .mobile-grid .cell .label {
            display: block;
            font-size: 0.7rem;
            color: var(--muted);
        }
        .mobile-grid .cell .value {
            display: block;
            font-size: 0.76rem;
            color: var(--slate);
            font-weight: 600;
        }
        .row-actions {
            display: flex;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        .row-actions .btn {
            text-decoration: none;
            border: none;
            border-radius: 10px;
            padding: 0.36rem 0.5rem;
            font-size: 0.74rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            line-height: 1.3;
        }
        .row-actions .btn-edit {
            background: rgba(15, 23, 42, 0.09);
            color: var(--slate);
        }
        .row-actions .btn-delete {
            background: rgba(220, 38, 38, 0.14);
            color: #b91c1c;
        }
        @media (max-width: 900px) {
            .table-wrap {
                display: none;
            }
            .mobile-responses {
                display: grid;
            }
        }
        .empty-state {
            text-align: center;
            color: var(--muted);
            padding: 2rem 1rem;
            border: 1px dashed rgba(15, 23, 42, 0.2);
            border-radius: 16px;
            background: #fff;
        }
    </style>

    <div class="report-wrapper">
        <section class="report-card report-head">
            <div>
                <h2>گزارش نظرسنجی: {{ $survey->title }}</h2>
                <p>
                    تعداد پاسخ‌های ثبت‌شده:
                    <strong>{{ number_format($responses->total()) }}</strong>
                    @if ($survey->unit)
                        <span style="margin-right: 0.6rem;">| واحد: {{ $survey->unit->name }}</span>
                    @endif
                </p>
            </div>
            <div class="report-actions">
                <a class="excel" href="{{ route('admin.surveys.report.export.excel', $survey) }}">خروجی اکسل</a>
                <a href="{{ route('admin.surveys.index') }}">بازگشت به لیست</a>
            </div>
        </section>
        @if (session('status'))
            <section class="report-card" style="border-color: rgba(22, 163, 74, .25); background: rgba(22, 163, 74, .06);">
                <strong style="color:#166534;">{{ session('status') }}</strong>
            </section>
        @endif

        @if ($responses->count())
            <section class="report-card table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-user">پاسخ‌دهنده</th>
                            <th class="col-identity">کد پرسنلی / کد ملی</th>
                            <th class="col-org">واحد / سمت</th>
                            <th class="col-time">زمان ثبت</th>
                            <th class="col-answers">پاسخ‌ها</th>
                            <th class="col-actions">اقدامات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($responses as $response)
                            <tr>
                                <td>#{{ $response->id }}</td>
                                <td>
                                    <div class="response-meta">
                                        <strong class="response-name">{{ $response->respondent_name ?: ($response->personnel ? trim($response->personnel->first_name . ' ' . $response->personnel->last_name) : 'ناشناس') }}</strong>
                                        <span class="muted-sm">
                                            وضعیت: {{ $response->status === 'submitted' ? 'ثبت نهایی' : 'پیش‌نویس' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if ($response->personnel)
                                        <div>{{ $response->personnel->personnel_code }}</div>
                                        <div class="muted-sm">{{ $response->personnel->national_code }}</div>
                                    @else
                                        {{ $response->respondent_identifier ?: '-' }}
                                    @endif
                                </td>
                                <td>
                                    @if ($response->personnel)
                                        <div>{{ $response->personnel->unit?->name ?? '-' }}</div>
                                        <div class="muted-sm">{{ $response->personnel->position?->name ?? '-' }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $response->submitted_at ? jalali_date($response->submitted_at, 'Y/m/d H:i') : '-' }}</td>
                                <td>
                                    <div class="answers-list">
                                        @forelse ($response->answers as $answer)
                                            @php
                                                $value = '-';
                                                if ($answer->option) {
                                                    $value = $answer->option->label;
                                                } elseif (!empty($answer->answer_json['option_ids'])) {
                                                    $optionLabels = collect($answer->question?->options ?? [])
                                                        ->whereIn('id', $answer->answer_json['option_ids'])
                                                        ->pluck('label')
                                                        ->values()
                                                        ->all();
                                                    $value = !empty($optionLabels)
                                                        ? implode('، ', $optionLabels)
                                                        : 'چندگزینه‌ای';
                                                } elseif (
                                                    in_array($answer->question?->type, ['multiple_choice', 'dropdown', 'checkboxes'], true) &&
                                                    !is_null($answer->answer_number) &&
                                                    $answer->question?->options?->isNotEmpty()
                                                ) {
                                                    $rawNumber = (int) $answer->answer_number;
                                                    $byId = $answer->question->options->firstWhere('id', $rawNumber);
                                                    if ($byId) {
                                                        $value = $byId->label;
                                                    } else {
                                                        $byPosition = $answer->question->options->firstWhere('position', $rawNumber);
                                                        $value = $byPosition?->label ?? (string) $rawNumber;
                                                    }
                                                } elseif ($answer->question?->type === 'date') {
                                                    if (filled($answer->answer_date) || filled($answer->answer_text)) {
                                                        $value = jalali_date($answer->answer_date ?: $answer->answer_text, 'Y/m/d');
                                                    }
                                                } elseif (filled($answer->answer_text)) {
                                                    $value = preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}/', trim((string) $answer->answer_text))
                                                        ? jalali_date($answer->answer_text, 'Y/m/d')
                                                        : $answer->answer_text;
                                                } elseif (!is_null($answer->answer_number)) {
                                                    if ($answer->question?->type === 'rating') {
                                                        $value = 'امتیاز ' . $answer->answer_number . ' از 5';
                                                    } else {
                                                        $value = (string) $answer->answer_number;
                                                    }
                                                }
                                            @endphp
                                            <div class="answer-item">
                                                <strong>{{ $answer->question?->title ?? 'سوال' }}:</strong>
                                                <span title="{{ $value }}">{{ $value }}</span>
                                            </div>
                                        @empty
                                            <span class="muted-sm">پاسخی ثبت نشده است.</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-edit" href="{{ route('admin.surveys.report.responses.edit', [$survey, $response]) }}">
                                            ویرایش
                                        </a>
                                        <form method="POST" action="{{ route('admin.surveys.report.responses.destroy', [$survey, $response]) }}"
                                              onsubmit="return confirm('از حذف این پاسخ مطمئن هستید؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-delete">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="mobile-responses">
                @foreach ($responses as $response)
                    <article class="mobile-response-card">
                        <div class="mobile-top">
                            <div>
                                <strong class="response-name">{{ $response->respondent_name ?: ($response->personnel ? trim($response->personnel->first_name . ' ' . $response->personnel->last_name) : 'ناشناس') }}</strong>
                                <div class="muted-sm">#{{ $response->id }} | {{ $response->status === 'submitted' ? 'ثبت نهایی' : 'پیش‌نویس' }}</div>
                            </div>
                            <div class="muted-sm">{{ $response->submitted_at ? jalali_date($response->submitted_at, 'Y/m/d H:i') : '-' }}</div>
                        </div>
                        <div class="mobile-grid">
                            <div class="cell">
                                <span class="label">پرسنلی / ملی</span>
                                <span class="value">
                                    @if ($response->personnel)
                                        {{ $response->personnel->personnel_code }} / {{ $response->personnel->national_code }}
                                    @else
                                        {{ $response->respondent_identifier ?: '-' }}
                                    @endif
                                </span>
                            </div>
                            <div class="cell">
                                <span class="label">واحد / سمت</span>
                                <span class="value">
                                    @if ($response->personnel)
                                        {{ $response->personnel->unit?->name ?? '-' }} / {{ $response->personnel->position?->name ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="answers-list">
                            @forelse ($response->answers as $answer)
                                @php
                                    $value = '-';
                                    if ($answer->option) {
                                        $value = $answer->option->label;
                                    } elseif (!empty($answer->answer_json['option_ids'])) {
                                        $optionLabels = collect($answer->question?->options ?? [])
                                            ->whereIn('id', $answer->answer_json['option_ids'])
                                            ->pluck('label')
                                            ->values()
                                            ->all();
                                        $value = !empty($optionLabels)
                                            ? implode('، ', $optionLabels)
                                            : 'چندگزینه‌ای';
                                    } elseif (
                                        in_array($answer->question?->type, ['multiple_choice', 'dropdown', 'checkboxes'], true) &&
                                        !is_null($answer->answer_number) &&
                                        $answer->question?->options?->isNotEmpty()
                                    ) {
                                        $rawNumber = (int) $answer->answer_number;
                                        $byId = $answer->question->options->firstWhere('id', $rawNumber);
                                        if ($byId) {
                                            $value = $byId->label;
                                        } else {
                                            $byPosition = $answer->question->options->firstWhere('position', $rawNumber);
                                            $value = $byPosition?->label ?? (string) $rawNumber;
                                        }
                                    } elseif ($answer->question?->type === 'date') {
                                        if (filled($answer->answer_date) || filled($answer->answer_text)) {
                                            $value = jalali_date($answer->answer_date ?: $answer->answer_text, 'Y/m/d');
                                        }
                                    } elseif (filled($answer->answer_text)) {
                                        $value = preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}/', trim((string) $answer->answer_text))
                                            ? jalali_date($answer->answer_text, 'Y/m/d')
                                            : $answer->answer_text;
                                    } elseif (!is_null($answer->answer_number)) {
                                        if ($answer->question?->type === 'rating') {
                                            $value = 'امتیاز ' . $answer->answer_number . ' از 5';
                                        } else {
                                            $value = (string) $answer->answer_number;
                                        }
                                    }
                                @endphp
                                <div class="answer-item">
                                    <strong>{{ $answer->question?->title ?? 'سوال' }}:</strong>
                                    <span title="{{ $value }}">{{ $value }}</span>
                                </div>
                            @empty
                                <span class="muted-sm">پاسخی ثبت نشده است.</span>
                            @endforelse
                        </div>
                        <div class="row-actions">
                            <a class="btn btn-edit" href="{{ route('admin.surveys.report.responses.edit', [$survey, $response]) }}">
                                ویرایش پاسخ
                            </a>
                            <form method="POST" action="{{ route('admin.surveys.report.responses.destroy', [$survey, $response]) }}"
                                  onsubmit="return confirm('از حذف این پاسخ مطمئن هستید؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-delete">حذف پاسخ</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </section>

            <div>{{ $responses->links() }}</div>
        @else
            <div class="empty-state">برای این نظرسنجی هنوز پاسخ نهایی ثبت نشده است.</div>
        @endif
    </div>
@endsection

