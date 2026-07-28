<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OTP Service — handles phone verification via SMS.
 *
 * Supports:
 * - Twilio (international)
 * - Generic SMS gateway (local Yemeni providers)
 * - Fallback: log-only for development
 */
class OtpService
{
    private int $length = 6;
    private int $expiryMinutes = 5;
    private int $maxAttempts = 3;
    private int $resendCooldown = 60; // seconds

    /**
     * Generate and send OTP to a phone number.
     */
    public function sendOtp(string $phone): array
    {
        // Check cooldown
        $cooldownKey = "otp_cooldown_{$phone}";
        if (Cache::has($cooldownKey)) {
            $remaining = Cache::get($cooldownKey);
            return [
                'success' => false,
                'message' => "يرجى الانتظار {$remaining} ثانية قبل إعادة الإرسال",
            ];
        }

        // Generate OTP
        $otp = str_pad(random_int(0, pow(10, $this->length) - 1), $this->length, '0', STR_PAD_LEFT);

        // Store OTP in cache
        $cacheKey = "otp_{$phone}";
        Cache::put($cacheKey, [
            'otp' => $otp,
            'attempts' => 0,
            'created_at' => now()->toISOString(),
        ], now()->addMinutes($this->expiryMinutes));

        // Set cooldown
        Cache::put($cooldownKey, $this->resendCooldown, now()->addSeconds($this->resendCooldown));

        // Send SMS
        $sent = $this->sendSms($phone, "رمز التحقق الخاص بك: {$otp}");

        if ($sent) {
            return [
                'success' => true,
                'message' => 'تم إرسال رمز التحقق',
                'expires_in' => $this->expiryMinutes * 60,
            ];
        }

        return [
            'success' => false,
            'message' => 'فشل إرسال رمز التحقق، يرجى المحاولة لاحقاً',
        ];
    }

    /**
     * Verify OTP for a phone number.
     */
    public function verifyOtp(string $phone, string $code): array
    {
        $cacheKey = "otp_{$phone}";
        $data = Cache::get($cacheKey);

        if (!$data) {
            return [
                'success' => false,
                'message' => 'رمز التحقق منتهي الصلاحية أو غير موجود',
            ];
        }

        // Check attempts
        if ($data['attempts'] >= $this->maxAttempts) {
            Cache::forget($cacheKey);
            return [
                'success' => false,
                'message' => 'تم تجاوز الحد الأقصى للمحاولات',
            ];
        }

        // Increment attempts
        $data['attempts']++;
        Cache::put($cacheKey, $data, now()->addMinutes($this->expiryMinutes));

        // Verify
        if ($data['otp'] !== $code) {
            return [
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
                'attempts_remaining' => $this->maxAttempts - $data['attempts'],
            ];
        }

        // Success — clear OTP
        Cache::forget($cacheKey);
        Cache::forget("otp_cooldown_{$phone}");

        return [
            'success' => true,
            'message' => 'تم التحقق بنجاح',
        ];
    }

    /**
     * Send SMS via configured provider.
     */
    private function sendSms(string $phone, string $message): bool
    {
        $provider = config('services.sms.provider', 'log');

        try {
            return match ($provider) {
                'twilio' => $this->sendViaTwilio($phone, $message),
                'generic' => $this->sendViaGeneric($phone, $message),
                default => $this->sendViaLog($phone, $message),
            };
        } catch (\Exception $e) {
            Log::error('SMS send failed', [
                'phone' => $phone,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send via Twilio.
     */
    private function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');
        $from = config('services.twilio.from');

        if (!$sid || !$token) {
            Log::warning('Twilio not configured, falling back to log');
            return $this->sendViaLog($phone, $message);
        }

        $response = Http::asForm()
            ->auth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => "+{$phone}",
                'Body' => $message,
            ]);

        return $response->successful();
    }

    /**
     * Send via generic SMS gateway.
     */
    private function sendViaGeneric(string $phone, string $message): bool
    {
        $apiUrl = config('services.sms.api_url');
        $apiKey = config('services.sms.api_key');

        if (!$apiUrl) {
            return $this->sendViaLog($phone, $message);
        }

        $response = Http::post($apiUrl, [
            'api_key' => $apiKey,
            'to' => $phone,
            'message' => $message,
        ]);

        return $response->successful();
    }

    /**
     * Log-only fallback (development).
     */
    private function sendViaLog(string $phone, string $message): bool
    {
        Log::info("OTP SMS to {$phone}: {$message}");
        return true;
    }
}
