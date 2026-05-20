<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsProvider;
use App\Services\Sms\SmsPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SmsPanelSettingsController extends Controller
{
    public function __construct(
        private readonly SmsPanelService $smsPanelService,
    ) {}

    public function update(Request $request): RedirectResponse
    {
        $provider = SmsProvider::query()
            ->available()
            ->findOrFail($request->input('sms_provider_id'));

        $existing = $provider->config;
        $hasPassword = $existing !== null;

        $validated = $request->validateWithBag('updateSmsPanel', [
            'sms_provider_id' => ['required', 'integer', Rule::exists('sms_providers', 'id')->where('is_available', true)],
            'username' => ['required', 'string', 'max:191'],
            'password' => [$hasPassword ? 'nullable' : 'required', 'string', 'max:191'],
            'send_number' => ['required', 'string', 'max:32'],
            'set_active' => ['sometimes', 'boolean'],
        ], [
            'username.required' => 'نام کاربری پنل الزامی است.',
            'password.required' => 'رمز عبور پنل الزامی است.',
            'send_number.required' => 'شماره ارسال‌کننده الزامی است.',
        ]);

        $adminId = (int) $request->session()->get('admin_id');

        $this->smsPanelService->saveConfig($provider, [
            'username' => $validated['username'],
            'password' => $validated['password'] ?? null,
            'send_number' => $validated['send_number'],
            'set_active' => $request->boolean('set_active', true),
        ], $adminId);

        return redirect()
            ->back()
            ->with('open_settings_modal', true)
            ->with('settings_active_tab', 'sms_panel')
            ->with('status', 'تنظیمات پنل پیامکی با موفقیت ذخیره شد.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $adminId = (int) $request->session()->get('admin_id');
        $rateKey = 'sms-panel-test:'.$adminId;
        $maxAttempts = max(1, (int) config('sms.test_rate_limit_per_minute', 5));

        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'test_mobile' => "تعداد درخواست تست بیش از حد مجاز است. لطفاً {$seconds} ثانیه دیگر تلاش کنید.",
            ])->errorBag('smsTest');
        }

        $validated = $request->validateWithBag('smsTest', [
            'sms_provider_id' => ['required', 'integer', Rule::exists('sms_providers', 'id')->where('is_available', true)],
            'test_mobile' => ['required', 'string', 'max:20', 'regex:/^(0?9\d{9}|98\d{10})$/'],
            'test_message' => ['required', 'string', 'min:2', 'max:500'],
        ], [
            'test_mobile.required' => 'شماره مقصد را وارد کنید.',
            'test_mobile.regex' => 'شماره موبایل معتبر نیست (مثال: 09121234567).',
            'test_message.required' => 'متن پیام تست را وارد کنید.',
        ]);

        RateLimiter::hit($rateKey, 60);

        $provider = SmsProvider::query()->with('config')->findOrFail($validated['sms_provider_id']);

        if (! $provider->config) {
            throw ValidationException::withMessages([
                'sms_provider_id' => 'ابتدا اطلاعات اتصال این پنل را ذخیره کنید.',
            ])->errorBag('smsTest');
        }

        $mobile = SmsPanelService::normalizeMobile($validated['test_mobile']);
        $result = $this->smsPanelService->sendUsingProvider($provider, $mobile, $validated['test_message']);

        if ($result->success) {
            $this->smsPanelService->markTested($provider);

            return redirect()
                ->back()
                ->with('open_settings_modal', true)
                ->with('settings_active_tab', 'sms_panel')
                ->with('status', $result->message);
        }

        throw ValidationException::withMessages([
            'test_message' => $result->message,
        ])->errorBag('smsTest');
    }
}
