<?php
namespace App\Services;

use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendVerificationCode($phone)
    {
        // Check daily limit and cooldown
        $today = Carbon::today();
        $codeRecord = VerificationCode::where('phone', $phone)
            ->whereDate('created_at', $today)
            ->first();

        if ($codeRecord && $codeRecord->daily_count >= config('app.sms_daily_limit', 5)) {
            throw new \Exception('Daily SMS limit reached');
        }

        if ($codeRecord && $codeRecord->last_sent_at && now()->diffInMinutes($codeRecord->last_sent_at) < config('app.sms_cooldown_minutes', 10)) {
            throw new \Exception('Please wait before requesting another code');
        }

        // Generate 6-digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in database
        $expiresAt = now()->addMinutes(config('app.code_validity_minutes', 2));
        VerificationCode::updateOrCreate(
            ['phone' => $phone, 'is_valid' => true],
            [
                'code' => $code,
                'is_valid' => true,
                'expires_at' => $expiresAt,
                'daily_count' => $codeRecord ? $codeRecord->daily_count + 1 : 1,
                'last_sent_at' => now(),
            ]
        );

        // Simulate SMS sending (replace with actual SMS provider)
        Log::info("Sending SMS to $phone: Your verification code is $code");
    }
}