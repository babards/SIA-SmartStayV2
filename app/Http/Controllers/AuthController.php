<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\VerifyEmail;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\EmailVerificationController;
use App\Traits\LogsActivity;

class AuthController extends Controller
{
    use LogsActivity;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:tenant,landlord',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_verified' => 0,
            'verification_token' => Str::random(64),
        ]);

        Mail::to($user->email)->send(new VerifyEmail($user));
        $this->logActivity('register', "New user registration: {$user->first_name} {$user->last_name} ({$user->email})");
        return redirect('/login')->with('message', 'Please check your email to verify your account before logging in.');
    }

    public function verifyEmail($token)
    {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return redirect('/login')->with('error', 'Invalid verification token.');
        }

        $user->is_verified = 1;
        $user->verification_token = null;
        $user->save();

        return redirect('/login')->with('success', 'Email verified successfully. You can now login.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        // 🔒 Check if user exists and is locked
        if ($user && $user->locked) {
            $this->logActivity('login_locked', "Attempt to login on locked account: {$user->email}");
            return back()->with('error', 'Your account is locked. Please contact an administrator.');
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Reset login attempts after successful login
            $user->login_attempts = 0;
            $user->save();

            Auth::logout(); // Logout immediately for 2FA

            // Email not verified
            if (!$user->is_verified) {
                $this->logActivity('login_failed', "Login failed - Unverified email: {$request->email}");
                return back()->with('error', 'Please verify your email before logging in.');
            }

            // 🔐 2FA setup
            $user->two_factor_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->two_factor_expires_at = now()->addMinutes(2);
            $user->save();

            Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($user));
            $this->logActivity('login_2fa_required', "2FA code sent to: {$user->email}");

            $request->session()->put('2fa_user_id', $user->id);
            return redirect()->route('2fa.verify.form')->with('message', 'Please check your email for the 2FA code.');
        }

        // ❌ Invalid credentials, track failed attempt
        if ($user) {
            $user->increment('login_attempts');

            if ($user->login_attempts >= 5) {
                $user->locked = true;
                $user->save();

                $this->logActivity('account_locked', "User account locked: {$user->email}");
                return back()->with('error', 'Your account has been locked due to too many failed login attempts. Contact admin.');
            }
        }

        $this->logActivity('login_failed', "Failed login attempt: {$request->email}");

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }


    public function verify2FA(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('2fa_user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->logActivity('2fa_failed', '2FA failed: user not found');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'User not found.']);
            }
            return redirect()->route('login')->with('error', 'User not found.');
        }

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            $this->logActivity('2fa_failed', '2FA failed: no code found');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No 2FA code found. Please try logging in again.']);
            }
            return redirect()->route('login')->with('error', 'No 2FA code found. Please try logging in again.');
        }

        if (now()->gt($user->two_factor_expires_at)) {
            $this->logActivity('2fa_failed', '2FA failed: code expired');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => '2FA code has expired. Please try logging in again.']);
            }
            return redirect()->route('login')->with('error', '2FA code has expired. Please try logging in again.');
        }

        if ($user->two_factor_code !== $request->two_factor_code) {
            $this->logActivity('2fa_failed', "Failed 2FA verification for: {$user->email}");
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid 2FA code.']);
            }
            return back()->with('error', 'Invalid 2FA code.');
        }

        // Clear 2FA code and expiry
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->last_login = now();
        $user->save();

        Auth::login($user);
        $request->session()->forget('2fa_user_id');
        $this->logActivity('2fa_success', "Successful 2FA verification for: {$user->email}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => url('/dashboard')]);
        }
        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->logActivity('logout', "User logged out: {$user->first_name} {$user->last_name} ({$user->email})");
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome')->with('success', 'You have been logged out.');
    }

    public function show2FAForm()
    {
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        return view('auth.2fa-verify');
    }

    public function resend2FACode(Request $request)
    {
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }
        // Generate and send new code...
        $user->two_factor_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->two_factor_expires_at = now()->addMinutes(2);
        $user->save();
        Mail::to($user->email)->send(new TwoFactorCodeMail($user));
        $this->logActivity('2fa_resend', "2FA code resent to: {$user->email}");
        return response()->json(['success' => true, 'message' => 'A new code has been sent to your email.']);
    }

    public function unlockUser($id)
    {
        $user = User::findOrFail($id);
        $user->locked = false;
        $user->login_attempts = 0;
        $user->save();

        return back()->with('crud_success', 'User has been unlocked successfully.');
    }

}
