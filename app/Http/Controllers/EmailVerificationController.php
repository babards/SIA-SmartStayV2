<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\LogsActivity;

class EmailVerificationController extends Controller
{
    use LogsActivity;

    public function verifyEmail($token)
    {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            $this->logActivity('email_verification_failed', "Failed email verification attempt with invalid token");
            return redirect()->route('login')->with('error', 'Invalid verification token.');
        }

        $user->is_verified = 1;
        $user->verification_token = null;
        $user->save();

        $this->logActivity('email_verification_success', "Email verified for: {$user->email}");
        return redirect()->route('login')->with('crud_success', 'Email verified successfully. You can now login.');
    }
}
