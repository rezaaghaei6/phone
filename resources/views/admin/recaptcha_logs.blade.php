@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4">لاگ‌های reCAPTCHA</h1>
    <table class="w-full border">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">شماره</th>
                <th class="p-2">IP</th>
                <th class="p-2">توکن</th>
                <th class="p-2">امتیاز</th>
                <th class="p-2">زمان</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td class="p-2">{{ $log->phone ?? '-' }}</td>
                    <td class="p-2">{{ $log->ip_address }}</td>
                    <td class="p-2">{{ $log->recaptcha_token }}</td>
                    <td class="p-2">{{ $log->recaptcha_score }}</td>
                    <td class="p-2">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection