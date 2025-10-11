<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\GuestPropertyController;

Route::get('/verify-email/{token}', [EmailVerificationController::class,'verifyEmail'])
    ->name('verify.email');

Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');


Route::get('/2fa/verify', [TwoFactorAuthController::class, 'verifyForm'])->name('2fa.verify.form');
Route::post('/2fa/verify', [AuthController::class, 'verify2FA'])->name('2fa.verify');
Route::post('/2fa/resend', [AuthController::class, 'resend2FACode'])->name('2fa.resend');


// Public routes
Route::get('/', [App\Http\Controllers\GuestPropertyController::class, 'index'])->name('welcome');

// Guest property details and application
Route::get('/properties/{property}', [App\Http\Controllers\GuestPropertyController::class, 'show'])->name('guest.properties.show');
Route::post('/properties/{propertyId}/apply', [App\Http\Controllers\GuestPropertyController::class, 'apply'])->name('guest.properties.apply');
Route::get('/properties/{id}/weather', [PropertyController::class, 'getWeatherData'])->name('guest.properties.weather');

// Authentication routes
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile routes
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    
    // Debug route for avatar issues (temporary)
    Route::get('/debug-avatar', function() {
        $user = auth()->user();
        $data = [
            'user_avatar' => $user->avatar,
            'avatar_url_method' => $user->avatar_url,
            'direct_asset_path' => $user->avatar ? asset('storage/avatars/' . $user->avatar) : 'No avatar',
            'storage_path_exists' => $user->avatar ? file_exists(storage_path('app/public/avatars/' . $user->avatar)) : false,
            'public_path_exists' => $user->avatar ? file_exists(public_path('storage/avatars/' . $user->avatar)) : false,
            'storage_directory_contents' => scandir(storage_path('app/public/avatars')),
            'public_directory_contents' => is_dir(public_path('storage/avatars')) ? scandir(public_path('storage/avatars')) : 'Directory does not exist'
        ];
        return response()->json($data);
    })->name('debug.avatar');

    // Direct avatar serving route (backup if symlink doesn't work)
    Route::get('/avatars/{filename}', function($filename) {
        $path = storage_path('app/public/avatars/' . $filename);
        if (file_exists($path)) {
            return response()->file($path);
        }
        abort(404);
    })->name('avatars.serve');

    // Test avatar upload functionality
    Route::get('/test-avatar-upload', function() {
        $avatarPath = storage_path('app/public/avatars');
        $data = [
            'avatars_directory_exists' => file_exists($avatarPath),
            'avatars_directory_writable' => is_writable($avatarPath),
            'avatars_directory_path' => $avatarPath,
            'current_user_avatar' => auth()->user()->avatar,
            'storage_app_public_exists' => file_exists(storage_path('app/public')),
            'storage_app_public_writable' => is_writable(storage_path('app/public')),
        ];
        return response()->json($data);
    })->name('test.avatar.upload');

    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::resource('users', UserController::class);
        Route::patch('/unlock-user/{id}', [AuthController::class, 'unlockUser'])->name('unlock-user');

        // Admin Property Management Routes
        Route::get('properties', [PropertyController::class, 'adminIndex'])->name('properties.index');
        Route::post('properties', [PropertyController::class, 'adminstore'])->name('properties.store');
        Route::get('properties/create', [PropertyController::class, 'adminCreate'])->name('properties.create');
        Route::get('properties/{property}', [PropertyController::class, 'adminShow'])->name('properties.show');
        Route::get('properties/{property}/edit', [PropertyController::class, 'adminEdit'])->name('properties.edit');
        Route::put('properties/{property}', [PropertyController::class, 'adminupdate'])->name('properties.update');
        Route::delete('properties/{property}', [PropertyController::class, 'admindestroy'])->name('properties.destroy');

        // Logs
        Route::get('/logs', [App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');
        Route::get('/properties/{id}/images', [PropertyController::class, 'adminGetPropertyImages'])->name('properties.images');
        Route::get('/properties/{id}/weather', [PropertyController::class, 'getWeatherData'])->name('properties.weather');

    });

    // Landlord routes
    Route::middleware(['role:landlord'])->prefix('landlord')->name('landlord.')->group(function () {
        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
        Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
        Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
        Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
        
        // Application management for landlords
        Route::get('/properties/{propertyId}/applications', [PropertyController::class, 'landlordViewApplications'])->name('properties.applications');
        Route::post('/applications/{applicationId}/approve', [PropertyController::class, 'landlordApproveApplication'])->name('applications.approve');
        Route::post('/applications/{applicationId}/reject', [PropertyController::class, 'landlordRejectApplication'])->name('applications.reject');
        Route::get('/applications', [PropertyController::class, 'landlordAllApplications'])->name('applications.all');
        Route::get('/properties/{propertyId}/boarders', [PropertyController::class, 'landlordViewBoarders'])->name('properties.boarders');
        Route::get('/boarders', [PropertyController::class, 'landlordAllBoarders'])->name('boarders.all');
        Route::post('/boarders/{boardersId}/kicked', [PropertyController::class, 'landlordKickBoarders'])->name('boarders.kicked');
        Route::get('/properties/{id}/images', [PropertyController::class, 'getPropertyImages'])->name('properties.images');
        Route::get('/properties/{id}/weather', [PropertyController::class, 'getWeatherData'])->name('properties.weather');
    });

    // Tenant routes
    Route::middleware(['role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
        Route::get('/properties', [PropertyController::class, 'tenantIndex'])->name('properties.index');
        Route::get('/properties/{property}', [PropertyController::class, 'tenantShow'])->name('properties.show');

        // Property application for tenants
        Route::post('/properties/{propertyId}/apply', [PropertyController::class, 'tenantApply'])->name('properties.apply');
        Route::get('/my-applications', [PropertyController::class, 'tenantMyApplications'])->name('applications.index');
        Route::post('/applications/{applicationId}/cancel', [PropertyController::class, 'tenantCancelApplication'])->name('applications.cancel');
        Route::get('/properties/{id}/weather', [PropertyController::class, 'getWeatherData'])->name('properties.weather');
    });
});
