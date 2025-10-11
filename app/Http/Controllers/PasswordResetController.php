<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Traits\LogsActivity;


class PasswordResetController extends Controller
{
    use LogsActivity;

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            $this->logActivity('password_reset_requested', "Password reset link requested for: {$request->email}");
            return back()->with('success', 'Password reset link sent to your email.');
        } else {
            $this->logActivity('password_reset_failed', "Failed password reset request for: {$request->email}");
            return back()->withErrors(['email' => 'Failed to send reset link.']);
        }
    }

    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }
    
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required'
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'The new password must be different from your current password.'])->withInput();
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation','token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($user) {
                $this->logActivity('password_reset_success', "Password successfully reset for: {$request->email}", $user->id);
            } else {
                $this->logActivity('password_reset_success', "Password successfully reset for: {$request->email}");
            }
            return redirect('/login')->with('crud_success', 'Password has been reset successfully.');
        } else {
            if ($user) {
                $this->logActivity('password_reset_failed', "Failed password reset attempt for: {$request->email}", $user->id);
            } else {
                $this->logActivity('password_reset_failed', "Failed password reset attempt for: {$request->email}");
            }
            return back()->withErrors(['email' => 'Invalid or expired token.']);
        }
    }
}
