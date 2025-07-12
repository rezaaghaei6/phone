<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\RecaptchaLog;
use Illuminate\Support\Facades\Log;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        // در محیط local اگر کپچا وارد نشده باشد، کنترل نکنیم
        if (app()->environment('local') && !$request->filled('captcha')) {
            Log::warning('Captcha not provided in local environment, skipping verification');
            return $next($request);
        }

        // بررسی وجود کپچا در درخواست
        $captcha = $request->input('captcha');
        
        if (!$captcha) {
            Log::error('Captcha not provided');
            return back()->withErrors(['captcha' => 'لطفاً کپچا را وارد کنید']);
        }

        // بررسی صحت کپچا
        $validator = Validator::make($request->all(), [
            'captcha' => 'required|captcha'
        ], [
            'captcha.required' => 'لطفاً کپچا را وارد کنید',
            'captcha.captcha' => 'کپچا نادرست است'
        ]);

        if ($validator->fails()) {
            Log::warning('Captcha validation failed', [
                'captcha' => $captcha,
                'ip' => $request->ip()
            ]);
            
            return back()->withErrors($validator);
        }

        try {
            // لاگ کپچا
            RecaptchaLog::create([
                'phone' => $request->input('phone'),
                'ip_address' => $request->ip(),
                'recaptcha_token' => substr($captcha, 0, 50), // فقط 50 کاراکتر اول
                'recaptcha_score' => 1, // برای کپچای معمولی، امتیاز 1 قرار می‌دهیم
            ]);
        } catch (\Exception $logError) {
            Log::error('Failed to log captcha attempt: ' . $logError->getMessage());
        }

        return $next($request);
    }
}