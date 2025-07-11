@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="flex items-center mb-4">
        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
            {{ auth()->user()->name[0] ?? '' }}
        </div>
        <h1 class="text-2xl font-bold ml-4">خوش آمدید، {{ auth()->user()->name }}</h1>
    </div>
    @if (auth()->user()->is_admin)
        <div class="flex space-x-4">
            <a href="{{ route('admin.create') }}" class="bg-green-500 text-white p-2 rounded">ایجاد ادمین جدید</a>
            <a href="{{ route('admin.user_logs') }}" class="bg-blue-500 text-white p-2 rounded">لاگ‌های کاربران</a>
            <a href="{{ route('admin.recaptcha_logs') }}" class="bg-blue-500 text-white p-2 rounded">لاگ‌های reCAPTCHA</a>
        </div>
    @endif
</div>
@endsection