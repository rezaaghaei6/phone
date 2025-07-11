@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4 text-center">ورود / ثبت نام</h1>

    {{-- نمایش خطاهای مربوط به phone یا recaptcha --}}
    @if ($errors->has('phone') || $errors->has('recaptcha'))
        <div class="text-red-500 mb-4">
            {{ $errors->first('phone') ?: $errors->first('recaptcha') }}
        </div>
    @endif

    <form id="phoneForm" action="{{ route('auth.send_code') }}" method="POST">
        @csrf

        {{-- input پنهان برای reCAPTCHA --}}
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">

        <div class="mb-4">
            <label class="block text-gray-700">شماره موبایل</label>
            <input type="text" name="phone" class="w-full p-2 border rounded" placeholder="09xxxxxxxxx" required>
        </div>

        <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">ارسال کد</button>
    </form>
</div>
@endsection

@section('scripts')
{{-- اسکریپت reCAPTCHA v3 --}}
<script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
<script>
    document.getElementById('phoneForm').addEventListener('submit', function(event) {
        event.preventDefault(); // از ارسال فرم جلوگیری می‌کنیم تا اول reCAPTCHA اجرا بشه

        grecaptcha.ready(function() {
            grecaptcha.execute('{{ env('RECAPTCHA_SITE_KEY') }}', {action: 'submit'}).then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                document.getElementById('phoneForm').submit(); // حالا فرم رو بفرست
            });
        });
    });
</script>
@endsection
