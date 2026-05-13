<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display the user's wishlist.
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to view your wishlist.');
        }

        $wishlistItems = Wishlist::with('movie')
            ->where('UserID', Auth::id())
            ->latest()
            ->get();

        return view('wishlist.index', compact('wishlistItems'));
    }

    /**
     * Add a movie to the wishlist (AJAX).
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Please login.'], 401);
        }

        $validated = $request->validate([
            'movieId' => 'required|integer|exists:movies,id',
        ]);

        // Check if already in wishlist
        $existing = Wishlist::where('MovieID', $validated['movieId'])
            ->where('UserID', Auth::id())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'Movie is already in your wishlist.',
            ], 409);
        }

        $wishlist = Wishlist::create([
            'MovieID' => $validated['movieId'],
            'UserID'  => Auth::id(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Added to wishlist successfully.',
            'wishlistId' => $wishlist->id,
        ]);
    }

    /**
     * Remove a movie from the wishlist (AJAX).
     */
    public function destroy($movieId)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Please login.'], 401);
        }

        $deleted = Wishlist::where('MovieID', $movieId)
            ->where('UserID', Auth::id())
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Removed from wishlist successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Movie not found in your wishlist.',
        ], 404);
    }
}