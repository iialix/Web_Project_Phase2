<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Rating;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    /**
     * Display a listing of all movies (homepage + movies index).
     */
    public function index(Request $request)
    {
        $query = $request->query('q');
        
        if ($query) {
            $movies = Movie::search($query)->latest()->get();
        } else {
            $movies = Movie::latest()->get();
        }

        $wishlistIds = collect([]);
        if (Auth::check()) {
            $wishlistIds = Wishlist::where('UserID', Auth::id())
                ->pluck('MovieID')
                ->map(fn($id) => (int) $id);
        }

        return view('movies.index', compact('movies', 'wishlistIds', 'query'));
    }

    /**
     * Show the form for creating a new movie.
     */
    public function create()
    {
        if (!Auth::check()) {
            return redirect()->route('movies.index');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('movies.index')
                ->with('error', 'Only admin can add movies.');
        }

        return view('movies.create');
    }

    /**
     * Store a newly created movie in the database.
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('movies.index');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('movies.index')
                ->with('error', 'Only admin can add movies.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'categories'  => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'poster'      => 'nullable|image|mimes:jpeg,png,webp|max:5120',
        ]);

        // Handle poster: uploaded file, TMDB URL, or none
        $posterFilename = null;

        if ($request->hasFile('poster')) {
            $file = $request->file('poster');
            $posterFilename = 'poster_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();
            $file->move(public_path('uploads'), $posterFilename);
        } elseif ($request->filled('tmdb_poster_url')) {
            $posterFilename = $this->downloadTmdbPoster($request->input('tmdb_poster_url'));
        }

        $movie = Movie::create([
            'name'        => $validated['name'],
            'categories'  => $validated['categories'],
            'description' => $validated['description'],
            'poster'      => $posterFilename,
        ]);

        return redirect()->route('movies.show', $movie->id)
            ->with('success', 'Movie added successfully!');
    }

    /**
     * Display the specified movie with its ratings.
     */
    public function show($id)
    {
        $movie = Movie::findOrFail($id);
        $ratings = Rating::with('user')
            ->where('MovieID', $id)
            ->latest()
            ->get();

        $averageRating = $ratings->count() > 0
            ? round($ratings->avg('Rating'), 1)
            : 0;

        $isInWishlist = false;
        $userRating = null;

        if (Auth::check()) {
            $isInWishlist = Wishlist::where('MovieID', $id)
                ->where('UserID', Auth::id())
                ->exists();

            $userRating = Rating::where('MovieID', $id)
                ->where('UserID', Auth::id())
                ->first();
        }

        return view('movies.show', compact(
            'movie', 'ratings', 'averageRating', 'isInWishlist', 'userRating'
        ));
    }

    /**
     * Show the form for editing the specified movie.
     */
    public function edit($id)
    {
        if (!Auth::check()) {
            return redirect()->route('movies.index');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('movies.index')
                ->with('error', 'Only admin can edit movies.');
        }

        $movie = Movie::findOrFail($id);
        return view('movies.edit', compact('movie'));
    }

    /**
     * Update the specified movie in the database.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('movies.index');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('movies.index')
                ->with('error', 'Only admin can update movies.');
        }

        $movie = Movie::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'categories'  => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'poster'      => 'nullable|image|mimes:jpeg,png,webp|max:5120',
        ]);

        // Handle poster: uploaded file or keep existing
        $posterFilename = $movie->poster;

        if ($request->hasFile('poster')) {
            $file = $request->file('poster');
            $posterFilename = 'poster_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();
            $file->move(public_path('uploads'), $posterFilename);
        }

        $movie->update([
            'name'        => $validated['name'],
            'categories'  => $validated['categories'],
            'description' => $validated['description'],
            'poster'      => $posterFilename,
        ]);

        return redirect()->route('movies.show', $id)
            ->with('success', 'Movie updated successfully!');
    }

    /**
     * Remove the specified movie from the database.
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Please login.']);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Only admin can delete movies.']);
        }

        $movie = Movie::findOrFail($id);
        
        // Delete related ratings and wishlist entries (cascade handles DB, but we also delete the poster file)
        Rating::where('MovieID', $id)->delete();
        Wishlist::where('MovieID', $id)->delete();
        $movie->delete();

        return response()->json(['success' => true, 'message' => 'Movie deleted successfully.']);
    }

    /**
     * Search movies via AJAX (for the search bar).
     */
    public function search(Request $request)
    {
        $query = $request->query('q');
        
        if (empty($query)) {
            return redirect()->route('movies.index');
        }

        $movies = Movie::search($query)->latest()->get();

        $wishlistIds = collect([]);
        if (Auth::check()) {
            $wishlistIds = Wishlist::where('UserID', Auth::id())
                ->pluck('MovieID')
                ->map(fn($id) => (int) $id);
        }

        return view('movies.index', compact('movies', 'wishlistIds', 'query'));
    }

    /**
     * Download a poster image from a TMDB URL and store it locally.
     */
    private function downloadTmdbPoster(string $url): ?string
    {
        if (!preg_match('/^https:\/\/image\.tmdb\.org\/t\/p\//', $url)) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || !$imageData) {
            return null;
        }

        $ext = 'jpg';
        if (strpos($contentType, 'image/png') !== false) {
            $ext = 'png';
        } elseif (strpos($contentType, 'image/webp') !== false) {
            $ext = 'webp';
        }

        $filename = 'poster_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $uploadDir = public_path('uploads');
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $filename, $imageData);

        return $filename;
    }
}