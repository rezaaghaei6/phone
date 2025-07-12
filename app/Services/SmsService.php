<?php

namespace App\Services;

use App\Models\VerificationCode;
use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class SmsService
{
    const MAX_DAILY_ATTEMPTS = 5;
    const COOLDOWN_MINUTES = 2;
    const CODE_VALIDITY_MINUTES = 10;
    const MAX_HOURLY_ATTEMPTS = 3;
    const MAX_IP_DAILY_ATTEMPTS = 50;

    /**
     * ارسال کد تایید با کنترل‌های امنیتی
     */
    public function sendVerificationCode(string $phone): array
    {
        $normalizedPhone = PhoneHelper::normalize($phone);
        
        if (!$normalizedPhone || !PhoneHelper::isValid($normalizedPhone)) {
            throw new \Exception('شماره موبایل معتبر نیست');
        }

        // اعتبارسنجی امنیتی
        $securityErrors = PhoneHelper::validateSecurity($normalizedPhone);
        if (!empty($securityErrors)) {
            throw new \Exception(implode(', ', $securityErrors));
        }

        // بررسی محدودیت نرخ
        $this->checkRateLimits($normalizedPhone);

        // بررسی محدودیت روزانه
        $this->checkDailyLimit($normalizedPhone);

        // بررسی کولداون
        $this->checkCooldown($normalizedPhone);

        // تولید کد تایید امن
        $code = $this->generateSecureCode();

        // ذخیره کد در دیتابیس
        $verificationRecord = $this->storeVerificationCode($normalizedPhone, $code);

        // ارسال SMS
        $sent = $this->sendSMS($normalizedPhone, $code);

        if (!$sent) {
            throw new \Exception('خطا در ارسال پیامک');
        }

        // لاگ امنیتی
        $this->logSecurityEvent('verification_code_sent', $normalizedPhone);

        return [
            'success' => true,
            'message' => 'کد تایید با موفقیت ارسال شد',
            'expires_at' => $verificationRecord->expires_at,
            'phone' => $this->maskPhoneNumber($normalizedPhone)
        ];
    }

    /**
     * تایید کد با کنترل‌های امنیتی
     */
    public function verifyCode(string $phone, string $code): bool
    {
        $normalizedPhone = PhoneHelper::normalize($phone);
        
        if (!$normalizedPhone) {
            return false;
        }

        // بررسی محدودیت نرخ برای تایید
        $key = "verify_attempts:{$normalizedPhone}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new \Exception('تعداد تلاش‌های تایید بیش از حد مجاز');
        }

        RateLimiter::hit($key, 300); // 5 دقیقه

        // جستجوی کد معتبر
        $verification = VerificationCode::where('phone', $normalizedPhone)
            ->where('code', $code)
            ->where('is_valid', true)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$verification) {
            $this->logSecurityEvent('invalid_verification_attempt', $normalizedPhone, [
                'attempted_code' => $code
            ]);
            return false;
        }

        // غیرفعال کردن کد پس از استفاده
        $verification->update(['is_valid' => false]);

        // پاک کردن محدودیت نرخ
        RateLimiter::clear($key);

        $this->logSecurityEvent('verification_success', $normalizedPhone);

        return true;
    }

    /**
     * بررسی محدودیت‌های نرخ
     */
    private function checkRateLimits(string $phone): void
    {
        $phoneKey = "sms_phone:{$phone}";
        $ipKey = "sms_ip:" . request()->ip();

        // محدودیت ساعتی برای هر شماره
        if (RateLimiter::tooManyAttempts($phoneKey, self::MAX_HOURLY_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($phoneKey);
            throw new \Exception("حداکثر تعداد تلاش در ساعت. تا {$seconds} ثانیه دیگر صبر کنید");
        }

        // محدودیت روزانه برای هر IP
        if (RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_DAILY_ATTEMPTS)) {
            throw new \Exception('حداکثر تعداد تلاش برای این آدرس IP');
        }

        RateLimiter::hit($phoneKey, 3600); // 1 ساعت
        RateLimiter::hit($ipKey, 86400); // 24 ساعت
    }

    /**
     * بررسی محدودیت روزانه
     */
    private function checkDailyLimit(string $phone): void
    {
        $today = Carbon::today();
        $dailyCount = VerificationCode::where('phone', $phone)
            ->whereDate('created_at', $today)
            ->count();

        if ($dailyCount >= self::MAX_DAILY_ATTEMPTS) {
            throw new \Exception('حداکثر تعداد درخواست روزانه رسیده است');
        }
    }

    /**
     * بررسی کولداون
     */
    private function checkCooldown(string $phone): void
    {
        $lastCode = VerificationCode::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(self::COOLDOWN_MINUTES))
            ->latest()
            ->first();

        if ($lastCode) {
            $waitTime = self::COOLDOWN_MINUTES - now()->diffInMinutes($lastCode->created_at);
            throw new \Exception("لطفا {$waitTime} دقیقه دیگر صبر کنید");
        }
    }

    /**
     * تولید کد امن
     */
    private function generateSecureCode(): string
    {
        // استفاده از random_int برای امنیت بیشتر
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($this->isWeakCode($code));

        return $code;
    }

    /**
     * بررسی ضعیف بودن کد
     */
    private function isWeakCode(string $code): bool
    {
        // کدهای ضعیف
        $weakPatterns = [
            '/^(\d)\1{5}$/',     // تکرار یک رقم (111111)
            '/^123456$/',         // متوالی
            '/^654321$/',         // متوالی معکوس
            '/^000000$/',         // صفر
            '/^(12){3}$/',       // تکرار الگو
        ];

        foreach ($weakPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ذخیره کد تایید
     */
    private function storeVerificationCode(string $phone, string $code): VerificationCode
    {
        // غیرفعال کردن کدهای قبلی
        VerificationCode::where('phone', $phone)
            ->where('is_valid', true)
            ->update(['is_valid' => false]);

        $today = Carbon::today();
        $dailyCount = VerificationCode::where('phone', $phone)
            ->whereDate('created_at', $today)
            ->count();

        return VerificationCode::create([
            'phone' => $phone,
            'code' => $code,
            'is_valid' => true,
            'expires_at' => now()->addMinutes(self::CODE_VALIDITY_MINUTES),
            'daily_count' => $dailyCount + 1,
            'last_sent_at' => now(),
        ]);
    }

    /**
     * ارسال SMS
     */
    private function sendSMS(string $phone, string $code): bool
    {
        try {
            // در محیط تولید، از سرویس SMS واقعی استفاده کنید
            if (app()->environment('production')) {
                // TODO: Implement real SMS service (Kavenegar, etc.)
                return $this->sendRealSMS($phone, $code);
            }

            // در محیط توسعه، فقط لاگ می‌کنیم
            Log::info("SMS کد تایید ارسال شد", [
                'phone' => $phone,
                'code' => $code,
                'message' => "کد تایید شما: {$code}"
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('خطا در ارسال SMS', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ارسال SMS واقعی
     */
    private function sendRealSMS(string $phone, string $code): bool
    {
        // TODO: پیاده‌سازی سرویس SMS واقعی
        // مثال با کاوه‌نگار:
        /*
        $api = new \Kavenegar\KavenegarApi(config('services.kavenegar.api_key'));
        $result = $api->Send(
            config('services.kavenegar.sender'),
            $phone,
            "کد تایید شما: {$code}"
        );
        return $result->status === 1;
        */
        
        return true; // Placeholder
    }

    /**
     * مخفی کردن شماره تلفن
     */
    private function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) >= 4) {
            return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
        }
        return $phone;
    }

    /**
     * لاگ امنیتی
     */
    private function logSecurityEvent(string $event, string $phone, array $extra = []): void
    {
        Log::channel('security')->info($event, array_merge([
            'phone' => $phone,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $extra));
    }

    /**
     * متد برای سازگاری با کدهای موجود
     */
    public function sendCode(string $phone): array
    {
        return $this->sendVerificationCode($phone);
    }

    /**
     * دریافت آمار استفاده
     */
    public function getUsageStats(string $phone): array
    {
        $today = Carbon::today();
        
        return [
            'daily_count' => VerificationCode::where('phone', $phone)
                ->whereDate('created_at', $today)
                ->count(),
            'remaining_attempts' => max(0, self::MAX_DAILY_ATTEMPTS - 
                VerificationCode::where('phone', $phone)
                    ->whereDate('created_at', $today)
                    ->count()),
            'cooldown_remaining' => $this->getCooldownRemaining($phone),
        ];
    }

    /**
     * زمان باقی‌مانده کولداون
     */
    private function getCooldownRemaining(string $phone): int
    {
        $lastCode = VerificationCode::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(self::COOLDOWN_MINUTES))
            ->latest()
            ->first();

        if (!$lastCode) {
            return 0;
        }

        return max(0, self::COOLDOWN_MINUTES - now()->diffInMinutes($lastCode->created_at));
    }
}