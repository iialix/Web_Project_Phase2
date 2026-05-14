@extends('layouts.app')

@section('title', 'Profile - MovieTracker')

@section('content')
<div class="page-wrapper">

    {{-- ─── Profile Header ─────────────────────────── --}}
    <div class="profile-hero">
        <div class="profile-avatar-lg">{{ strtoupper(substr($user->UserName, 0, 1)) }}</div>
        <div class="profile-hero-info">
            <h1 class="profile-name">{{ e($user->UserName) }}</h1>
            <p class="profile-email">
                <span class="profile-email-icon">✉</span> {{ e($user->Email) }}
            </p>
            @if($user->isAdmin())
                <span class="admin-tag">👑 Admin</span>
            @endif
        </div>
    </div>

    {{-- ─── Stats ───────────────────────────────────── --}}
    <div class="profile-stats-grid">
        <div class="stat-card">
            <span class="stat-icon">⭐</span>
            <span class="stat-number">{{ $totalRatings }}</span>
            <span class="stat-label">Total Ratings</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon">🎬</span>
            <span class="stat-number">
                {{ $ratings->avg('Rating') ? number_format($ratings->avg('Rating'), 1) : '—' }}
            </span>
            <span class="stat-label">Avg. Rating</span>
        </div>
        <div class="stat-card">
            <span class="stat-icon">📅</span>
            <span class="stat-number">{{ $user->created_at ? $user->created_at->format('M Y') : 'N/A' }}</span>
            <span class="stat-label">Member Since</span>
        </div>
    </div>

    {{-- ─── My Ratings ─────────────────────────────── --}}
    <div class="section-header" style="margin-top: 3rem;">
        <h2 class="section-title">My <span class="gradient-text">Reviews</span></h2>
        <a href="{{ route('movies.index') }}" class="btn-secondary btn-sm">Browse Movies</a>
    </div>

    @if($ratings->count() > 0)
        <div class="ratings-list">
            @foreach($ratings as $rating)
                <div class="rating-item profile-rating-item" id="profile-rating-{{ $rating->id }}">
                    <div class="profile-rating-poster">
                        <img
                            src="{{ $rating->movie->poster ? asset('storage/uploads/' . $rating->movie->poster) : 'https://placehold.co/80x120/1a1a2e/e94560?text=?' }}"
                            alt="{{ e($rating->movie->name) }}"
                        >
                    </div>
                    <div class="profile-rating-content">
                        <div class="rating-header">
                            <div>
                                <a href="{{ route('movies.show', $rating->movie->id) }}" class="rating-movie-title">
                                    {{ e($rating->movie->name) }}
                                </a>
                                <span class="rating-date">{{ $rating->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="rating-score">
                                <span class="score-number">{{ $rating->Rating }}</span>
                                <span class="score-denom">/10</span>
                            </div>
                        </div>
                        <div class="rating-stars-row">
                            @for($i=1; $i<=10; $i++)
                                <span style="color: {{ $i <= $rating->Rating ? 'var(--accent)' : 'rgba(255,255,255,0.15)' }}; font-size: 1.1rem;">★</span>
                            @endfor
                        </div>
                        @if($rating->Description)
                            <p class="rating-description">{{ e($rating->Description) }}</p>
                        @endif
                        <div class="rating-actions">
                            <a href="{{ route('movies.show', $rating->movie->id) }}" class="btn-text">View Movie →</a>
                            <button class="btn-text btn-danger-text" onclick="deleteProfileRating({{ $rating->id }})">🗑 Delete</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">⭐</div>
            <h3>No reviews yet</h3>
            <p>Start rating movies and your reviews will appear here.</p>
            <a href="{{ route('movies.index') }}" class="btn-primary">Explore Movies</a>
        </div>
    @endif

    {{-- ─── Danger Zone ────────────────────────────── --}}
    <div class="danger-zone">
        <h3>Account</h3>
        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-danger">Logout</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function deleteProfileRating(ratingId) {
    if (!confirm('Delete this review?')) return;
    fetch(`/ratings/${ratingId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById(`profile-rating-${ratingId}`);
            if (el) {
                el.style.transition = 'opacity 0.3s, transform 0.3s';
                el.style.opacity = '0';
                el.style.transform = 'translateX(-20px)';
                setTimeout(() => { el.remove(); showToast('Review deleted', 'info'); }, 300);
            }
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    });
}
</script>
@endpush