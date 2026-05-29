@if ($showOtpStep ?? false)
    <div class="access-gate access-gate--otp" id="surveyOtpGate" data-cooldown="{{ (int) ($otpCooldownSeconds ?? 0) }}">
        <div class="access-steps" aria-hidden="true">
            <span class="access-step is-done">۱. اطلاعات پرسنلی</span>
            <span class="access-step-divider"></span>
            <span class="access-step is-active">۲. تایید پیامکی</span>
        </div>
        <h2>تایید شماره موبایل</h2>
        <p class="helper">
            کد تایید به شماره
            <strong class="otp-mobile-mask">{{ $maskedMobile ?? '***' }}</strong>
            ارسال شد. کد را وارد کنید تا بتوانید نظرسنجی را شروع کنید.
        </p>

        @if (!empty($otpNotice))
            <div class="otp-notice" role="status">{{ $otpNotice }}</div>
        @endif

        <form method="POST" action="{{ route('surveys.public.otp.verify', $survey->public_token) }}" class="otp-form" autocomplete="off">
            @csrf
            <label class="otp-input-wrap">
                <span>کد تایید ({{ $toFaDigits($otpCodeLength ?? 6) }} رقم)</span>
                <input
                    type="text"
                    name="otp_code"
                    id="otpCodeInput"
                    class="otp-code-input"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="{{ (int) ($otpCodeLength ?? 6) }}"
                    placeholder="{{ str_repeat('۰', (int) ($otpCodeLength ?? 6)) }}"
                    required
                    autofocus
                >
            </label>

            @if ($accessError)
                <div class="access-error">{{ $accessError }}</div>
            @endif

            <div class="otp-actions">
                <button type="submit" class="btn primary otp-verify-btn">تایید و شروع نظرسنجی</button>
                <button type="button" class="btn ghost otp-resend-btn" id="otpResendBtn" disabled>
                    ارسال مجدد کد
                    <span class="otp-resend-timer" id="otpResendTimer" hidden></span>
                </button>
            </div>
        </form>

        <p class="otp-security-hint">
            <span aria-hidden="true">🔒</span>
            کد فقط برای این نشست معتبر است و پس از چند تلاش ناموفق باطل می‌شود.
        </p>
    </div>
@else
    <form class="access-gate" method="POST" action="{{ route('surveys.public.access', $survey->public_token) }}">
        @csrf
        @if ($requireSmsOtp ?? false)
            <div class="access-steps" aria-hidden="true">
                <span class="access-step is-active">۱. اطلاعات پرسنلی</span>
                <span class="access-step-divider"></span>
                <span class="access-step">۲. تایید پیامکی</span>
            </div>
        @endif
        <h2>تایید اطلاعات پرسنلی</h2>
        <p class="helper">
            برای ورود به فرم، اطلاعات مورد نیاز را وارد کنید.
            @if ($requireSmsOtp ?? false)
                پس از تایید، کد پیامکی به موبایل ثبت‌شده ارسال می‌شود.
            @endif
        </p>
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
        <div class="otp-actions" style="justify-content: center; margin-top: 0.75rem;">
            <button type="submit" class="btn primary" style="min-width: 200px;">
                {{ ($requireSmsOtp ?? false) ? 'ادامه و دریافت کد پیامکی' : 'بررسی و شروع' }}
            </button>
        </div>
    </form>
@endif
