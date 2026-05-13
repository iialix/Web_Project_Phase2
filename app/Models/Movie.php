<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'categories',
        'description',
        'poster',
    ];

    /**
     * Get the ratings for this movie.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class, 'MovieID');
    }

    /**
     * Get the wishlist entries for this movie.
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'MovieID');
    }

    /**
     * Scope a query to search movies by name or categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $term)
    {
        $term = "%{$term}%";
        return $query->where('name', 'like', $term)
                     ->orWhere('categories', 'like', $term);
    }
}