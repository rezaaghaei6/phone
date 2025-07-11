@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4 text-center">ایجاد ادمین جدید</h1>
    @if ($errors->any())
        <div class="text-red-500 mb-4">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <div class="text-green-500 mb-4">{{ session('success') }}</div>
    @endif
    <form action="{{ route('admin.create_submit') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700">شماره موبایل</label>
            <input type="text" name="phone" class="w-full p-2 border rounded" placeholder="+98xxxxxxxxxx" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">نام</label>
            <input type="text" name="name" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">ایجاد</button>
    </form>
</div>
@endsection