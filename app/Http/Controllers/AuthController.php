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
        $request->validate([
            'phone' => 'required|string',
            'captcha' => 'required|captcha'
        ], [
            'phone.required' => 'شماره موبایل الزامی است',
            'captcha.required' => 'لطفاً کپچا را وارد کنید',
            'captcha.captcha' => 'کپچا نادرست است'
        ]);
        
        try {
            $phone = PhoneHelper::normalize($request->input('phone'));
            
            if (!PhoneHelper::isValid($phone)) {
                return redirect()->back()->withErrors(['phone' => 'شماره موبایل معتبر نیست']);
            }
            
            $smsService->sendCode($phone);
            session(['phone' => $phone]);
            
            return redirect()->route('auth.verify')->with('success', 'کد تایید برای شما ارسال شد');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['phone' => $e->getMessage()]);
        }
    }

    public function showVerifyForm()
    {
        if (!session('phone')) {
            return redirect()->route('auth.phone');
        }
        return view('auth.verify');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
            'captcha' => 'required|captcha'
        ], [
            'code.required' => 'کد تایید الزامی است',
            'code.digits' => 'کد تایید باید 6 رقم باشد',
            'captcha.required' => 'لطفاً کپچا را وارد کنید',
            'captcha.captcha' => 'کپچا نادرست است'
        ]);
        
        $phone = session('phone');
        $code = $request->input('code');

        if (!$phone) {
            return redirect()->route('auth.phone');
        }

        $verification = VerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->where('is_valid', true)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$verification) {
            return redirect()->back()->withErrors(['code' => 'کد نامعتبر یا منقضی شده است']);
        }

        // غیرفعال کردن کد پس از استفاده
        $verification->update(['is_valid' => false]);

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            session(['phone' => $phone]);
            return redirect()->route('auth.register');
        }

        Auth::login($user);
        session()->forget('phone');
        return redirect()->route('dashboard');
    }

    public function showRegisterForm()
    {
        if (!session('phone')) {
            return redirect()->route('auth.phone');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
        ], [
            'name.required' => 'نام الزامی است',
            'surname.required' => 'نام خانوادگی الزامی است'
        ]);

        $phone = session('phone');
        if (!$phone) {
            return redirect()->route('auth.phone');
        }

        $user = User::create([
            'phone' => $phone,
            'name' => $request->input('name'),
            'surname' => $request->input('surname'),
            'is_admin' => false,
        ]);

        Auth::login($user);
        session()->forget('phone');
        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('auth.phone');
    }
}