@php
    /** @var \App\Models\SurveyQuestion $question */
    $questionIndex = isset($questionIndex) ? (int) $questionIndex : null;
    $existingAnswers = $existingAnswers ?? [];
    $toFaDigits = $toFaDigits ?? static fn ($v) => (string) $v;
    $isStatic = $question->isStaticDisplay();
@endphp
@if ($isStatic)
    <div class="{{ ($questionCssClass ?? 'question wizard-question') }} wizard-static-block" data-question data-display-only="1" data-question-id="{{ $question->id }}" data-required="0" data-type="{{ $question->type }}" role="note" aria-label="متن راهنما">
        @if ($question->type === 'static_text_short')
            <div class="static-text-short">
                <p class="static-text-primary">{{ $question->title }}</p>
                @if ($question->description)
                    <p class="static-text-secondary">{{ $question->description }}</p>
                @endif
            </div>
        @else
            <div class="static-text-long">
                @if (filled($question->title) && $question->title !== 'متن راهنما')
                    <h3 class="static-text-heading">{{ $question->title }}</h3>
                @endif
                <div class="static-text-body">{!! nl2br(e($question->description)) !!}</div>
            </div>
        @endif
    </div>
@else
<div class="{{ $questionCssClass ?? 'question wizard-question' }}" data-question data-question-id="{{ $question->id }}" data-required="{{ $question->is_required ? '1' : '0' }}" data-type="{{ $question->type }}">
    @if ($question->description)
        <p class="q-section-line">{{ $toFaDigits($questionIndex) }} — {{ $question->description }}</p>
        <h3 class="wizard-q-title">
            <span class="q-title-text">{{ $question->title }}</span>@if($question->is_required)<span class="q-required-star" aria-hidden="true">*</span>@endif
        </h3>
    @else
        <h3 class="wizard-q-title wizard-q-title--merged">
            <span class="q-step-num">{{ $toFaDigits($questionIndex) }}</span><span class="q-step-sep"> — </span><span class="q-title-text">{{ $question->title }}</span>@if($question->is_required)<span class="q-required-star" aria-hidden="true">*</span>@endif
        </h3>
    @endif

    @if (in_array($question->type, ['short_text', 'email', 'phone', 'url'], true))
        <input type="text" class="input" name="answers[{{ $question->id }}][value]" placeholder="{{ $question->type === 'short_text' ? 'حروف فارسی' : 'پاسخ شما' }}"
            value="{{ $existingAnswers[$question->id]['text'] ?? '' }}">
    @elseif ($question->type === 'long_text')
        <textarea rows="3" class="input" name="answers[{{ $question->id }}][value]" placeholder="پاسخ شما">{{ $existingAnswers[$question->id]['text'] ?? '' }}</textarea>
    @elseif ($question->type === 'number')
        <input type="number" class="input" name="answers[{{ $question->id }}][value]" placeholder="عدد"
            value="{{ $existingAnswers[$question->id]['number'] ?? '' }}">
    @elseif ($question->type === 'date')
        <input
            type="text"
            class="input jalali-answer-display"
            data-hidden-id="answer-date-{{ $question->id }}"
            value=""
            placeholder="مثلاً 1405/02/08">
        <input
            id="answer-date-{{ $question->id }}"
            type="hidden"
            name="answers[{{ $question->id }}][value]"
            value="{{ $existingAnswers[$question->id]['date'] ?? '' }}">
    @elseif (in_array($question->type, ['multiple_choice', 'checkboxes', 'dropdown', 'rating', 'yes_no', 'linear_scale'], true))
        <div class="option-list">
            @if ($question->type === 'rating' && $question->options->isNotEmpty())
                @php
                    $ratingOptions = $question->options->values();
                    $selectedOptionId = (int) ($existingAnswers[$question->id]['option_id'] ?? 0);
                    $selectedIndex = $ratingOptions->search(fn ($item) => (int) $item->id === $selectedOptionId);
                    if ($selectedIndex === false) {
                        $selectedIndex = 0;
                    }
                @endphp
                <div class="rating-slider-wrap"
                     data-rating-slider
                     data-question-id="{{ $question->id }}"
                     data-option-count="{{ $ratingOptions->count() }}">
                    <div class="rating-current" data-rating-current>
                        {{ $ratingOptions[$selectedIndex]->label }}
                    </div>
                    <input
                        type="range"
                        class="rating-slider"
                        min="0"
                        max="{{ max($ratingOptions->count() - 1, 0) }}"
                        step="1"
                        value="{{ $selectedIndex }}"
                        data-rating-range>
                    <input
                        type="hidden"
                        name="answers[{{ $question->id }}][option_id]"
                        value="{{ $ratingOptions[$selectedIndex]->id }}"
                        data-rating-option-id>
                    <div class="rating-ends">
                        <span title="{{ $ratingOptions->first()?->label }}">{{ $ratingOptions->first()?->label }}</span>
                        <span title="{{ $ratingOptions->last()?->label }}">{{ $ratingOptions->last()?->label }}</span>
                    </div>
                    <script type="application/json" data-rating-options>
                        {!! $ratingOptions->map(fn ($option) => ['id' => (int) $option->id, 'label' => (string) $option->label])->toJson(JSON_UNESCAPED_UNICODE) !!}
                    </script>
                </div>
            @elseif ($question->options->isNotEmpty())
                @foreach ($question->options as $option)
                    <label>
                        @if ($question->type === 'checkboxes')
                            <input type="checkbox" name="answers[{{ $question->id }}][option_ids][]" value="{{ $option->id }}"
                                @checked(in_array($option->id, $existingAnswers[$question->id]['option_ids'] ?? [], true))>
                        @else
                            <input type="radio" name="answers[{{ $question->id }}][option_id]" value="{{ $option->id }}"
                                @checked(($existingAnswers[$question->id]['option_id'] ?? null) == $option->id)>
                        @endif
                        {{ $option->label }}
                    </label>
                @endforeach
            @elseif ($question->type === 'rating')
                @php
                    $minRating = (int) ($question->settings['min_rating'] ?? 1);
                    $maxRating = (int) ($question->settings['max_rating'] ?? 5);
                    if ($minRating < 1) {
                        $minRating = 1;
                    }
                    if ($maxRating < $minRating) {
                        $maxRating = max($minRating, 5);
                    }
                    $savedRating = (int) ($existingAnswers[$question->id]['number'] ?? 0);
                @endphp
                @for ($rate = $minRating; $rate <= $maxRating; $rate++)
                    <label>
                        <input type="radio" name="answers[{{ $question->id }}][value]" value="{{ $rate }}"
                            @checked($savedRating === $rate)>
                        امتیاز {{ $rate }}
                    </label>
                @endfor
            @endif
        </div>
    @elseif ($question->type === 'file_upload')
        @php
            $fileCfg = $question->settings ?? [];
            $allowedExt = collect(explode(',', str_replace('،', ',', (string) ($fileCfg['allowed_extensions'] ?? ''))))
                ->map(fn ($x) => trim((string) $x))
                ->filter()
                ->values()
                ->all();
            $maxKb = (int) ($fileCfg['max_file_size_kb'] ?? 0);
            $existingFilePath = $existingAnswers[$question->id]['file_path'] ?? null;
            $existingFileName = $existingAnswers[$question->id]['file_name'] ?? null;
        @endphp
        <input type="file" class="input" name="answers[{{ $question->id }}][file]" @if(!empty($allowedExt)) accept="{{ collect($allowedExt)->map(fn ($x) => '.' . ltrim($x, '.'))->implode(',') }}" @endif>
        <input type="hidden" name="answers[{{ $question->id }}][current_file]" value="{{ $existingFilePath }}">
        <input type="hidden" name="answers[{{ $question->id }}][current_file_name]" value="{{ $existingFileName }}">
        <div class="q-desc" style="margin-top:.45rem;">
            @if (!empty($allowedExt))
                پسوند مجاز: {{ implode('، ', $allowedExt) }}
            @endif
            @if ($maxKb > 0)
                <span style="margin-right:.4rem;">حداکثر حجم: {{ number_format($maxKb) }}KB</span>
            @endif
        </div>
        @if ($existingFilePath && $existingFileName)
            <div class="q-desc" style="margin-top:.15rem;">
                فایل فعلی: {{ $existingFileName }}
            </div>
        @endif
    @endif
    <div class="error-text" hidden>لطفا این سوال را پاسخ دهید.</div>
</div>
@endif
