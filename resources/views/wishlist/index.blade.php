@extends('layouts.app')

@section('title', 'My Wishlist - MovieTracker')

@section('content')
<div class="page-wrapper">
    <div class="section-header">
        <h1 class="section-title"><span class="gradient-text">My</span> Wishlist</h1>
        <a href="{{ route('movies.index') }}" class="btn-secondary">Browse Movies</a>
    </div>

    @if($wishlistItems->count() > 0)
        <p class="result-count">{{ $wishlistItems->count() }} {{ Str::plural('movie', $wishlistItems->count()) }} saved</p>
        <div class="movies-grid">
            @foreach($wishlistItems as $index => $item)
                <div class="movie-card" style="animation-delay: {{ $index * 0.06 }}s" id="wishlist-card-{{ $item->movie->id }}">
                    <a href="{{ route('movies.show', $item->movie->id) }}" class="card-link" tabindex="-1"></a>

                    <div class="poster-wrapper">
                        <img
                            src="{{ $item->movie->poster ? asset('uploads/' . $item->movie->poster) : 'https://placehold.co/300x450/1a1a2e/e94560?text=No+Poster' }}"
                            alt="{{ e($item->movie->name) }}"
                            loading="lazy"
                        >
                        <div class="card-overlay">
                            <span class="card-genre">{{ e($item->movie->categories) }}</span>
                            <h3 class="card-title">{{ e($item->movie->name) }}</h3>
                            <div class="card-actions">
                                <a href="{{ route('movies.show', $item->movie->id) }}" class="btn-primary btn-sm">View Details</a>
                                <button
                                    class="wishlist-btn active"
                                    data-movie-id="{{ $item->movie->id }}"
                                    onclick="removeFromWishlist({{ $item->movie->id }}, this)"
                                    title="Remove from wishlist"
                                >❤</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">♡</div>
            <h3>Your wishlist is empty</h3>
            <p>Browse movies and click the heart icon to save them here.</p>
            <a href="{{ route('movies.index') }}" class="btn-primary">Browse Movies</a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function removeFromWishlist(movieId, btn) {
    fetch(`/wishlist/${movieId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById(`wishlist-card-${movieId}`);
            if (card) {
                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    showToast('Removed from wishlist', 'info');
                    // Check if empty
                    const grid = document.querySelector('.movies-grid');
                    if (grid && !grid.children.length) location.reload();
                }, 400);
            }
        } else {
            showToast(data.error || 'Could not remove', 'error');
        }
    });
}
</script>
@endpush