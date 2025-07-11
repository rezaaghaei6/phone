<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('auth.phone');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/phone', [AuthController::class, 'showPhoneForm'])->name('auth.phone');
    Route::post('/phone', [AuthController::class, 'sendCode'])->middleware('recaptcha')->name('auth.send_code');
    Route::get('/verify', [AuthController::class, 'showVerifyForm'])->name('auth.verify');
    Route::post('/verify', [AuthController::class, 'verifyCode'])->middleware('recaptcha')->name('auth.verify_code');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('auth.register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register_submit');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::get('/admin/create', [AdminController::class, 'showCreateAdminForm'])->name('admin.create');
        Route::post('/admin/create', [AdminController::class, 'createAdmin'])->name('admin.create_submit');
        Route::get('/admin/user-logs', [AdminController::class, 'userLogs'])->name('admin.user_logs');
        Route::get('/admin/recaptcha-logs', [AdminController::class, 'recaptchaLogs'])->name('admin.recaptcha_logs');
    });
});