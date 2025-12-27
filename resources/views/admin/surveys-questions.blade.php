@extends('admin.layouts.app')

@section('page-title', 'طراحی سوالات نظرسنجی')
@section('page-description', 'مدیریت و طراحی انواع سوالات برای این نظرسنجی.')

@section('content')
    <style>
        :root {
            --panel: #fff;
            --border: rgba(15, 23, 42, 0.08);
        }
        .designer-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 1.5rem;
            align-items: start;
        }
        .designer-canvas {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 26px;
            padding: 1.5rem;
            min-height: 480px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .survey-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .survey-header h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .badge {
            background: rgba(214, 17, 25, 0.12);
            color: var(--primary);
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            font-size: 0.8rem;
        }
        .question-card {
            border: 1px dashed rgba(15, 23, 42, 0.2);
            border-radius: 20px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .question-card header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .question-meta {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .question-actions {
            display: flex;
            gap: 0.5rem;
        }
        .question-actions button {
            border: none;
            border-radius: 12px;
            padding: 0.45rem 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .question-actions .danger {
            background: rgba(220, 38, 38, 0.15);
            color: #b91c1c;
        }
        .question-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .question-options {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.9rem;
        }
        .question-options span {
            background: rgba(15, 23, 42, 0.05);
            padding: 0.35rem 0.6rem;
            border-radius: 12px;
        }
        .designer-side {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: sticky;
            top: 1rem;
        }
        .type-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .type-btn {
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 14px;
            padding: 0.6rem 0.8rem;
            text-align: center;
            cursor: pointer;
            font-size: 0.85rem;
            background: rgba(15, 23, 42, 0.03);
        }
        .type-btn.active {
            border-color: rgba(214, 17, 25, 0.5);
            background: rgba(214, 17, 25, 0.12);
            color: var(--primary);
            font-weight: 600;
        }
        .form-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.9rem;
        }
        .form-field input,
        .form-field textarea,
        .form-field select {
            border: 1px solid rgba(15, 23, 42, 0.16);
            border-radius: 14px;
            padding: 0.75rem 0.9rem;
            font-family: inherit;
        }
        .inline-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .option-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .option-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 110px auto;
            gap: 0.5rem;
            align-items: center;
        }
        .option-row button {
            border: none;
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
            border-radius: 10px;
            padding: 0.35rem 0.6rem;
            cursor: pointer;
        }
        .save-btn {
            border: none;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            cursor: pointer;
        }
        @media (max-width: 1024px) {
            .designer-wrap {
                grid-template-columns: 1fr;
            }
            .designer-side {
                position: static;
            }
        }
    </style>

    <div class="designer-wrap">
        <section class="designer-canvas">
            <div class="survey-header">
                <div>
                    <h2>طراحی سوالات: {{ $survey->title }}</h2>
                    <div class="question-meta">
                        <span>واحد: {{ $survey->unit?->name ?? 'نامشخص' }}</span>
                        <span>وضعیت: {{ $survey->status }}</span>
                    </div>
                </div>
                <span class="badge">{{ $survey->questions->count() }} سوال</span>
            </div>

            @forelse ($survey->questions as $question)
                <article class="question-card">
                    <header>
                        <div>
                            <strong>{{ $question->title }}</strong>
                            <div class="question-meta">
                                <span>نوع: {{ $questionTypes[$question->type]['label'] ?? $question->type }}</span>
                                <span>{{ $question->is_required ? 'اجباری' : 'اختیاری' }}</span>
                            </div>
                        </div>
                        <div class="question-actions">
                            <form method="POST" action="{{ route('admin.surveys.questions.destroy', [$survey, $question]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </header>
                    @if ($question->description)
                        <div class="question-meta">{{ $question->description }}</div>
                    @endif
                    @if (in_array($question->type, ['multiple_choice', 'checkboxes', 'dropdown'], true))
                        <div class="question-options">
                            @foreach ($question->options as $option)
                                <span>{{ $option->label }}</span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="question-card" style="text-align:center;">
                    هنوز سوالی ثبت نشده. از ستون سمت راست یک نوع سوال اضافه کنید.
                </div>
            @endforelse
        </section>

        <aside class="designer-side">
            <div>
                <strong>افزودن سوال جدید</strong>
                <p class="question-meta">نوع سوال را انتخاب کنید و فرم را تکمیل کنید.</p>
            </div>
            @if ($errors->any())
                <div class="question-meta" style="color: #dc2626;">
                    لطفا خطاهای فرم را بررسی کنید.
                </div>
            @endif

            <div class="type-grid" id="questionTypeGrid">
                @foreach ($questionTypes as $key => $type)
                    <button type="button" class="type-btn" data-type="{{ $key }}" data-has-options="{{ $type['has_options'] ? '1' : '0' }}">
                        {{ $type['label'] }}
                    </button>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.surveys.questions.store', $survey) }}" class="form-card" id="questionForm">
                @csrf
                <input type="hidden" name="type" id="questionTypeInput" value="short_text">

                <div class="form-field">
                    <label for="questionTitle">عنوان سوال</label>
                    <input id="questionTitle" name="title" type="text" placeholder="مثلاً میزان رضایت شما؟" required>
                    @error('title')
                        <small class="question-meta" style="color: #dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="questionDescription">توضیح کوتاه (اختیاری)</label>
                    <textarea id="questionDescription" name="description" rows="2" placeholder="راهنمایی برای پاسخ دهنده"></textarea>
                </div>

                <label class="inline-toggle">
                    <input type="checkbox" name="is_required" value="1">
                    سوال اجباری باشد
                </label>

                <div class="form-field" id="optionsWrapper" style="display:none;">
                    <label>گزینه ها</label>
                    <div class="option-list" id="optionList">
                        <div class="option-row">
                            <input type="text" name="options[0][label]" placeholder="گزینه ۱">
                            <input type="text" name="options[0][value]" placeholder="مقدار">
                            <button type="button" class="remove-option">حذف</button>
                        </div>
                        <div class="option-row">
                            <input type="text" name="options[1][label]" placeholder="گزینه ۲">
                            <input type="text" name="options[1][value]" placeholder="مقدار">
                            <button type="button" class="remove-option">حذف</button>
                        </div>
                    </div>
                    <button type="button" class="type-btn" id="addOptionBtn">افزودن گزینه</button>
                    @error('options')
                        <small class="question-meta" style="color: #dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <button type="submit" class="save-btn">ثبت سوال</button>
            </form>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const typeButtons = document.querySelectorAll('.type-btn[data-type]');
            const typeInput = document.getElementById('questionTypeInput');
            const optionsWrapper = document.getElementById('optionsWrapper');
            const optionList = document.getElementById('optionList');
            const addOptionBtn = document.getElementById('addOptionBtn');

            const setActiveType = (type, hasOptions) => {
                typeInput.value = type;
                typeButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.type === type));
                optionsWrapper.style.display = hasOptions ? 'block' : 'none';
                optionList.querySelectorAll('input').forEach((input) => {
                    input.disabled = !hasOptions;
                });
            };

            typeButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    setActiveType(btn.dataset.type, btn.dataset.hasOptions === '1');
                });
            });

            setActiveType('short_text', false);

            const rebuildOptionNames = () => {
                const rows = optionList.querySelectorAll('.option-row');
                rows.forEach((row, index) => {
                    const labelInput = row.querySelector('input[name$="[label]"]');
                    const valueInput = row.querySelector('input[name$="[value]"]');
                    if (labelInput) {
                        labelInput.name = `options[${index}][label]`;
                    }
                    if (valueInput) {
                        valueInput.name = `options[${index}][value]`;
                    }
                });
            };

            addOptionBtn?.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'option-row';
                row.innerHTML = `
                    <input type="text" name="options[0][label]" placeholder="گزینه جدید">
                    <input type="text" name="options[0][value]" placeholder="مقدار">
                    <button type="button" class="remove-option">حذف</button>
                `;
                optionList.appendChild(row);
                rebuildOptionNames();
            });

            optionList?.addEventListener('click', (event) => {
                const button = event.target.closest('.remove-option');
                if (!button) return;
                const row = button.closest('.option-row');
                if (!row) return;
                const rows = optionList.querySelectorAll('.option-row');
                if (rows.length <= 1) return;
                row.remove();
                rebuildOptionNames();
            });
        });
    </script>
@endsection
