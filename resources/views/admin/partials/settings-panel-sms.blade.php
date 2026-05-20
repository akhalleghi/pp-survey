@php
    use App\Services\Sms\SmsCredentialVault;

    $smsPanelErrors = $errors->updateSmsPanel ?? $errors;
    $smsTestErrors = $errors->smsTest ?? $errors;
    $smsProviders = \App\Models\SmsProvider::query()
        ->available()
        ->with('config')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
    $activeConfig = \App\Models\SmsProviderConfig::query()->where('is_active', true)->with('provider')->first();
    $defaultProviderId = old('sms_provider_id', $activeConfig?->sms_provider_id ?? $smsProviders->first()?->id);
    $selectedProvider = $smsProviders->firstWhere('id', (int) $defaultProviderId) ?? $smsProviders->first();
    $selectedConfig = $selectedProvider?->config;
    $usernameValue = old('username');
    if ($usernameValue === null && $selectedConfig) {
        try {
            $usernameValue = SmsCredentialVault::decrypt($selectedConfig->username_encrypted);
        } catch (Throwable $e) {
            $usernameValue = '';
        }
    }
    $sendNumberValue = old('send_number', $selectedConfig?->send_number ?? '');
    $hasStoredPassword = $selectedConfig !== null;
@endphp

<section class="settings-modal-panel {{ ($settingsActiveTab ?? '') === 'sms_panel' ? 'active' : '' }}" data-settings-panel="sms_panel">
    <h3><i class="fa-solid fa-comment-sms" aria-hidden="true"></i> تنظیمات پنل پیامکی</h3>
    <p>پنل پیامک فعال را انتخاب کنید و اطلاعات اتصال را ذخیره کنید. اطلاعات حساس به‌صورت رمزنگاری‌شده در پایگاه داده نگهداری می‌شوند.</p>

    <div class="sms-panel-security-note">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        <span>فقط <strong>مدیر اصلی</strong> به این بخش دسترسی دارد. رمز عبور پنل هرگز در صفحه نمایش داده نمی‌شود؛ برای تغییر، رمز جدید وارد کنید. از ارسال تست فقط برای شمارهٔ خودتان استفاده کنید.</span>
    </div>

    @if ($smsProviders->isEmpty())
        <p class="error-text">هیچ پنل پیامکی در سامانه تعریف نشده است. با پشتیبانی فنی تماس بگیرید.</p>
    @else
        <form method="POST" action="{{ route('admin.settings.sms-panel') }}" class="sms-panel-config-form" autocomplete="off">
            @csrf
            <div class="form-grid">
                <div class="form-control">
                    <label for="sms-provider-select">پنل پیامک</label>
                    <select id="sms-provider-select" name="sms_provider_id" required class="{{ $smsPanelErrors->has('sms_provider_id') ? 'error' : '' }}">
                        @foreach ($smsProviders as $provider)
                            <option value="{{ $provider->id }}" @selected((int) $defaultProviderId === (int) $provider->id)>
                                {{ $provider->name }}
                                @if ($provider->config?->is_active)
                                    (فعال)
                                @elseif ($provider->config)
                                    (پیکربندی‌شده)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @if ($smsPanelErrors->has('sms_provider_id'))
                        <span class="error-text">{{ $smsPanelErrors->first('sms_provider_id') }}</span>
                    @endif
                </div>
                <div class="form-control">
                    <label for="sms-username">نام کاربری پنل</label>
                    <input
                        id="sms-username"
                        type="text"
                        name="username"
                        value="{{ $usernameValue }}"
                        required
                        autocomplete="off"
                        class="{{ $smsPanelErrors->has('username') ? 'error' : '' }}"
                    >
                    @if ($smsPanelErrors->has('username'))
                        <span class="error-text">{{ $smsPanelErrors->first('username') }}</span>
                    @endif
                </div>
                <div class="form-control">
                    <label for="sms-password">رمز عبور پنل</label>
                    <input
                        id="sms-password"
                        type="password"
                        name="password"
                        value=""
                        {{ $hasStoredPassword ? '' : 'required' }}
                        autocomplete="new-password"
                        placeholder="{{ $hasStoredPassword ? 'خالی = بدون تغییر' : '' }}"
                        class="{{ $smsPanelErrors->has('password') ? 'error' : '' }}"
                    >
                    @if ($smsPanelErrors->has('password'))
                        <span class="error-text">{{ $smsPanelErrors->first('password') }}</span>
                    @else
                        <span class="password-field-hint">
                            @if ($hasStoredPassword)
                                رمز قبلی ذخیره شده است. در صورت نیاز به تغییر، رمز جدید را وارد کنید.
                            @else
                                رمز عبور وب‌سرویس پنل 3300.ir را وارد کنید.
                            @endif
                        </span>
                    @endif
                </div>
                <div class="form-control">
                    <label for="sms-send-number">شماره ارسال‌کننده (خط)</label>
                    <input
                        id="sms-send-number"
                        type="text"
                        name="send_number"
                        value="{{ $sendNumberValue }}"
                        required
                        dir="ltr"
                        inputmode="numeric"
                        placeholder="مثال: 3000xxxx"
                        class="{{ $smsPanelErrors->has('send_number') ? 'error' : '' }}"
                    >
                    @if ($smsPanelErrors->has('send_number'))
                        <span class="error-text">{{ $smsPanelErrors->first('send_number') }}</span>
                    @else
                        <span class="password-field-hint">شماره خط اختصاصی همان‌طور که در پنل 3300.ir نمایش داده می‌شود وارد کنید (مثال: 30001636). هنگام ارسال، به‌صورت خودکار به فرمت 9830001636 تبدیل می‌شود.</span>
                    @endif
                </div>
            </div>
            <label class="inline-toggle" style="margin: 0.75rem 0 1rem;">
                <input type="checkbox" name="set_active" value="1" {{ old('set_active', true) ? 'checked' : '' }}>
                استفاده از این پنل به‌عنوان پنل فعال سامانه
            </label>
            <div class="form-actions">
                <button type="submit" class="primary-btn">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    ذخیره تنظیمات پنل
                </button>
            </div>
        </form>

        <div class="sms-test-card">
            <h4><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> تست ارسال پیامک</h4>
            <p>پس از ذخیره اطلاعات اتصال، یک پیامک آزمایشی ارسال کنید تا از صحت تنظیمات مطمئن شوید.</p>
            <form method="POST" action="{{ route('admin.settings.sms-panel.test') }}" class="sms-panel-test-form" autocomplete="off">
                @csrf
                <input type="hidden" name="sms_provider_id" value="{{ $defaultProviderId }}" id="sms-test-provider-id">
                <div class="form-grid">
                    <div class="form-control">
                        <label for="sms-test-mobile">شماره مقصد</label>
                        <input
                            id="sms-test-mobile"
                            type="tel"
                            name="test_mobile"
                            value="{{ old('test_mobile') }}"
                            required
                            dir="ltr"
                            inputmode="tel"
                            placeholder="09121234567"
                            class="{{ $smsTestErrors->has('test_mobile') ? 'error' : '' }}"
                        >
                        @if ($smsTestErrors->has('test_mobile'))
                            <span class="error-text">{{ $smsTestErrors->first('test_mobile') }}</span>
                        @endif
                    </div>
                    <div class="form-control" style="grid-column: 1 / -1;">
                        <label for="sms-test-message">متن پیام تست</label>
                        <textarea
                            id="sms-test-message"
                            name="test_message"
                            rows="3"
                            maxlength="500"
                            required
                            placeholder="این یک پیام آزمایشی از سامانه نظرسنجی است."
                            class="{{ $smsTestErrors->has('test_message') ? 'error' : '' }}"
                        >{{ old('test_message', 'پیام آزمایشی سامانه نظرسنجی') }}</textarea>
                        @if ($smsTestErrors->has('test_message'))
                            <span class="error-text">{{ $smsTestErrors->first('test_message') }}</span>
                        @endif
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-btn" {{ $hasStoredPassword ? '' : 'disabled title=ابتدا تنظیمات پنل را ذخیره کنید' }}>
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        ارسال پیام تست
                    </button>
                </div>
            </form>
        </div>

        <script>
            (() => {
                const providerSelect = document.getElementById('sms-provider-select');
                const testProviderInput = document.getElementById('sms-test-provider-id');
                if (!providerSelect || !testProviderInput) return;
                const sync = () => { testProviderInput.value = providerSelect.value; };
                providerSelect.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endif
</section>
