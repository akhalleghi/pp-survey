@once
    <style>
        .publish-rejection-notice {
            margin-top: 0.65rem;
            margin-bottom: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            border: 1px solid rgba(220, 38, 38, 0.35);
            background: rgba(254, 226, 226, 0.45);
            font-size: 0.85rem;
            line-height: 1.55;
        }

        .publish-rejection-notice__title {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #b91c1c;
            margin-bottom: 0.35rem;
        }

        .publish-rejection-notice__text {
            margin: 0;
            white-space: pre-wrap;
            color: var(--slate, #334155);
        }
    </style>
@endonce
@if ($survey->status === 'draft' && filled($survey->publish_rejection_reason))
    <div class="publish-rejection-notice" role="status">
        <strong class="publish-rejection-notice__title">دلیل رد درخواست انتشار توسط مدیر</strong>
        <p class="publish-rejection-notice__text">{{ $survey->publish_rejection_reason }}</p>
    </div>
@endif
