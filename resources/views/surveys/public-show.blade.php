<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $survey->title }}</title>
    <style>
        body {
            margin: 0;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            background: #f5f6f8;
            color: #0f172a;
        }
        .wrap {
            max-width: 920px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }
        .card {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 1.75rem;
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.6rem;
        }
        .helper {
            color: #64748b;
            font-size: 0.9rem;
        }
        .question {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem;
            margin-top: 1rem;
            background: rgba(15, 23, 42, 0.02);
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
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>{{ $survey->title }}</h1>
            @if ($survey->description)
                <p class="helper">{{ $survey->description }}</p>
            @endif

            
            @if ($survey->questions->isEmpty())
                <p class="helper">???? ????? ???? ??? ??????? ??? ???? ???.</p>
            @else
                @foreach ($survey->questions as $question)
                    <div class="question">
                        <strong>{{ $question->title }} @if($question->is_required) * @endif</strong>
                        @if ($question->description)
                            <div class="helper">{{ $question->description }}</div>
                        @endif

                        @if (in_array($question->type, ['short_text', 'email'], true))
                            <input type="text" class="input" placeholder="???? ???">
                        @elseif ($question->type === 'long_text')
                            <textarea rows="3" class="input" placeholder="???? ???"></textarea>
                        @elseif ($question->type === 'number')
                            <input type="number" class="input" placeholder="???">
                        @elseif ($question->type === 'date')
                            <input type="text" class="input" placeholder="?????">
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
                    </div>
                @endforeach
            @endif
</div>
    </div>
</body>
</html>
