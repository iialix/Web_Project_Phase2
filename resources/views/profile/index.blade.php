@extends('layouts.app')

@section('title', 'Profile - Movie Tracker')

@section('content')
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">&#128100;</div>
        <div class="profile-info">
            <h2>{{ e($user->UserName) }}</h2>
            <p>{{ e($user->Email) }}</p>
        </div>
    </div>
    <div class="profile-stats">
        <div class="stat-card">
            <span class="stat-number">{{ $totalRatings }}</span>
            <span class="stat-label">Ratings</span>
        </div>
    </div>
    <h3>My Ratings</h3>
    @if($ratings->count() > 0)
        <div class="ratings-list">
            @foreach($ratings as $rating)
                <div class="rating-item">
                    <div class="rating-header">
                        <span class="username">{{ e($rating->movie->name) }}</span>
                        <span class="rating-value">{{ $rating->Rating }}/10</span>
                    </div>
                    @if($rating->Description)
                        <p>{{ e($rating->Description) }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="empty-state">No ratings yet. Start rating movies!</p>
    @endif
</div>
@endsection