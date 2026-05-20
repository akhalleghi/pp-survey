<?php

namespace App\Services\Sms;

use App\Models\SmsProvider;
use App\Models\SmsProviderConfig;
use App\Support\SmsDriverRegistry;
use App\Services\Sms\Support\Panel3300Format;
use Illuminate\Support\Facades\DB;

final class SmsPanelService
{
    /**
     * @return array{username: string, password: string, send_number: string, api_url: string|null}
     */
    public function credentialsForProvider(SmsProvider $provider): array
    {
        $config = $provider->config;
        if (! $config) {
            throw new \RuntimeException('اطلاعات اتصال این پنل ذخیره نشده است.');
        }

        return [
            'username' => SmsCredentialVault::decrypt($config->username_encrypted),
            'password' => SmsCredentialVault::decrypt($config->password_encrypted),
            'send_number' => $config->send_number,
            'api_url' => $provider->default_api_url,
        ];
    }

    public function activeProvider(): ?SmsProvider
    {
        $activeConfig = SmsProviderConfig::query()
            ->where('is_active', true)
            ->with('provider')
            ->first();

        return $activeConfig?->provider;
    }

    public function sendUsingProvider(SmsProvider $provider, string $mobile, string $message): SmsSendResult
    {
        $credentials = $this->credentialsForProvider($provider);
        $driver = SmsDriverRegistry::make($provider->driver);

        return $driver->send($mobile, $message, $credentials);
    }

    public function sendUsingActive(string $mobile, string $message): SmsSendResult
    {
        $provider = $this->activeProvider();
        if (! $provider) {
            return new SmsSendResult(false, 'هیچ پنل پیامکی به‌عنوان فعال انتخاب نشده است.');
        }

        return $this->sendUsingProvider($provider, $mobile, $message);
    }

    /**
     * @param  array{username: string, password?: string|null, send_number: string, set_active?: bool}  $data
     */
    public function saveConfig(SmsProvider $provider, array $data, ?int $adminUserId = null): SmsProviderConfig
    {
        return DB::transaction(function () use ($provider, $data, $adminUserId) {
            $config = SmsProviderConfig::query()->firstOrNew([
                'sms_provider_id' => $provider->id,
            ]);

            $config->username_encrypted = SmsCredentialVault::encrypt($data['username']);

            if (! empty($data['password'])) {
                $config->password_encrypted = SmsCredentialVault::encrypt($data['password']);
            } elseif (! $config->exists) {
                throw new \InvalidArgumentException('رمز عبور پنل الزامی است.');
            }

            $config->send_number = $this->normalizeSendNumber($data['send_number']);
            $config->updated_by_admin_user_id = $adminUserId;

            if (! empty($data['set_active'])) {
                SmsProviderConfig::query()
                    ->when($config->exists, fn ($q) => $q->where('id', '!=', $config->id))
                    ->update(['is_active' => false]);
                $config->is_active = true;
            } else {
                $config->is_active = false;
            }

            $config->save();

            return $config->fresh(['provider']);
        });
    }

    public function activateProvider(SmsProvider $provider): void
    {
        DB::transaction(function () use ($provider) {
            if (! $provider->config) {
                throw new \RuntimeException('ابتدا اطلاعات اتصال این پنل را ذخیره کنید.');
            }

            SmsProviderConfig::query()->update(['is_active' => false]);
            $provider->config->update(['is_active' => true]);
        });
    }

    public static function normalizeMobile(string $mobile): string
    {
        return Panel3300Format::mobileForApi($mobile);
    }

    public function normalizeSendNumber(string $number): string
    {
        $latin = Panel3300Format::toLatinDigits(trim($number));
        $digits = preg_replace('/\D+/', '', $latin) ?? '';

        if ($digits === '') {
            return '';
        }

        // در UI همان شماره خط پنل (مثلاً 30001636) ذخیره می‌شود.
        if (str_starts_with($digits, '98') && strlen($digits) > 10) {
            return substr($digits, 2);
        }

        return ltrim($digits, '0') !== '' ? ltrim($digits, '0') : $digits;
    }

    public function markTested(SmsProvider $provider): void
    {
        $provider->config?->update(['last_tested_at' => now()]);
    }
}
