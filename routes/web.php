<?php

use App\Http\Controllers\Api\CascadeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Cascade API endpoints (no auth required, used during registration)
Route::prefix('api/cascade')->group(function () {
    Route::get('/communities', [CascadeController::class, 'communities']);
    Route::get('/states', [CascadeController::class, 'states']);
    Route::get('/districts', [CascadeController::class, 'districts']);
    Route::get('/countries', [CascadeController::class, 'countries']);
});

// Guest-only routes (logged-in users redirected to /dashboard)
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::post('/login/send-otp', [LoginController::class, 'sendLoginOtp'])->name('login.otp.send');
    Route::post('/login/verify-otp', [LoginController::class, 'verifyLoginOtp'])->name('login.otp.verify');

    // Registration step 1
    Route::get('/register', [RegisterController::class, 'showStep1'])->name('register');
    Route::post('/register', [RegisterController::class, 'storeStep1'])->name('register.store1');
});

// Logout (requires auth)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Registration steps 2-5 (requires auth, onboarding in progress)
Route::middleware('auth')->group(function () {
    Route::get('/register/step-2', [RegisterController::class, 'showStep2'])->name('register.step2');
    Route::post('/register/step-2', [RegisterController::class, 'storeStep2'])->name('register.store2');
    Route::get('/register/step-3', [RegisterController::class, 'showStep3'])->name('register.step3');
    Route::post('/register/step-3', [RegisterController::class, 'storeStep3'])->name('register.store3');
    Route::get('/register/step-4', [RegisterController::class, 'showStep4'])->name('register.step4');
    Route::post('/register/step-4', [RegisterController::class, 'storeStep4'])->name('register.store4');
    Route::get('/register/step-5', [RegisterController::class, 'showStep5'])->name('register.step5');
    Route::post('/register/step-5', [RegisterController::class, 'storeStep5'])->name('register.store5');
    Route::get('/register/verify', [RegisterController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify/send-otp', [RegisterController::class, 'sendOtp'])->name('register.sendotp');
    Route::post('/register/verify', [RegisterController::class, 'verifyOtp'])->name('register.verifyotp');
    Route::get('/register/complete', [RegisterController::class, 'complete'])->name('register.complete');
});

// Authenticated routes requiring completed profile
Route::middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
