@extends('admin.layouts.app')

@section('page-title', 'ویرایش سوال نظرسنجی')
@section('page-description', 'ویرایش این سوال فقط قبل از ثبت اولین پاسخ امکان‌پذیر است.')

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker-default.css') }}">
    <style>
        .edit-wrap { max-width: 980px; margin: 0 auto; }
        .card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem;
        }
        .head { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .type-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; }
        .type-btn {
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 12px;
            padding: .55rem .7rem;
            background: rgba(15, 23, 42, 0.03);
            text-align: center;
            cursor: pointer;
            font-size: .86rem;
        }
        .type-btn.active { border-color: rgba(214,17,25,.45); background: rgba(214,17,25,.11); color: var(--primary); font-weight: 700; }
        .form-grid { display: grid; gap: .75rem; margin-top: .9rem; }
        .field { display: flex; flex-direction: column; gap: .35rem; }
        .field input, .field textarea { border: 1px solid rgba(15,23,42,.15); border-radius: 12px; padding: .7rem .8rem; font-family: inherit; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .55rem; }
        .option-list { display: flex; flex-direction: column; gap: .45rem; }
        .option-row { display: grid; grid-template-columns: minmax(0,1fr) 130px auto; gap: .5rem; }
        .btn {
            border: none; border-radius: 12px; padding: .62rem .95rem; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; font-family: inherit;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
        .btn-ghost { background: rgba(15,23,42,.08); color: var(--slate); }
        .actions { margin-top: .9rem; display: flex; justify-content: flex-end; gap: .55rem; flex-wrap: wrap; }
        @media (max-width: 760px) {
            .type-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .option-row { grid-template-columns: 1fr; }
        }
    </style>

    @php
        $selectedType = old('type', $question->type);
        $options = old('options', $question->options->map(fn($option) => ['label' => $option->label, 'value' => $option->value])->all());
        if (empty($options)) {
            $options = [['label' => '', 'value' => ''], ['label' => '', 'value' => '']];
        }
        $settings = old('settings', $question->settings ?? []);
    @endphp

    <div class="edit-wrap">
        <div class="card">
            <div class="head">
                <div>
                    <strong>ویرایش سوال: {{ $question->title }}</strong>
                    <div style="color:var(--muted); font-size:.85rem;">نظرسنجی: {{ $survey->title }}</div>
                </div>
                <a class="btn btn-ghost" href="{{ route('admin.surveys.questions.index', $survey) }}">بازگشت</a>
            </div>

            <form method="POST" action="{{ route('admin.surveys.questions.update', [$survey, $question]) }}" id="questionEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="type" id="questionTypeInput" value="{{ $selectedType }}">

                <div class="type-grid" id="questionTypeGrid">
                    @foreach ($questionTypes as $key => $type)
                        <button type="button" class="type-btn {{ $selectedType === $key ? 'active' : '' }}" data-type="{{ $key }}" data-has-options="{{ $type['has_options'] ? '1' : '0' }}">
                            {{ $type['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label>عنوان سوال</label>
                        <input type="text" name="title" required value="{{ old('title', $question->title) }}">
                    </div>
                    <div class="field">
                        <label>توضیح کوتاه</label>
                        <textarea name="description" rows="2">{{ old('description', $question->description) }}</textarea>
                    </div>
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $question->is_required))>
                        سوال اجباری باشد
                    </label>
                </div>

                <div class="field" id="settingsWrapper" style="margin-top:.8rem;">
                    <label>تنظیمات سوال</label>
                    <div class="settings-grid">
                        <div data-setting-group="text"><input type="number" min="1" name="settings[min_length]" placeholder="حداقل کاراکتر" value="{{ $settings['min_length'] ?? '' }}"></div>
                        <div data-setting-group="text"><input type="number" min="1" name="settings[max_length]" placeholder="حداکثر کاراکتر" value="{{ $settings['max_length'] ?? '' }}"></div>
                        <div data-setting-group="text"><input type="text" name="settings[placeholder]" placeholder="راهنمای پاسخ" value="{{ $settings['placeholder'] ?? '' }}"></div>
                        <div data-setting-group="number"><input type="number" name="settings[min_value]" placeholder="حداقل مقدار" value="{{ $settings['min_value'] ?? '' }}"></div>
                        <div data-setting-group="number"><input type="number" name="settings[max_value]" placeholder="حداکثر مقدار" value="{{ $settings['max_value'] ?? '' }}"></div>
                        <div data-setting-group="number"><input type="number" name="settings[step]" placeholder="گام" value="{{ $settings['step'] ?? '' }}"></div>
                        <div data-setting-group="rating"><input type="number" min="1" name="settings[min_rating]" placeholder="حداقل امتیاز" value="{{ $settings['min_rating'] ?? '' }}"></div>
                        <div data-setting-group="rating"><input type="number" min="1" name="settings[max_rating]" placeholder="حداکثر امتیاز" value="{{ $settings['max_rating'] ?? '' }}"></div>
                        <div data-setting-group="rating"><input type="number" min="1" name="settings[rating_step]" placeholder="گام امتیاز" value="{{ $settings['rating_step'] ?? '' }}"></div>
                        <div data-setting-group="date"><input type="text" class="jalali-picker-input" name="settings[min_date]" placeholder="حداقل تاریخ" value="{{ $settings['min_date'] ?? '' }}"></div>
                        <div data-setting-group="date"><input type="text" class="jalali-picker-input" name="settings[max_date]" placeholder="حداکثر تاریخ" value="{{ $settings['max_date'] ?? '' }}"></div>
                        <div data-setting-group="choice"><input type="number" min="1" name="settings[min_choices]" placeholder="حداقل انتخاب" value="{{ $settings['min_choices'] ?? '' }}"></div>
                        <div data-setting-group="choice"><input type="number" min="1" name="settings[max_choices]" placeholder="حداکثر انتخاب" value="{{ $settings['max_choices'] ?? '' }}"></div>
                        <div data-setting-group="file"><input type="number" min="1" name="settings[max_file_size_kb]" placeholder="حداکثر حجم فایل (KB)" value="{{ $settings['max_file_size_kb'] ?? '' }}"></div>
                        <div data-setting-group="file"><input type="text" name="settings[allowed_extensions]" placeholder="پسوندها: pdf,jpg,png,docx" value="{{ $settings['allowed_extensions'] ?? '' }}"></div>
                    </div>
                </div>

                <div class="field" id="optionsWrapper" style="margin-top:.8rem;">
                    <label>گزینه‌ها (متن + مقدار)</label>
                    <div class="option-list" id="optionList">
                        @foreach ($options as $i => $option)
                            <div class="option-row">
                                <input type="text" name="options[{{ $i }}][label]" value="{{ $option['label'] ?? '' }}" placeholder="متن گزینه">
                                <input type="text" name="options[{{ $i }}][value]" value="{{ $option['value'] ?? '' }}" placeholder="مقدار">
                                <button type="button" class="btn btn-ghost remove-option">حذف</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-ghost" id="addOptionBtn" style="width:fit-content;">افزودن گزینه</button>
                    @error('options')
                        <small style="color:#dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="actions">
                    <a class="btn btn-ghost" href="{{ route('admin.surveys.questions.index', $survey) }}">انصراف</a>
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات سوال</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/persian-datepicker-behzadi/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const initPersianDatepickerInputs = () => {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.persianDatepicker) {
                    return;
                }

                window.jQuery('.jalali-picker-input').each(function () {
                    window.jQuery(this).persianDatepicker({
                        formatDate: 'YYYY/0M/0D',
                        closeOnBlur: true,
                        selectedBefore: !!this.value,
                        selectedDate: this.value || null,
                    });
                });
            };

            initPersianDatepickerInputs();

            const typeButtons = document.querySelectorAll('.type-btn[data-type]');
            const typeInput = document.getElementById('questionTypeInput');
            const optionsWrapper = document.getElementById('optionsWrapper');
            const optionList = document.getElementById('optionList');
            const addOptionBtn = document.getElementById('addOptionBtn');
            const settingsGroups = {
                short_text: ['text'], long_text: ['text'], multiple_choice: ['choice'], checkboxes: ['choice'],
                dropdown: ['choice'], rating: ['choice', 'rating'], number: ['number'], email: [], date: ['date'],
                phone: ['text'], url: ['text'], yes_no: ['choice'], linear_scale: ['choice', 'rating'], file_upload: ['file'],
            };
            const setActiveType = (type, hasOptions) => {
                typeInput.value = type;
                typeButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.type === type));
                optionsWrapper.style.display = hasOptions ? 'block' : 'none';
                optionList.querySelectorAll('input').forEach((input) => {
                    input.disabled = !hasOptions;
                    input.required = hasOptions;
                });
                const activeGroups = settingsGroups[type] || [];
                document.querySelectorAll('[data-setting-group]').forEach((el) => {
                    el.style.display = activeGroups.includes(el.dataset.settingGroup) ? 'block' : 'none';
                    const input = el.querySelector('input');
                    if (input) input.disabled = !activeGroups.includes(el.dataset.settingGroup);
                });
            };
            const rebuildOptionNames = () => {
                const rows = optionList.querySelectorAll('.option-row');
                rows.forEach((row, index) => {
                    const labelInput = row.querySelector('input[name$="[label]"]');
                    const valueInput = row.querySelector('input[name$="[value]"]');
                    if (labelInput) labelInput.name = `options[${index}][label]`;
                    if (valueInput) valueInput.name = `options[${index}][value]`;
                });
            };
            addOptionBtn?.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'option-row';
                row.innerHTML = `<input type="text" name="options[0][label]" placeholder="متن گزینه" required>
                    <input type="text" name="options[0][value]" placeholder="مقدار" required>
                    <button type="button" class="btn btn-ghost remove-option">حذف</button>`;
                optionList.appendChild(row);
                rebuildOptionNames();
            });
            optionList?.addEventListener('click', (event) => {
                const btn = event.target.closest('.remove-option');
                if (!btn) return;
                const row = btn.closest('.option-row');
                const rows = optionList.querySelectorAll('.option-row');
                if (!row || rows.length <= 2) return;
                row.remove();
                rebuildOptionNames();
            });
            typeButtons.forEach((btn) => btn.addEventListener('click', () => setActiveType(btn.dataset.type, btn.dataset.hasOptions === '1')));
            const selected = @json($selectedType);
            const selectedBtn = document.querySelector(`.type-btn[data-type="${selected}"]`);
            setActiveType(selected, selectedBtn?.dataset.hasOptions === '1');
        });
    </script>
@endsection
