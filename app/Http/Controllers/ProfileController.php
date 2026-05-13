<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the user's profile with their ratings.
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to view your profile.');
        }

        $user = Auth::user();
        $ratings = Rating::with('movie')
            ->where('UserID', $user->id)
            ->latest()
            ->get();

        $totalRatings = $ratings->count();

        return view('profile.index', compact('user', 'ratings', 'totalRatings'));
    }
}