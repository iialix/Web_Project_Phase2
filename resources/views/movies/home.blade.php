@extends('layouts.app')

@section('title', 'Home - MovieTracker')
@section('meta_description', 'Discover, rate, and track your favorite movies on MovieTracker.')

@section('content')

{{-- ─── HERO ──────────────────────────────────────────── --}}
<section class="hero">
    <div class="hero-bg-shapes">
        <div class="hero-shape shape-1"></div>
        <div class="hero-shape shape-2"></div>
        <div class="hero-shape shape-3"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">🎬 Now Streaming</div>
        <h1 class="hero-title">Welcome to <span class="gradient-text">MovieTracker</span></h1>
        <p class="hero-subtitle">Discover, rate, and keep track of all your favorite films in one beautiful place.</p>
        <div class="hero-actions">
            <a href="{{ route('movies.index') }}" class="btn-primary">
                <span>Browse Movies</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            @guest
                <a href="{{ route('signup') }}" class="btn-secondary">Join Free</a>
            @endguest
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="stat-val">{{ \App\Models\Movie::count() }}</span>
                <span class="stat-lbl">Movies</span>
            </div>
            <div class="hero-stat">
                <span class="stat-val">{{ \App\Models\Rating::count() }}</span>
                <span class="stat-lbl">Reviews</span>
            </div>
            <div class="hero-stat">
                <span class="stat-val">{{ \App\Models\User::count() }}</span>
                <span class="stat-lbl">Members</span>
            </div>
        </div>
    </div>
</section>

{{-- ─── FEATURED MOVIES ──────────────────────────────── --}}
<section class="section" style="padding: 2rem 2rem 4rem;">
    <div class="section-header">
        <h2 class="section-title">
            @if($query)
                Search Results for: <span class="gradient-text">{{ e($query) }}</span>
            @else
                <span class="gradient-text">Featured</span> Movies
            @endif
        </h2>
        <form action="{{ route('movies.search') }}" method="GET" class="search-bar">
            <input type="text" name="q" placeholder="Search movies or genres…" value="{{ $query ?? '' }}" id="hero-search">
            <button type="submit" class="btn-primary btn-sm">Search</button>
        </form>
    </div>

    @if($movies->count() > 0)
        <div class="movies-grid">
            @foreach($movies->take(10) as $index => $movie)
                <div class="movie-card" style="animation-delay: {{ $index * 0.07 }}s">
                    <a href="{{ route('movies.show', $movie->id) }}" class="card-link" tabindex="-1"></a>

                    <div class="poster-wrapper">
                        <img
                            src="{{ $movie->poster ? asset('uploads/' . $movie->poster) : 'https://placehold.co/300x450/1a1a2e/e94560?text=No+Poster' }}"
                            alt="{{ e($movie->name) }}"
                            loading="lazy"
                        >
                        <div class="card-overlay">
                            <span class="card-genre">{{ e($movie->categories) }}</span>
                            <h3 class="card-title">{{ e($movie->name) }}</h3>
                            <div class="card-actions">
                                <a href="{{ route('movies.show', $movie->id) }}" class="btn-primary btn-sm">View Details</a>
                                @auth
                                    <button
                                        class="wishlist-btn {{ in_array($movie->id, $wishlistIds->toArray()) ? 'active' : '' }}"
                                        data-movie-id="{{ $movie->id }}"
                                        data-in-wishlist="{{ in_array($movie->id, $wishlistIds->toArray()) ? 'true' : 'false' }}"
                                        title="{{ in_array($movie->id, $wishlistIds->toArray()) ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                        onclick="toggleWishlist(this)"
                                    >{{ in_array($movie->id, $wishlistIds->toArray()) ? '❤' : '♡' }}</button>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($movies->count() > 10)
            <div class="text-center mt-4">
                <a href="{{ route('movies.index') }}" class="btn-secondary">View All {{ $movies->count() }} Movies →</a>
            </div>
        @endif
    @else
        <div class="empty-state">
            <div class="empty-icon">🎬</div>
            @if($query)
                <p>No movies found for "<strong>{{ e($query) }}</strong>".</p>
                <a href="{{ route('home') }}" class="btn-secondary">Clear Search</a>
            @else
                <p>No movies yet. Be the first to add one!</p>
                @auth
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('movies.create') }}" class="btn-primary">Add Movie</a>
                    @endif
                @endauth
            @endif
        </div>
    @endif
</section>

@endsection

@push('scripts')
<script>
function toggleWishlist(btn) {
    const movieId  = btn.dataset.movieId;
    const inList   = btn.dataset.inWishlist === 'true';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    if (inList) {
        fetch(`/wishlist/${movieId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.textContent = '♡';
                btn.classList.remove('active');
                btn.dataset.inWishlist = 'false';
                btn.title = 'Add to wishlist';
                showToast('Removed from wishlist', 'info');
            }
        });
    } else {
        fetch('/wishlist', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ movieId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.textContent = '❤';
                btn.classList.add('active');
                btn.dataset.inWishlist = 'true';
                btn.title = 'Remove from wishlist';
                showToast('Added to wishlist!', 'success');
            } else {
                showToast(data.error || 'Could not add to wishlist', 'error');
            }
        });
    }
}
</script>
@endpush
