@extends('admin.layouts.app')

@section('page-title', 'ویرایش پاسخ نظرسنجی')
@section('page-description', 'مدیر می‌تواند پاسخ ثبت‌شده را اصلاح یا بازبینی کند.')

@section('content')
    <style>
        .edit-wrap { display: flex; flex-direction: column; gap: 1rem; }
        .card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem;
        }
        .head { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .muted { color: var(--muted); font-size: .86rem; }
        .q {
            border: 1px dashed rgba(15, 23, 42, 0.16);
            border-radius: 14px;
            padding: .9rem;
            margin-bottom: .8rem;
        }
        .q h4 { margin: 0 0 .4rem; font-size: 1rem; }
        .q .desc { margin: 0 0 .55rem; color: var(--muted); font-size: .84rem; }
        .input {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 12px;
            padding: .62rem .74rem;
            font-family: inherit;
        }
        .option-list { display: flex; flex-wrap: wrap; gap: .45rem .9rem; }
        .option-list label { font-size: .9rem; display: inline-flex; gap: .35rem; align-items: center; }
        .actions { display: flex; gap: .55rem; justify-content: flex-end; flex-wrap: wrap; }
        .btn {
            text-decoration: none;
            border: none;
            border-radius: 12px;
            padding: .6rem .95rem;
            cursor: pointer;
            font-weight: 700;
            font-family: inherit;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
        .btn-ghost { background: rgba(15, 23, 42, 0.08); color: var(--slate); }
    </style>

    <div class="edit-wrap">
        <section class="card head">
            <div>
                <h3 style="margin:0;">ویرایش پاسخ #{{ $response->id }}</h3>
                <div class="muted">
                    پاسخ‌دهنده:
                    {{ $response->respondent_name ?: ($response->personnel ? trim($response->personnel->first_name . ' ' . $response->personnel->last_name) : 'ناشناس') }}
                </div>
            </div>
            <div class="muted">
                ثبت نهایی: {{ $response->submitted_at ? jalali_date($response->submitted_at, 'Y/m/d H:i') : '-' }}
            </div>
        </section>

        <form class="card" method="POST" enctype="multipart/form-data" action="{{ route('admin.surveys.report.responses.update', [$survey, $response]) }}">
            @csrf
            @method('PUT')

            @foreach ($survey->questions as $question)
                <div class="q">
                    <h4>{{ $question->title }}</h4>
                    @if ($question->description)
                        <p class="desc">{{ $question->description }}</p>
                    @endif

                    @if (in_array($question->type, ['short_text', 'email', 'phone', 'url'], true))
                        <input type="text" class="input" name="answers[{{ $question->id }}][value]"
                               value="{{ $existingAnswers[$question->id]['text'] ?? '' }}">
                    @elseif ($question->type === 'long_text')
                        <textarea class="input" rows="3" name="answers[{{ $question->id }}][value]">{{ $existingAnswers[$question->id]['text'] ?? '' }}</textarea>
                    @elseif ($question->type === 'number')
                        <input type="number" class="input" name="answers[{{ $question->id }}][value]"
                               value="{{ $existingAnswers[$question->id]['number'] ?? '' }}">
                    @elseif ($question->type === 'date')
                        <input type="date" class="input" name="answers[{{ $question->id }}][value]"
                               value="{{ $existingAnswers[$question->id]['date'] ?? '' }}">
                    @elseif ($question->type === 'file_upload')
                        @php
                            $existingFilePath = $existingAnswers[$question->id]['file_path'] ?? null;
                            $existingFileName = $existingAnswers[$question->id]['file_name'] ?? null;
                            $cfg = $question->settings ?? [];
                            $allowedExt = collect(explode(',', str_replace('،', ',', (string) ($cfg['allowed_extensions'] ?? ''))))
                                ->map(fn($x) => trim((string) $x))
                                ->filter()
                                ->values()
                                ->all();
                            $maxKb = (int) ($cfg['max_file_size_kb'] ?? 0);
                        @endphp
                        <input type="file" class="input" name="answers[{{ $question->id }}][file]" @if(!empty($allowedExt)) accept="{{ collect($allowedExt)->map(fn($x) => '.' . ltrim($x, '.'))->implode(',') }}" @endif>
                        <input type="hidden" name="answers[{{ $question->id }}][current_file]" value="{{ $existingFilePath }}">
                        <input type="hidden" name="answers[{{ $question->id }}][current_file_name]" value="{{ $existingFileName }}">
                        @if ($existingFilePath && $existingFileName)
                            <div class="muted" style="margin-top:.45rem;">
                                فایل فعلی:
                                <a href="{{ route('admin.surveys.report.responses.files.download', [$survey, $response, $question]) }}">{{ $existingFileName }}</a>
                            </div>
                            <label style="display:inline-flex;gap:.35rem;align-items:center;margin-top:.35rem;">
                                <input type="checkbox" name="answers[{{ $question->id }}][remove_file]" value="1">
                                حذف فایل فعلی
                            </label>
                        @endif
                        <div class="muted" style="margin-top:.35rem;">
                            @if (!empty($allowedExt))
                                پسوند مجاز: {{ implode('، ', $allowedExt) }}
                            @endif
                            @if ($maxKb > 0)
                                <span style="margin-right:.4rem;">حداکثر حجم: {{ number_format($maxKb) }}KB</span>
                            @endif
                        </div>
                    @elseif (in_array($question->type, ['multiple_choice', 'dropdown', 'rating', 'yes_no', 'linear_scale'], true))
                        <div class="option-list">
                            @foreach ($question->options as $option)
                                <label>
                                    <input type="radio" name="answers[{{ $question->id }}][option_id]" value="{{ $option->id }}"
                                           @checked(($existingAnswers[$question->id]['option_id'] ?? null) == $option->id)>
                                    {{ $option->label }}
                                </label>
                            @endforeach
                        </div>
                    @elseif ($question->type === 'checkboxes')
                        <div class="option-list">
                            @foreach ($question->options as $option)
                                <label>
                                    <input type="checkbox" name="answers[{{ $question->id }}][option_ids][]" value="{{ $option->id }}"
                                           @checked(in_array($option->id, $existingAnswers[$question->id]['option_ids'] ?? [], true))>
                                    {{ $option->label }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="actions">
                <a class="btn btn-ghost" href="{{ route('admin.surveys.report', $survey) }}">انصراف</a>
                <button type="submit" class="btn btn-primary">ذخیره تغییرات پاسخ</button>
            </div>
        </form>
    </div>
@endsection
