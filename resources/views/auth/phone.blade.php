@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4 text-center">ورود / ثبت نام</h1>

    {{-- نمایش خطاهای مربوط به phone یا recaptcha --}}
    @if ($errors->has('phone') || $errors->has('recaptcha'))
        <div class="text-red-500 mb-4 p-3 bg-red-100 rounded">
            {{ $errors->first('phone') ?: $errors->first('recaptcha') }}
        </div>
    @endif

    @if (session('success'))
        <div class="text-green-500 mb-4 p-3 bg-green-100 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form id="phoneForm" action="{{ route('auth.send_code') }}" method="POST">
        @csrf

        {{-- input پنهان برای reCAPTCHA --}}
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">

        <div class="mb-4">
            <label class="block text-gray-700 mb-2">شماره موبایل</label>
            <input type="text" name="phone" value="{{ old('phone') }}" 
                   class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   placeholder="09xxxxxxxxx" required>
        </div>

        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-lg transition-colors">
            ارسال کد
        </button>
    </form>

    {{-- اگر reCAPTCHA تنظیم نشده باشه --}}
    @if (!config('services.recaptcha.site_key'))
        <div class="mt-4 p-3 bg-yellow-100 text-yellow-800 rounded">
            <strong>توجه:</strong> reCAPTCHA تنظیم نشده است. لطفاً کلیدهای reCAPTCHA را در فایل .env تنظیم کنید.
        </div>
    @endif
</div>
@endsection

@section('scripts')
@if (config('services.recaptcha.site_key'))
{{-- اسکریپت reCAPTCHA v3 --}}
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
    document.getElementById('phoneForm').addEventListener('submit', function(event) {
        event.preventDefault();

        // اگر reCAPTCHA موجود نباشه، فرم رو مستقیم بفرست
        if (typeof grecaptcha === 'undefined') {
            console.warn('reCAPTCHA not loaded, submitting form directly');
            this.submit();
            return;
        }

        grecaptcha.ready(function() {
            grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'submit'}).then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                document.getElementById('phoneForm').submit();
            }).catch(function(error) {
                console.error('reCAPTCHA error:', error);
                // در صورت خطا، فرم رو بدون reCAPTCHA بفرست
                document.getElementById('phoneForm').submit();
            });
        });
    });
</script>
@else
<script>
    // اگر reCAPTCHA تنظیم نشده، فقط فرم عادی
    console.warn('reCAPTCHA site key not configured');
</script>
@endif
@endsection