<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TMDBController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ─── Home ─────────────────────────────────────────────
Route::get('/', [MovieController::class, 'index'])->name('home');

// ─── Movies ────────────────────────────────────────────
Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/create', [MovieController::class, 'create'])->name('movies.create');
Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
Route::get('/movies/search', [MovieController::class, 'search'])->name('movies.search');
Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/movies/{movie}/edit', [MovieController::class, 'edit'])->name('movies.edit');
Route::put('/movies/{movie}', [MovieController::class, 'update'])->name('movies.update');
Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');

// ─── Auth ─────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/signup', [AuthController::class, 'showSignupForm'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Profile ──────────────────────────────────────────
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

// ─── Wishlist ─────────────────────────────────────────
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
Route::delete('/wishlist/{movie}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

// ─── Ratings (AJAX) ───────────────────────────────────
Route::post('/ratings', [RatingController::class, 'store'])->name('ratings.store');
Route::put('/ratings/{rating}', [RatingController::class, 'update'])->name('ratings.update');
Route::delete('/ratings/{rating}', [RatingController::class, 'destroy'])->name('ratings.destroy');
Route::get('/ratings/movie/{movie}', [RatingController::class, 'byMovie'])->name('ratings.byMovie');
Route::get('/ratings/user/{user}', [RatingController::class, 'byUser'])->name('ratings.byUser');

// ─── TMDB API (AJAX) ──────────────────────────────────
Route::get('/api/tmdb/search', [TMDBController::class, 'search'])->name('api.tmdb.search');
Route::get('/api/tmdb/{id}', [TMDBController::class, 'detail'])->name('api.tmdb.detail');

// ─── Upload (AJAX) ────────────────────────────────────
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// ─── Admin Check (AJAX) ───────────────────────────────
Route::get('/api/admin-check', [AuthController::class, 'checkAdmin'])->name('api.admin.check');