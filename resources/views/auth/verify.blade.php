@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4 text-center">تایید کد</h1>
    @if ($errors->has('code') || $errors->has('recaptcha'))
        <div class="text-red-500 mb-4">{{ $errors->first('code') ?: $errors->first('recaptcha') }}</div>
    @endif
    <form id="verifyForm" action="{{ route('auth.verify_code') }}" method="POST">
        @csrf
        <input type="hidden" name="recaptcha_token">
        <div class="mb-4">
            <label class="block text-gray-700">کد شش رقمی</label>
            <input type="text" name="code" class="w-full p-2 border rounded" required>
        </div>
        <button type="button" onclick="submitWithRecaptcha('verifyForm')" class="w-full bg-blue-500 text-white p-2 rounded">تایید</button>
    </form>
</div>
@endsection