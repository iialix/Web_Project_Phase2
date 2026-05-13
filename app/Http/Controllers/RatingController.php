<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * Store a newly created rating (AJAX).
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Please login to rate.'], 401);
        }

        $validated = $request->validate([
            'movieId'     => 'required|integer|exists:movies,id',
            'rating'      => 'required|integer|between:1,10',
            'description' => 'nullable|string|max:1000',
        ]);

        // Check if user already rated this movie
        $existing = Rating::where('MovieID', $validated['movieId'])
            ->where('UserID', Auth::id())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'You have already rated this movie.',
            ], 409);
        }

        $rating = Rating::create([
            'MovieID'     => $validated['movieId'],
            'UserID'      => Auth::id(),
            'Rating'      => $validated['rating'],
            'Description' => $validated['description'] ?? '',
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Rating added successfully.',
            'ratingId' => $rating->id,
        ]);
    }

    /**
     * Update the specified rating (AJAX).
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Please login.'], 401);
        }

        $rating = Rating::findOrFail($id);

        if ($rating->UserID !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'rating'      => 'required|integer|between:1,10',
            'description' => 'nullable|string|max:1000',
        ]);

        $rating->update([
            'Rating'      => $validated['rating'],
            'Description' => $validated['description'] ?? '',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully.',
        ]);
    }

    /**
     * Remove the specified rating (AJAX).
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Please login.'], 401);
        }

        $rating = Rating::findOrFail($id);

        if ($rating->UserID !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $rating->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rating deleted successfully.',
        ]);
    }

    /**
     * Get ratings by movie (AJAX).
     */
    public function byMovie($movieId)
    {
        $ratings = Rating::with('user')
            ->where('MovieID', $movieId)
            ->latest()
            ->get();

        $average = $ratings->count() > 0
            ? round($ratings->avg('Rating'), 1)
            : 0;

        return response()->json([
            'success'       => true,
            'movieId'       => (int) $movieId,
            'totalRatings'  => $ratings->count(),
            'averageRating' => $average,
            'data'          => $ratings,
        ]);
    }

    /**
     * Get ratings by user (AJAX).
     */
    public function byUser($userId)
    {
        $ratings = Rating::with('movie')
            ->where('UserID', $userId)
            ->latest()
            ->get();

        return response()->json([
            'success'      => true,
            'userId'       => (int) $userId,
            'totalRatings' => $ratings->count(),
            'data'         => $ratings,
        ]);
    }
}