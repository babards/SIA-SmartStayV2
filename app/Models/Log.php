<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
    ];

    // Actions that happen before authentication
    const PRE_AUTH_ACTIONS = [
        'register',
        'email_verification_success',
        'email_verification_failed',
        'login_failed',
        'login_locked',
        'login_2fa_required',
        '2fa_failed',
        'password_reset_requested',
        'password_reset_failed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get display name based on authentication status
     * Shows "Guest" for pre-authentication actions, actual user for authenticated actions
     */
    public function getDisplayUserAttribute()
    {
        // If action is pre-authentication, show as Guest
        if (in_array($this->action, self::PRE_AUTH_ACTIONS)) {
            return 'Guest';
        }

        // For authenticated actions, show actual user name
        if ($this->user) {
            return $this->user->first_name . ' ' . $this->user->last_name;
        }

        return 'Guest';
    }

    /**
     * Check if this log entry is from an authenticated session
     */
    public function isAuthenticated()
    {
        return !in_array($this->action, self::PRE_AUTH_ACTIONS);
    }

} 