@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-4">لاگ‌های کاربران</h1>
    <table class="w-full border">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2">شماره</th>
                <th class="p-2">کد</th>
                <th class="p-2">وضعیت</th>
                <th class="p-2">تعداد روزانه</th>
                <th class="p-2">آخرین ارسال</th>
                <th class="p-2">ایجاد شده در</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td class="p-2">{{ $log->phone }}</td>
                    <td class="p-2">{{ $log->code }}</td>
                    <td class="p-2">{{ $log->is_valid ? 'معتبر' : 'نامعتبر' }}</td>
                    <td class="p-2">{{ $log->daily_count }}</td>
                    <td class="p-2">{{ $log->last_sent_at ? $log->last_sent_at->format('Y-m-d H:i:s') : '-' }}</td>
                    <td class="p-2">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection