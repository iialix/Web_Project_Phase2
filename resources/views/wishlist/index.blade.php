@extends('layouts.app')

@section('title', 'Wishlist - Movie Tracker')

@section('content')
<div class="section-header">
    <h2>My Wishlist</h2>
    <a href="{{ route('movies.index') }}" class="btn-secondary">Browse Movies</a>
</div>

@if($wishlistItems->count() > 0)
    <div class="movies-grid">
        @foreach($wishlistItems as $item)
            <div class="movie-card" onclick="window.location='{{ route('movies.show', $item->movie->id) }}'">
                <div class="poster-wrapper">
                    <img src="{{ $item->movie->poster ? asset('storage/uploads/' . $item->movie->poster) : 'https://via.placeholder.com/300x450?text=No+Poster' }}" alt="{{ e($item->movie->name) }}" loading="lazy">
                    <div class="overlay">
                        <h3>{{ e($item->movie->name) }}</h3>
                        <p class="categories">{{ e($item->movie->categories) }}</p>
                        <div class="card-actions">
                            <a href="{{ route('movies.show', $item->movie->id) }}" class="btn-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="empty-state">Your wishlist is empty. Start adding movies!</p>
@endif
@endsection