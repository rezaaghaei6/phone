<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\RecaptchaLog;
use Illuminate\Support\Facades\Log;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        // اگر در محیط development هستیم و کلیدهای reCAPTCHA تنظیم نشده
        if (app()->environment('local') && (!config('services.recaptcha.site_key') || !config('services.recaptcha.secret_key'))) {
            Log::warning('reCAPTCHA keys not configured in local environment, skipping verification');
            return $next($request);
        }

        $token = $request->input('recaptcha_token');

        if (!$token) {
            Log::error('reCAPTCHA token not provided');
            return back()->withErrors(['recaptcha' => 'خطا در اعتبارسنجی reCAPTCHA - توکن موجود نیست']);
        }

        $secretKey = config('services.recaptcha.secret_key');
        if (!$secretKey) {
            Log::error('reCAPTCHA secret key not configured');
            return back()->withErrors(['recaptcha' => 'خطا در تنظیمات reCAPTCHA']);
        }

        try {
            $response = Http::timeout(10)->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            if (!$response->successful()) {
                Log::error('reCAPTCHA API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return back()->withErrors(['recaptcha' => 'خطا در ارتباط با سرویس reCAPTCHA']);
            }

            $result = $response->json();
            
            Log::info('reCAPTCHA verification result', $result);

            // Log the reCAPTCHA attempt
            try {
                RecaptchaLog::create([
                    'phone' => $request->input('phone'),
                    'ip_address' => $request->ip(),
                    'recaptcha_token' => substr($token, 0, 50), // فقط 50 کاراکتر اول را ذخیره کن
                    'recaptcha_score' => $result['score'] ?? 0,
                ]);
            } catch (\Exception $logError) {
                Log::error('Failed to log reCAPTCHA attempt: ' . $logError->getMessage());
            }

            if (!$result['success']) {
                $errors = $result['error-codes'] ?? [];
                Log::error('reCAPTCHA verification failed', ['errors' => $errors]);
                return back()->withErrors(['recaptcha' => 'اعتبارسنجی reCAPTCHA ناموفق بود']);
            }

            $score = $result['score'] ?? 0;
            if ($score < 0.5) {
                Log::warning('reCAPTCHA score too low', ['score' => $score]);
                return back()->withErrors(['recaptcha' => 'امتیاز reCAPTCHA پایین است']);
            }

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification exception: ' . $e->getMessage());
            return back()->withErrors(['recaptcha' => 'خطا در اعتبارسنجی reCAPTCHA']);
        }

        return $next($request);
    }
}