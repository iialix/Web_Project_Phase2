<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'MovieID',
        'UserID',
        'Rating',
        'Description',
    ];

    /**
     * Get the movie that this rating belongs to.
     */
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'MovieID');
    }

    /**
     * Get the user that made this rating.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID');
    }
}