@php
    if ($survey->allow_edit) {
        $editCapabilityValue = ((int) ($survey->response_edit_window_hours ?? 0)) > 0
            ? 'دارد (' . $toFaDigits((int) $survey->response_edit_window_hours) . ' ساعت)'
            : 'دارد';
    } else {
        $editCapabilityValue = 'ندارد';
    }
@endphp
<div class="survey-meta">
    <div class="meta-card">
        <span class="label">تعداد سوالات</span>
        <span class="value">{{ $toFaDigits($questionsCount) }} سوال</span>
    </div>
    <div class="meta-card">
        <span class="label">زمان تقریبی تکمیل</span>
        <span class="value">{{ $toFaDigits(number_format($estimatedDurationMinutes)) }} دقیقه</span>
    </div>
    <div class="meta-card">
        <span class="label">نحوه پاسخ‌دهی</span>
        <span class="value">{{ $questionsDisplaySinglePage ? 'همهٔ سوالات در یک صفحه' : 'مرحله‌ای، سوال‌به‌سوال' }}</span>
    </div>
    <div class="meta-card">
        <span class="label">قابلیت ویرایش</span>
        <span class="value">{{ $editCapabilityValue }}</span>
    </div>
</div>
