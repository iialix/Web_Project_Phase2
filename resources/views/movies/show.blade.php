@extends('layouts.app')

@section('title', e($movie->name) . ' - Movie Tracker')

@section('content')
<a href="{{ route('movies.index') }}" class="btn-secondary back-btn">&larr; Back to Movies</a>

<div class="movie-detail">
    <div class="movie-detail-poster">
        <img src="{{ $movie->poster ? asset('storage/uploads/' . $movie->poster) : 'https://via.placeholder.com/300x450?text=No+Poster' }}" alt="{{ e($movie->name) }}">
    </div>
    <div class="movie-detail-info">
        <h2>{{ e($movie->name) }}</h2>
        <p class="categories">{{ e($movie->categories) }}</p>
        <p class="description">{{ e($movie->description) }}</p>
    </div>
</div>

<div class="ratings-section">
    <h3>Ratings & Reviews</h3>
    @if($ratings->count() > 0)
        <div class="average-rating">
            <strong>Average: {{ $averageRating }}/10</strong>
            <span class="review-count">({{ $ratings->count() }} reviews)</span>
        </div>
        @foreach($ratings as $rating)
            <div class="rating-item">
                <div class="rating-header">
                    <span class="username">{{ e($rating->user->UserName) }}</span>
                    <span class="rating-value">{{ $rating->Rating }}/10</span>
                </div>
                @if($rating->Description)
                    <p>{{ e($rating->Description) }}</p>
                @endif
            </div>
        @endforeach
    @else
        <p class="empty-state">No ratings yet. Be the first to rate!</p>
    @endif
</div>
@endsection