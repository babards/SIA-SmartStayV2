<?php

namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = 'users';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar',
        'role',
        'is_verified',
        'verification_token',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Relationship to properties they own (as landlord)
    public function properties()
    {
        return $this->hasMany(Property::class, 'userID');
    }

    // Relationship to applications they made (as tenant)
    public function applications()
    {
        return $this->hasMany(PropertyApplication::class, 'user_id');
    }

    // Get the avatar URL or default placeholder
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            // Check if file exists in storage/app/public/avatars
            if (file_exists(storage_path('app/public/avatars/' . $this->avatar))) {
                // First try the standard storage symlink
                if (file_exists(public_path('storage/avatars/' . $this->avatar))) {
                    return asset('storage/avatars/' . $this->avatar);
                }
                // Fallback to direct avatar serving route
                return route('avatars.serve', ['filename' => $this->avatar]);
            }
        }
        return null;
    }
}
