<?php
namespace App\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showPhoneForm()
    {
        return view('auth.phone');
    }

    public function sendCode(Request $request, SmsService $smsService)
    {
        $request->validate(['phone' => 'required|string']);
        try {
            $phone = PhoneHelper::normalizePhone($request->input('phone'));
            $smsService->sendCode($phone);
            session(['phone' => $phone]);
            return redirect()->route('auth.verify');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['phone' => $e->getMessage()]);
        }
    }

    public function showVerifyForm()
    {
        return view('auth.verify');
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $phone = session('phone');
        $code = $request->input('code');

        $verification = VerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->where('is_valid', true)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$verification) {
            return redirect()->back()->withErrors(['code' => 'Invalid or expired code']);
        }

        // Invalidate code after 1 minute
        if (now()->diffInMinutes($verification->created_at) >= config('app.code_expiry_minutes', 1)) {
            $verification->update(['is_valid' => false]);
            return redirect()->back()->withErrors(['code' => 'Code has expired']);
        }

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            session(['phone' => $phone]);
            return redirect()->route('auth.register');
        }

        Auth::login($user);
        $verification->update(['is_valid' => false]);
        return redirect()->route('dashboard');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
        ]);

        $phone = session('phone');
        $user = User::create([
            'phone' => $phone,
            'name' => $request->input('name'),
            'surname' => $request->input('surname'),
            'is_admin' => false,
        ]);

        Auth::login($user);
        return redirect()->route('dashboard');
    }
}