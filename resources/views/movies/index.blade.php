@extends('layouts.app')

@section('title', 'Movies - Movie Tracker')

@section('content')
<div class="section-header">
    <h2>{{ $query ? 'Search Results for: ' . e($query) : 'All Movies' }}</h2>
    <div class="search-bar">
        <form action="{{ route('movies.search') }}" method="GET">
            <input type="text" name="q" placeholder="Search movies..." value="{{ $query ?? '' }}">
            <button type="submit" class="btn-primary">Search</button>
        </form>
    </div>
</div>

@if($movies->count() > 0)
    <div class="movies-grid">
        @foreach($movies as $movie)
            <div class="movie-card" onclick="window.location='{{ route('movies.show', $movie->id) }}'">
                <div class="poster-wrapper">
                    <img src="{{ $movie->poster ? asset('storage/uploads/' . $movie->poster) : 'https://via.placeholder.com/300x450?text=No+Poster' }}" alt="{{ e($movie->name) }}" loading="lazy">
                    <div class="overlay">
                        <h3>{{ e($movie->name) }}</h3>
                        <p class="categories">{{ e($movie->categories) }}</p>
                        <div class="card-actions">
                            <a href="{{ route('movies.show', $movie->id) }}" class="btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="empty-state">
        @if($query)
            No movies found matching "{{ e($query) }}".
        @else
            No movies found. 
            @auth
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('movies.create') }}">Add the first movie!</a>
                @endif
            @endauth
        @endif
    </p>
@endif
@endsection