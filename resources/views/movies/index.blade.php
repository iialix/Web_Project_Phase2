@extends('layouts.app')

@section('title', 'Movies - MovieTracker')
@section('meta_description', 'Browse all movies on MovieTracker. Search, filter, and discover new films.')

@section('content')
<div class="page-wrapper">
    <div class="section-header">
        <h1 class="section-title">
            @if($query)
                Results for: <span class="gradient-text">"{{ e($query) }}"</span>
            @else
                <span class="gradient-text">All</span> Movies
            @endif
        </h1>
        <div class="header-actions">
            <form action="{{ route('movies.search') }}" method="GET" class="search-bar" id="movie-search-form">
                <input
                    type="text"
                    name="q"
                    id="search-input"
                    placeholder="Search movies or genres…"
                    value="{{ $query ?? '' }}"
                    autocomplete="off"
                >
                <button type="submit" class="btn-primary btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </button>
                @if($query)
                    <a href="{{ route('movies.index') }}" class="btn-ghost btn-sm">Clear</a>
                @endif
            </form>
            @auth
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('movies.create') }}" class="btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Add Movie
                    </a>
                @endif
            @endauth
        </div>
    </div>

    @if($movies->count() > 0)
        <p class="result-count">{{ $movies->count() }} {{ Str::plural('movie', $movies->count()) }} found</p>
        <div class="movies-grid">
            @foreach($movies as $index => $movie)
                <div class="movie-card" style="animation-delay: {{ $index * 0.06 }}s">
                    <a href="{{ route('movies.show', $movie->id) }}" class="card-link" tabindex="-1"></a>

                    <div class="poster-wrapper">
                        <img
                            src="{{ $movie->poster ? asset('uploads/' . $movie->poster) : 'https://placehold.co/300x450/1a1a2e/e94560?text=No+Poster' }}"
                            alt="{{ e($movie->name) }}"
                            loading="lazy"
                        >

                        {{-- Admin badge --}}
                        @auth
                            @if(Auth::user()->isAdmin())
                                <div class="admin-badge">Admin</div>
                            @endif
                        @endauth

                        <div class="card-overlay">
                            <span class="card-genre">{{ e($movie->categories) }}</span>
                            <h3 class="card-title">{{ e($movie->name) }}</h3>
                            <p class="card-desc">{{ Str::limit($movie->description, 80) }}</p>
                            <div class="card-actions">
                                <a href="{{ route('movies.show', $movie->id) }}" class="btn-primary btn-sm">View Details</a>
                                @auth
                                    <button
                                        class="wishlist-btn {{ in_array($movie->id, $wishlistIds->toArray()) ? 'active' : '' }}"
                                        data-movie-id="{{ $movie->id }}"
                                        data-in-wishlist="{{ in_array($movie->id, $wishlistIds->toArray()) ? 'true' : 'false' }}"
                                        onclick="toggleWishlist(this)"
                                        title="{{ in_array($movie->id, $wishlistIds->toArray()) ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                    >{{ in_array($movie->id, $wishlistIds->toArray()) ? '❤' : '♡' }}</button>
                                @endauth
                            </div>
                        </div>
                    </div>

                    {{-- Admin controls outside overlay --}}
                    @auth
                        @if(Auth::user()->isAdmin())
                            <div class="card-admin-bar">
                                <a href="{{ route('movies.edit', $movie->id) }}" class="btn-admin-edit">✏ Edit</a>
                                <button
                                    class="btn-admin-delete"
                                    onclick="confirmDelete({{ $movie->id }}, '{{ addslashes($movie->name) }}')"
                                >🗑 Delete</button>
                            </div>
                        @endif
                    @endauth
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">🎬</div>
            @if($query)
                <h3>No movies found</h3>
                <p>No results for "<strong>{{ e($query) }}</strong>".</p>
                <a href="{{ route('movies.index') }}" class="btn-secondary">Browse All Movies</a>
            @else
                <h3>No movies yet</h3>
                <p>The library is empty. Add the first movie!</p>
                @auth
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('movies.create') }}" class="btn-primary">Add First Movie</a>
                    @endif
                @endauth
            @endif
        </div>
    @endif
</div>

{{-- Delete confirmation modal --}}
<div class="modal-backdrop" id="delete-modal" style="display:none;">
    <div class="modal">
        <h3>Delete Movie?</h3>
        <p>Are you sure you want to delete <strong id="delete-movie-name"></strong>? This cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-danger" id="confirm-delete-btn">Yes, Delete</button>
            <button class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleWishlist(btn) {
    const movieId = btn.dataset.movieId;
    const inList  = btn.dataset.inWishlist === 'true';
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
                showToast('Added to wishlist!', 'success');
            } else {
                showToast(data.error || 'Already in wishlist', 'error');
            }
        });
    }
}

let deleteMovieId = null;
function confirmDelete(id, name) {
    deleteMovieId = id;
    document.getElementById('delete-movie-name').textContent = name;
    document.getElementById('delete-modal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
    deleteMovieId = null;
}

document.getElementById('confirm-delete-btn').addEventListener('click', () => {
    if (!deleteMovieId) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`/movies/${deleteMovieId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            showToast('Movie deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    });
});
</script>
@endpush