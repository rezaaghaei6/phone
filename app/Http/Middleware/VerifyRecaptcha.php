<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('recaptcha_token');

        if (!$token) {
            return back()->withErrors(['recaptcha' => 'خطا در اعتبارسنجی reCAPTCHA']);
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => env('NOCAPTCHA_SECRET'),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (!$response->json('success') || $response->json('score') < 0.5) {
            return back()->withErrors(['recaptcha' => 'اعتبارسنجی reCAPTCHA ناموفق بود.']);
        }

        return $next($request);
    }
}
