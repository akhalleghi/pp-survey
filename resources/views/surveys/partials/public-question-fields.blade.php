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

    @if (in_array($question->type, ['short_text', 'phone', 'url'], true))
        <input type="text" class="input" name="answers[{{ $question->id }}][value]" placeholder="{{ $question->type === 'short_text' ? 'حروف فارسی' : 'پاسخ شما' }}"
            value="{{ $existingAnswers[$question->id]['text'] ?? '' }}">
    @elseif ($question->type === 'email')
        <input
            type="email"
            class="input"
            name="answers[{{ $question->id }}][value]"
            placeholder="مثال: name@example.com"
            inputmode="email"
            autocomplete="email"
            dir="ltr"
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
                    <script type="application/json" data-rating-options>@json($ratingOptions->map(fn ($option) => ['id' => (int) $option->id, 'label' => (string) $option->label])->values()->all())</script>
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
            $acceptAttr = !empty($allowedExt)
                ? collect($allowedExt)->map(fn ($x) => '.' . ltrim($x, '.'))->implode(',')
                : null;
        @endphp
        <div class="file-upload-zone" data-file-upload-zone>
            <input
                type="file"
                class="file-upload-input"
                id="file-upload-{{ $question->id }}"
                name="answers[{{ $question->id }}][file]"
                @if($acceptAttr) accept="{{ $acceptAttr }}" @endif
                hidden>
            <label class="file-upload-drop" for="file-upload-{{ $question->id }}">
                <span class="file-upload-icon" aria-hidden="true">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5v1.8A2.2 2.2 0 006.2 20.5h11.6a2.2 2.2 0 002.2-2.2v-1.8" />
                    </svg>
                </span>
                <span class="file-upload-title">فایل را اینجا رها کنید یا برای انتخاب کلیک کنید</span>
                <span class="file-upload-hint">
                    @if (!empty($allowedExt))
                        فرمت‌های مجاز: {{ implode('، ', $allowedExt) }}
                    @endif
                    @if ($maxKb > 0)
                        @if (!empty($allowedExt)) — @endif
                        حداکثر حجم: {{ number_format($maxKb) }} کیلوبایت
                    @endif
                </span>
            </label>
            <div class="file-upload-selected" data-file-selected @if(!$existingFilePath) hidden @endif>
                <div class="file-upload-selected-main">
                    <span class="file-upload-selected-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h7l5 5v11a1 1 0 01-1 1H7a1 1 0 01-1-1V5a1 1 0 011-1z" />
                        </svg>
                    </span>
                    <span class="file-upload-selected-name" data-file-name>{{ $existingFileName ?: 'فایل انتخاب‌شده' }}</span>
                </div>
                <button type="button" class="file-upload-clear" data-file-clear>حذف فایل</button>
            </div>
            <input type="hidden" name="answers[{{ $question->id }}][current_file]" value="{{ $existingFilePath }}">
            <input type="hidden" name="answers[{{ $question->id }}][current_file_name]" value="{{ $existingFileName }}">
        </div>
    @endif
    <div class="error-text" data-error-text hidden>لطفاً این سوال را پاسخ دهید.</div>
</div>
@endif
