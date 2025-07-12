@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4 text-center">ورود / ثبت نام</h1>

    {{-- نمایش خطاهای مربوط به phone یا captcha --}}
    @if ($errors->has('phone') || $errors->has('captcha'))
        <div class="text-red-500 mb-4 p-3 bg-red-100 rounded">
            {{ $errors->first('phone') ?: $errors->first('captcha') }}
        </div>
    @endif

    @if (session('success'))
        <div class="text-green-500 mb-4 p-3 bg-green-100 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('auth.send_code') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 mb-2">شماره موبایل</label>
            <input type="text" name="phone" value="{{ old('phone') }}" 
                   class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                   placeholder="09xxxxxxxxx" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 mb-2">کپچا</label>
            <div class="flex items-center space-x-2">
                <div class="flex-1">
                    <input type="text" name="captcha" 
                           class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="کپچا را وارد کنید" required>
                </div>
                <div class="flex-shrink-0">
                    <img src="{{ captcha_src('flat') }}" alt="captcha" id="captcha-image" 
                         class="border rounded cursor-pointer" 
                         onclick="refreshCaptcha()" 
                         title="برای تازه‌سازی کلیک کنید">
                </div>
            </div>
            <small class="text-gray-500">برای تازه‌سازی کپچا روی تصویر کلیک کنید</small>
        </div>

        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-lg transition-colors">
            ارسال کد
        </button>
    </form>
</div>

<script>
function refreshCaptcha() {
    const captchaImage = document.getElementById('captcha-image');
    const timestamp = new Date().getTime();
    captchaImage.src = `{{ url('captcha/flat') }}?t=${timestamp}`;
}
</script>
@endsection