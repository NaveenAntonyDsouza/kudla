<?php

use App\Http\Controllers\Api\CascadeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShortlistController;
use App\Http\Controllers\SearchController;
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
    Route::post('/login/send-otp', [LoginController::class, 'sendLoginOtp'])->name('login.otp.send')->middleware('throttle:5,1');
    Route::post('/login/verify-otp', [LoginController::class, 'verifyLoginOtp'])->name('login.otp.verify')->middleware('throttle:10,1');

});

// Registration step 1 - accessible by both guests (new) and auth users (back button from step 2)
Route::get('/register', [RegisterController::class, 'showStep1'])->name('register');
Route::post('/register', [RegisterController::class, 'storeStep1'])->name('register.store1');

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
    Route::post('/register/verify', [RegisterController::class, 'verifyOtp'])->name('register.verifyotp')->middleware('throttle:10,1');
    Route::get('/register/verify-email', [RegisterController::class, 'showVerifyEmail'])->name('register.verifyemail');
    Route::post('/register/verify-email/send-otp', [RegisterController::class, 'sendEmailOtp'])->name('register.sendemailotp')->middleware('throttle:5,1');
    Route::post('/register/verify-email', [RegisterController::class, 'verifyEmailOtp'])->name('register.verifyemailotp')->middleware('throttle:10,1');
    Route::get('/register/complete', [RegisterController::class, 'complete'])->name('register.complete');

    // Onboarding (optional profile completion)
    Route::get('/onboarding/step-1', [OnboardingController::class, 'showStep1'])->name('onboarding.step1');
    Route::post('/onboarding/step-1', [OnboardingController::class, 'storeStep1'])->name('onboarding.store1');
    Route::get('/onboarding/step-2', [OnboardingController::class, 'showStep2'])->name('onboarding.step2');
    Route::post('/onboarding/step-2', [OnboardingController::class, 'storeStep2'])->name('onboarding.store2');
    Route::get('/onboarding/partner-preferences', [OnboardingController::class, 'showPartnerPreferences'])->name('onboarding.preferences');
    Route::post('/onboarding/partner-preferences', [OnboardingController::class, 'storePartnerPreferences'])->name('onboarding.storePreferences');
    Route::get('/onboarding/lifestyle', [OnboardingController::class, 'showLifestyle'])->name('onboarding.lifestyle');
    Route::post('/onboarding/lifestyle', [OnboardingController::class, 'storeLifestyle'])->name('onboarding.storeLifestyle');
    Route::post('/onboarding/finish', [OnboardingController::class, 'finishOnboarding'])->name('onboarding.finish');
});

// Authenticated routes requiring completed profile
Route::middleware(['auth', 'profile.complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Photo Management
    Route::get('/manage-photos', [PhotoController::class, 'index'])->name('photos.manage');
    Route::post('/manage-photos/upload', [PhotoController::class, 'upload'])->name('photos.upload')->middleware('throttle:10,1');
    Route::delete('/manage-photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('/manage-photos/{photo}/restore', [PhotoController::class, 'restore'])->name('photos.restore');
    Route::post('/manage-photos/{photo}/primary', [PhotoController::class, 'setPrimary'])->name('photos.primary');
    Route::post('/manage-photos/privacy', [PhotoController::class, 'updatePrivacy'])->name('photos.privacy');
    Route::delete('/manage-photos/{photo}/permanent', [PhotoController::class, 'deletePermanently'])->name('photos.deletePermanent');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Shortlist
    Route::get('/shortlist', [ShortlistController::class, 'index'])->name('shortlist.index');
    Route::post('/shortlist/{profile}', [ShortlistController::class, 'toggle'])->name('shortlist.toggle');

    // Block
    Route::get('/blocked', [BlockController::class, 'index'])->name('blocked.index');
    Route::post('/block/{profile}', [BlockController::class, 'block'])->name('block.profile');
    Route::post('/unblock/{profile}', [BlockController::class, 'unblock'])->name('unblock.profile');

    // Search
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Interests (specific routes first, parameterized last)
    Route::get('/interests', [InterestController::class, 'inbox'])->name('interests.inbox');
    Route::post('/interests/send/{profile}', [InterestController::class, 'send'])->name('interests.send');
    Route::get('/interests/{interest}', [InterestController::class, 'show'])->name('interests.show')->whereNumber('interest');
    Route::post('/interests/{interest}/accept', [InterestController::class, 'accept'])->name('interests.accept');
    Route::post('/interests/{interest}/decline', [InterestController::class, 'decline'])->name('interests.decline');
    Route::post('/interests/{interest}/cancel', [InterestController::class, 'cancel'])->name('interests.cancel');
    Route::post('/interests/{interest}/message', [InterestController::class, 'sendMessage'])->name('interests.message');
    Route::post('/interests/{interest}/star', [InterestController::class, 'toggleStar'])->name('interests.star');
    Route::post('/interests/{interest}/trash', [InterestController::class, 'trash'])->name('interests.trash');

    // Profile View & Edit
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/preview', [ProfileController::class, 'preview'])->name('profile.preview');
    Route::get('/profile/{profile}', [ProfileController::class, 'viewProfile'])->name('profile.view');
    Route::post('/profile/{section}', [ProfileController::class, 'update'])->name('profile.update');
});
