<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $survey->title }}</title>
    <link rel="stylesheet" href="/fonts/vazirmatn/vazirmatn.css">
    @php
        $surveyBackground = $survey->background_image ? asset($survey->background_image) : null;
        $defaultThankYou = 'از مشارکت شما سپاسگزاریم.';
    @endphp
    <style>
        :root {
            --survey-bg-image: none;
        }
        @if ($surveyBackground)
        :root {
            --survey-bg-image: url('{{ $surveyBackground }}');
        }
        @endif
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            background-color: #f4f5f7;
            background-image: radial-gradient(circle at top right, rgba(214, 17, 25, 0.08), transparent 45%),
                radial-gradient(circle at 20% 10%, rgba(59, 130, 246, 0.08), transparent 40%),
                var(--survey-bg-image);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #0f172a;
        }
        .wrap {
            width: 100%;
            max-width: 920px;
        }
        .survey-panel.is-hidden {
            display: none !important;
        }
        .card {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 1.75rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.6rem;
        }
        .helper {
            color: #64748b;
            font-size: 0.9rem;
        }
        .intro-body {
            margin: 1.25rem 0 1.5rem;
            line-height: 1.9;
            color: #334155;
            font-size: 0.98rem;
        }
        .intro-body p {
            margin: 0 0 0.75rem;
        }
        .intro-body p:last-child {
            margin-bottom: 0;
        }
        .access-gate {
            margin: 1.2rem 0 0.6rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.02);
            padding: 1rem;
        }
        .access-gate h2 {
            margin: 0 0 0.35rem;
            font-size: 1rem;
        }
        .access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .access-grid label {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.86rem;
            color: #334155;
        }
        .access-grid input {
            border: 1px solid rgba(15, 23, 42, 0.16);
            border-radius: 12px;
            padding: 0.68rem 0.8rem;
            font-family: inherit;
        }
        .access-error {
            margin-top: 0.65rem;
            color: #b91c1c;
            font-size: 0.85rem;
        }
        .wizard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        .badge {
            background: rgba(15, 23, 42, 0.06);
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            font-size: 0.8rem;
            color: #475569;
        }
        .question {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem;
            margin-top: 1rem;
            background: rgba(15, 23, 42, 0.02);
            display: none;
        }
        .question.error {
            border-color: rgba(220, 38, 38, 0.6);
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.2);
        }
        .error-text {
            color: #b91c1c;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .question.active {
            display: block;
        }
        .question strong {
            display: block;
            margin-bottom: 0.4rem;
        }
        .input {
            width: 100%;
            padding: 0.7rem;
            border-radius: 12px;
            border: 1px solid rgba(15,23,42,0.12);
            font-family: inherit;
        }
        .option-list {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-top: 0.6rem;
        }
        .option-list label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .wizard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            margin-top: 1.2rem;
        }
        .btn {
            border: none;
            border-radius: 14px;
            padding: 0.75rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
        }
        .btn.primary {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
        }
        .btn.ghost {
            background: rgba(15, 23, 42, 0.08);
            color: #334155;
        }
        .btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .progress {
            margin-top: 1.4rem;
        }
        .progress-bar {
            height: 10px;
            background: rgba(15, 23, 42, 0.08);
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-bar span {
            display: block;
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, #dc2626, #f97316);
        }
        .progress-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.4rem;
        }
        .complete-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .complete-msg {
            text-align: center;
            line-height: 1.85;
            color: #334155;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="wrap">
        @if ($showIntroStep)
            <div id="surveyIntro" class="survey-panel card">
                <h1>{{ $survey->title }}</h1>
                @if ($survey->description)
                    <p class="helper">{{ $survey->description }}</p>
                @endif
                <div class="intro-body">
                    {!! nl2br(e($survey->intro_text)) !!}
                </div>
                @if ($showAccessGate && !$audiencePassed)
                    <form class="access-gate" method="GET" action="{{ route('surveys.public.show', $survey->public_token) }}">
                        <h2>تایید اطلاعات پرسنلی</h2>
                        <p class="helper">برای ورود به فرم، اطلاعات مورد نیاز را وارد کنید.</p>
                        <div class="access-grid">
                            @if (in_array($identityMode, ['personnel_code', 'either'], true))
                                <label>
                                    <span>کد پرسنلی</span>
                                    <input type="text" name="personnel_code" value="{{ $submittedPersonnelCode }}" autocomplete="off">
                                </label>
                            @endif
                            @if (in_array($identityMode, ['national_code', 'either'], true))
                                <label>
                                    <span>کد ملی</span>
                                    <input type="text" name="national_code" value="{{ $submittedNationalCode }}" autocomplete="off">
                                </label>
                            @endif
                        </div>
                        @if ($accessError)
                            <div class="access-error">{{ $accessError }}</div>
                        @endif
                        <div style="text-align: center; margin-top: 0.75rem;">
                            <button type="submit" class="btn primary" style="min-width: 200px;">بررسی و شروع</button>
                        </div>
                    </form>
                @else
                <div style="text-align: center;">
                    <button type="button" class="btn primary" id="startSurveyBtn" style="min-width: 200px;">
                        {{ $showAccessGate ? 'شروع نظرسنجی' : 'شروع نظرسنجی' }}
                    </button>
                </div>
                @endif
            </div>
        @endif

        @if (!$showIntroStep && $showAccessGate && !$audiencePassed)
            <div id="surveyAccessOnly" class="survey-panel card">
                <h1>{{ $survey->title }}</h1>
                @if ($survey->description)
                    <p class="helper">{{ $survey->description }}</p>
                @endif
                <form class="access-gate" method="GET" action="{{ route('surveys.public.show', $survey->public_token) }}">
                    <h2>تایید اطلاعات پرسنلی</h2>
                    <p class="helper">برای ورود به فرم، اطلاعات مورد نیاز را وارد کنید.</p>
                    <div class="access-grid">
                        @if (in_array($identityMode, ['personnel_code', 'either'], true))
                            <label>
                                <span>کد پرسنلی</span>
                                <input type="text" name="personnel_code" value="{{ $submittedPersonnelCode }}" autocomplete="off">
                            </label>
                        @endif
                        @if (in_array($identityMode, ['national_code', 'either'], true))
                            <label>
                                <span>کد ملی</span>
                                <input type="text" name="national_code" value="{{ $submittedNationalCode }}" autocomplete="off">
                            </label>
                        @endif
                    </div>
                    @if ($accessError)
                        <div class="access-error">{{ $accessError }}</div>
                    @endif
                    <div style="text-align: center; margin-top: 0.75rem;">
                        <button type="submit" class="btn primary" style="min-width: 200px;">بررسی و ورود</button>
                    </div>
                </form>
            </div>
        @endif

        <div id="surveyWizard" class="survey-panel @if($showIntroStep || ($showAccessGate && !$audiencePassed)) is-hidden @endif">
            <div class="card">
                <div class="wizard-header">
                    <div>
                        <h1>{{ $survey->title }}</h1>
                        @if ($survey->description)
                            <p class="helper">{{ $survey->description }}</p>
                        @endif
                    </div>
                    <span class="badge">فرم نظرسنجی</span>
                </div>

                @if ($survey->questions->isEmpty())
                    <p class="helper">هنوز سوالی برای این نظرسنجی ثبت نشده است.</p>
                @else
                    @foreach ($survey->questions as $question)
                        <div class="question" data-question data-required="{{ $question->is_required ? '1' : '0' }}">
                            <strong>{{ $question->title }} @if($question->is_required) * @endif</strong>
                            @if ($question->description)
                                <div class="helper">{{ $question->description }}</div>
                            @endif

                            @if (in_array($question->type, ['short_text', 'email'], true))
                                <input type="text" class="input" placeholder="پاسخ شما">
                            @elseif ($question->type === 'long_text')
                                <textarea rows="3" class="input" placeholder="پاسخ شما"></textarea>
                            @elseif ($question->type === 'number')
                                <input type="number" class="input" placeholder="عدد">
                            @elseif ($question->type === 'date')
                                <input type="text" class="input" placeholder="تاریخ">
                            @elseif (in_array($question->type, ['multiple_choice', 'checkboxes', 'dropdown'], true))
                                <div class="option-list">
                                    @foreach ($question->options as $option)
                                        <label>
                                            <input type="{{ $question->type === 'checkboxes' ? 'checkbox' : 'radio' }}" name="q{{ $question->id }}">
                                            {{ $option->label }}
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($question->type === 'rating')
                                <div class="option-list">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <label>
                                            <input type="radio" name="q{{ $question->id }}">
                                            {{ $i }}
                                        </label>
                                    @endfor
                                </div>
                            @endif
                            <div class="error-text" hidden>لطفا این سوال را پاسخ دهید.</div>
                        </div>
                    @endforeach
                    <div class="wizard-actions">
                        <button type="button" class="btn ghost" id="prevQuestion">قبلی</button>
                        <button type="button" class="btn primary" id="nextQuestion">بعدی</button>
                    </div>
                    <div class="progress" aria-hidden="true">
                        <div class="progress-bar"><span id="progressFill"></span></div>
                        <div class="progress-meta">
                            <span id="progressLabel">سوال 1 از {{ $survey->questions->count() }}</span>
                            <span id="progressCount">{{ $survey->questions->count() }} سوال</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div id="surveyComplete" class="survey-panel is-hidden">
            <div class="card" style="text-align: center;">
                <div class="complete-icon" aria-hidden="true">✓</div>
                <h1 style="font-size: 1.35rem;">ثبت شد</h1>
                <p class="complete-msg" id="thankYouMessage">{{ $survey->thank_you_message ?: $defaultThankYou }}</p>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const showIntroStep = @json($showIntroStep);
            const showAccessGate = @json($showAccessGate);
            const audiencePassed = @json($audiencePassed);
            const introEl = document.getElementById('surveyIntro');
            const wizardEl = document.getElementById('surveyWizard');
            const completeEl = document.getElementById('surveyComplete');

            const showWizard = () => {
                introEl?.classList.add('is-hidden');
                wizardEl?.classList.remove('is-hidden');
            };

            document.getElementById('startSurveyBtn')?.addEventListener('click', showWizard);

            if (!showIntroStep && (!showAccessGate || audiencePassed)) {
                introEl?.classList.add('is-hidden');
                wizardEl?.classList.remove('is-hidden');
            }

            const questions = Array.from(document.querySelectorAll('[data-question]'));
            if (!questions.length) return;

            const prevBtn = document.getElementById('prevQuestion');
            const nextBtn = document.getElementById('nextQuestion');
            const progressFill = document.getElementById('progressFill');
            const progressLabel = document.getElementById('progressLabel');
            let index = 0;
            let finished = false;

            const updateWizard = () => {
                questions.forEach((q, i) => q.classList.toggle('active', i === index));
                if (prevBtn) prevBtn.disabled = index === 0;
                if (nextBtn) nextBtn.textContent = index === questions.length - 1 ? 'پایان' : 'بعدی';
                const percent = Math.round(((index + 1) / questions.length) * 100);
                if (progressFill) progressFill.style.width = `${percent}%`;
                if (progressLabel) progressLabel.textContent = `سوال ${index + 1} از ${questions.length}`;
            };

            const showComplete = () => {
                finished = true;
                wizardEl?.classList.add('is-hidden');
                introEl?.classList.add('is-hidden');
                completeEl?.classList.remove('is-hidden');
            };

            prevBtn?.addEventListener('click', () => {
                if (finished) return;
                if (index > 0) {
                    index -= 1;
                    updateWizard();
                }
            });

            const isQuestionAnswered = (question) => {
                const required = question.dataset.required === '1';
                if (!required) return true;
                const inputs = question.querySelectorAll('input, textarea, select');
                if (!inputs.length) return true;
                const textInput = question.querySelector('input[type="text"], input[type="number"], input[type="email"], textarea');
                if (textInput) {
                    return textInput.value.trim().length > 0;
                }
                const optionInputs = question.querySelectorAll('input[type="radio"], input[type="checkbox"]');
                if (optionInputs.length) {
                    return Array.from(optionInputs).some((input) => input.checked);
                }
                return true;
            };

            nextBtn?.addEventListener('click', () => {
                if (finished) return;
                const current = questions[index];
                const errorText = current?.querySelector('.error-text');
                const answered = current ? isQuestionAnswered(current) : true;
                if (!answered) {
                    current.classList.add('error');
                    if (errorText) errorText.hidden = false;
                    return;
                }
                if (current) {
                    current.classList.remove('error');
                    if (errorText) errorText.hidden = true;
                }
                if (index < questions.length - 1) {
                    index += 1;
                    updateWizard();
                } else {
                    showComplete();
                }
            });

            updateWizard();
        });
    </script>
</body>
</html>
