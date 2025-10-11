<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;

class TwoFactorAuthController extends Controller
{
    public function verifyForm()
    {
        return view('auth.two-factor');
    }
        public function verify(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|integer',
        ]);

        $userId = $request->session()->get('2fa_user_id');
        
        if (!$userId) {
            return redirect()->route('login')->with('crud_error', 'No active login attempt found.');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        if ($request->input('two_factor_code') == $user->two_factor_code && now()->lt($user->two_factor_expires_at)) {
            $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
            Auth::login($user);
            $request->session()->forget('2fa_user_id');
            return redirect('/dashboard');
        }

        return back()->withErrors(['two_factor_code' => 'Invalid or expired OTP.']);
    }

}
