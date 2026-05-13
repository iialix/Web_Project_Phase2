<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'MovieID',
        'UserID',
    ];

    /**
     * Get the movie in this wishlist entry.
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'MovieID');
    }

    /**
     * Get the user who owns this wishlist entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID');
    }
}