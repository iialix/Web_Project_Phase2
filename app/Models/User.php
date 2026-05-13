<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'UserName',
        'Email',
        'Password',
        'BirthDate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'Password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the ratings made by this user.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class, 'UserID');
    }

    /**
     * Get the password for the authentication.
     * Override to use the 'Password' column instead of 'password'.
     */
    public function getAuthPassword()
    {
        return $this->Password;
    }

    /**
     * Get the wishlist entries of this user.
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'UserID');
    }

    /**
     * Check if this user is the admin (first registered user).
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        $firstUser = self::oldest()->first();
        return $firstUser && $this->id === $firstUser->id;
    }
}