@extends('layouts.app')

@section('title', e($movie->name) . ' - MovieTracker')
@section('meta_description', Str::limit($movie->description, 155))

@section('content')
<div class="page-wrapper">

    {{-- Back button --}}
    <a href="{{ route('movies.index') }}" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Movies
    </a>

    {{-- ─── Movie Detail ─────────────────────────────────── --}}
    <div class="movie-detail">
        <div class="movie-detail-poster">
            <img
                src="{{ $movie->poster ? asset('uploads/' . $movie->poster) : 'https://placehold.co/400x600/1a1a2e/e94560?text=No+Poster' }}"
                alt="{{ e($movie->name) }}"
            >
        </div>

        <div class="movie-detail-info">
            <div class="detail-meta">
                <span class="detail-genre-badge">{{ e($movie->categories) }}</span>
                @if($averageRating > 0)
                    <span class="detail-rating-badge">
                        ⭐ {{ $averageRating }} / 10
                        <span class="rating-count">({{ $ratings->count() }} {{ Str::plural('review', $ratings->count()) }})</span>
                    </span>
                @endif
            </div>

            <h1 class="detail-title">{{ e($movie->name) }}</h1>
            <p class="detail-description">{{ e($movie->description) }}</p>

            <div class="detail-actions">
                @auth
                    {{-- Wishlist --}}
                    <button
                        id="wishlist-btn-detail"
                        class="btn-secondary wishlist-btn-detail {{ $isInWishlist ? 'active' : '' }}"
                        data-movie-id="{{ $movie->id }}"
                        data-in-wishlist="{{ $isInWishlist ? 'true' : 'false' }}"
                        onclick="toggleWishlistDetail(this)"
                    >
                        <span class="wl-icon">{{ $isInWishlist ? '❤' : '♡' }}</span>
                        <span class="wl-text">{{ $isInWishlist ? 'In Wishlist' : 'Add to Wishlist' }}</span>
                    </button>

                    @if(!$userRating)
                        <button class="btn-primary" onclick="scrollToRatingForm()">
                            ⭐ Rate Movie
                        </button>
                    @else
                        <button class="btn-primary" onclick="scrollToMyRating()">
                            ⭐ View My Rating
                        </button>
                    @endif

                    {{-- Admin controls --}}
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('movies.edit', $movie->id) }}" class="btn-secondary">✏ Edit</a>
                        <button class="btn-danger" onclick="confirmDelete({{ $movie->id }}, '{{ addslashes($movie->name) }}')">
                            🗑 Delete
                        </button>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn-primary">Login to Rate</a>
                    <a href="{{ route('login') }}" class="btn-secondary">♡ Add to Wishlist</a>
                @endauth
            </div>

            {{-- TMDB Info Panel --}}
            <div class="tmdb-info-panel" id="tmdb-info-panel" style="display:none;">
                <div class="tmdb-info-loading">
                    <div class="spinner-sm"></div>
                    <span>Loading TMDB info…</span>
                </div>
                <div class="tmdb-info-content" id="tmdb-info-content" style="display:none;"></div>
                <p class="tmdb-error" id="tmdb-error" style="display:none; color: var(--text-secondary); font-size: 0.9rem;">
                    ⚠ TMDB info unavailable right now.
                </p>
            </div>
            <button class="btn-ghost btn-sm mt-2" onclick="loadTMDBInfo('{{ addslashes($movie->name) }}')" id="tmdb-load-btn">
                🌐 Load TMDB Info
            </button>
        </div>
    </div>

    {{-- ─── Ratings Section ─────────────────────────────── --}}
    <section class="ratings-section" id="ratings-section">
        <div class="section-header">
            <h2 class="section-title">Ratings <span class="gradient-text">&amp; Reviews</span></h2>
            @if($averageRating > 0)
                <div class="avg-rating-display">
                    @php $stars = round($averageRating); @endphp
                    <span class="stars-display">
                        @for($i=1; $i<=10; $i++)
                            <span style="color: {{ $i <= $stars ? 'var(--accent)' : 'var(--text-secondary)' }}; font-size: 1.4rem;">★</span>
                        @endfor
                    </span>
                    <span class="avg-number">{{ $averageRating }}/10</span>
                    <span class="avg-count">({{ $ratings->count() }} {{ Str::plural('review', $ratings->count()) }})</span>
                </div>
            @endif
        </div>

        {{-- ─── Add Rating Form ─────────────────────────── --}}
        @auth
            @if(!$userRating)
                <div class="rating-form-container" id="rating-form-anchor">
                    <h3>Leave Your Review</h3>
                    <form action="{{ route('ratings.store') }}" method="POST" id="rating-submit-form">
                        @csrf
                        <input type="hidden" name="movieId" value="{{ $movie->id }}">

                        <div class="form-group">
                            <label class="rating-label">Your Rating (1–10)</label>
                            <div class="star-rating" id="star-rating-widget">
                                @for($i=10; $i>=1; $i--)
                                    <input type="radio" id="star{{ $i }}" name="rating" value="{{ $i }}" {{ old('rating') == $i ? 'checked' : '' }}>
                                    <label for="star{{ $i }}" title="{{ $i }}/10">★</label>
                                @endfor
                            </div>
                            <span class="rating-value-display" id="selected-rating-display">Click a star to rate</span>
                            @error('rating') <span class="error-msg active">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="description" class="rating-label">Review (optional)</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Share your thoughts about this movie…"
                                maxlength="1000"
                            >{{ old('description') }}</textarea>
                            @error('description') <span class="error-msg active">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn-primary" id="submit-rating-btn">Submit Review</button>
                    </form>
                </div>
            @endif
        @endauth

        {{-- ─── Ratings List ─────────────────────────────── --}}
        @if($ratings->count() > 0)
            <div class="ratings-list">
                @foreach($ratings as $rating)
                    @php $isOwn = Auth::check() && Auth::id() === $rating->UserID; @endphp
                    <div class="rating-item {{ $isOwn ? 'my-rating' : '' }}" id="rating-{{ $rating->id }}">
                        <div class="rating-header">
                            <div class="rating-user-info">
                                <span class="rating-avatar">{{ strtoupper(substr($rating->user->UserName, 0, 1)) }}</span>
                                <div>
                                    <span class="username">{{ e($rating->user->UserName) }}</span>
                                    @if($isOwn)<span class="own-badge">You</span>@endif
                                    <span class="rating-date">{{ $rating->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="rating-score">
                                <span class="score-number">{{ $rating->Rating }}</span>
                                <span class="score-denom">/10</span>
                            </div>
                        </div>

                        <div class="rating-stars-row">
                            @for($i=1; $i<=10; $i++)
                                <span style="color: {{ $i <= $rating->Rating ? 'var(--accent)' : 'rgba(255,255,255,0.2)' }}">★</span>
                            @endfor
                        </div>

                        @if($rating->Description)
                            <p class="rating-description">{{ e($rating->Description) }}</p>
                        @endif

                        @if($isOwn)
                            <div class="rating-actions">
                                <button class="btn-text" onclick="openEditModal({{ $rating->id }}, {{ $rating->Rating }}, `{{ addslashes($rating->Description ?? '') }}`)">
                                    ✏ Edit
                                </button>
                                <button class="btn-text btn-danger-text" onclick="deleteRating({{ $rating->id }})">
                                    🗑 Delete
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state compact">
                <p>No reviews yet. Be the first to share your thoughts!</p>
            </div>
        @endif
    </section>
</div>

{{-- ─── Edit Rating Modal ───────────────────────────────── --}}
<div class="modal-backdrop" id="edit-rating-modal" style="display:none;">
    <div class="modal">
        <h3>Edit Your Review</h3>
        <form id="edit-rating-form">
            @csrf
            <input type="hidden" id="edit-rating-id">
            <div class="form-group">
                <label class="rating-label">Rating (1–10)</label>
                <div class="star-rating" id="edit-star-widget">
                    @for($i=10; $i>=1; $i--)
                        <input type="radio" id="edit-star{{ $i }}" name="edit-rating" value="{{ $i }}">
                        <label for="edit-star{{ $i }}" title="{{ $i }}/10">★</label>
                    @endfor
                </div>
                <span class="rating-value-display" id="edit-rating-display">Select rating</span>
            </div>
            <div class="form-group">
                <label class="rating-label">Review</label>
                <textarea id="edit-description" rows="4" placeholder="Edit your review…" maxlength="1000"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-primary" onclick="submitEditRating()">Save Changes</button>
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete modal --}}
<div class="modal-backdrop" id="delete-movie-modal" style="display:none;">
    <div class="modal">
        <h3>Delete Movie?</h3>
        <p>Are you sure you want to delete <strong id="delete-movie-name"></strong>?</p>
        <div class="modal-actions">
            <button class="btn-danger" id="confirm-delete-btn">Yes, Delete</button>
            <button class="btn-secondary" onclick="document.getElementById('delete-movie-modal').style.display='none'">Cancel</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const MOVIE_ID = {{ $movie->id }};
const CSRF    = document.querySelector('meta[name="csrf-token"]').content;

// ── Scroll helpers ────────────────────────────
function scrollToRatingForm() {
    const el = document.getElementById('rating-form-anchor');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function scrollToMyRating() {
    const el = document.querySelector('.rating-item.my-rating');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ── Star rating display update ────────────────
document.querySelectorAll('#star-rating-widget input').forEach(inp => {
    inp.addEventListener('change', () => {
        document.getElementById('selected-rating-display').textContent = `You selected: ${inp.value}/10 ⭐`;
    });
});
document.querySelectorAll('#edit-star-widget input').forEach(inp => {
    inp.addEventListener('change', () => {
        document.getElementById('edit-rating-display').textContent = `${inp.value}/10 ⭐`;
    });
});

// ── Rating form submission (AJAX) ────────────
document.getElementById('rating-submit-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const selected = document.querySelector('#star-rating-widget input:checked');
    if (!selected) {
        showToast('Please select a rating before submitting.', 'error');
        return;
    }

    const submitButton = document.getElementById('submit-rating-btn');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    }

    const formData = {
        movieId: MOVIE_ID,
        rating: selected.value,
        description: document.getElementById('description')?.value || ''
    };

    try {
        const response = await fetch('/ratings', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showToast(data.message || 'Rating added successfully.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 400);
        } else {
            showToast(data.error || 'Failed to add rating.', 'error');
        }
    } catch (error) {
        showToast('Unable to submit rating. Please try again.', 'error');
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Submit Review';
        }
    }
});

// ── Wishlist toggle ───────────────────────────
function toggleWishlistDetail(btn) {
    const inList = btn.dataset.inWishlist === 'true';
    if (inList) {
        fetch(`/wishlist/${MOVIE_ID}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.dataset.inWishlist = 'false';
                btn.querySelector('.wl-icon').textContent = '♡';
                btn.querySelector('.wl-text').textContent = 'Add to Wishlist';
                btn.classList.remove('active');
                showToast('Removed from wishlist', 'info');
            }
        });
    } else {
        fetch('/wishlist', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ movieId: MOVIE_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.dataset.inWishlist = 'true';
                btn.querySelector('.wl-icon').textContent = '❤';
                btn.querySelector('.wl-text').textContent = 'In Wishlist';
                btn.classList.add('active');
                showToast('Added to wishlist!', 'success');
            } else {
                showToast(data.error || 'Could not add', 'error');
            }
        });
    }
}

// ── Delete rating ─────────────────────────────
function deleteRating(ratingId) {
    if (!confirm('Delete your review?')) return;
    fetch(`/ratings/${ratingId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`rating-${ratingId}`)?.remove();
            showToast('Review deleted', 'info');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    });
}

// ── Edit rating modal ─────────────────────────
function openEditModal(id, rating, description) {
    document.getElementById('edit-rating-id').value = id;
    document.getElementById('edit-description').value = description;
    const radio = document.getElementById(`edit-star${rating}`);
    if (radio) { radio.checked = true; }
    document.getElementById('edit-rating-display').textContent = `${rating}/10 ⭐`;
    document.getElementById('edit-rating-modal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('edit-rating-modal').style.display = 'none';
}
function submitEditRating() {
    const id          = document.getElementById('edit-rating-id').value;
    const selectedStar = document.querySelector('#edit-star-widget input:checked');
    if (!selectedStar) { showToast('Select a rating', 'error'); return; }
    const rating      = selectedStar.value;
    const description = document.getElementById('edit-description').value;

    fetch(`/ratings/${id}`, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ rating, description })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeEditModal();
            showToast('Review updated!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.error || 'Update failed', 'error');
        }
    });
}

// ── Delete movie (admin) ──────────────────────
let deleteMovieId = null;
function confirmDelete(id, name) {
    deleteMovieId = id;
    document.getElementById('delete-movie-name').textContent = name;
    document.getElementById('delete-movie-modal').style.display = 'flex';
}
document.getElementById('confirm-delete-btn')?.addEventListener('click', () => {
    if (!deleteMovieId) return;
    fetch(`/movies/${deleteMovieId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Movie deleted', 'success');
            setTimeout(() => window.location.href = '/movies', 1000);
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    });
});

// ── TMDB live info ────────────────────────────
function loadTMDBInfo(title) {
    const panel = document.getElementById('tmdb-info-panel');
    const btn   = document.getElementById('tmdb-load-btn');
    panel.style.display = 'block';
    btn.style.display   = 'none';

    fetch(`/api/tmdb/search?query=${encodeURIComponent(title)}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.querySelector('.tmdb-info-loading').style.display = 'none';
        if (data.success && data.data && data.data.length > 0) {
            const m = data.data[0];
            document.getElementById('tmdb-info-content').innerHTML = `
                <div class="tmdb-card">
                    <p><strong>TMDB Title:</strong> ${m.title} (${m.year})</p>
                    <p><strong>TMDB Rating:</strong> ⭐ ${m.vote_average}/10</p>
                    <p><strong>Overview:</strong> ${m.overview ? m.overview.substring(0, 200) + '…' : 'N/A'}</p>
                </div>
            `;
            document.getElementById('tmdb-info-content').style.display = 'block';
        } else {
            document.getElementById('tmdb-error').style.display = 'block';
        }
    })
    .catch(() => {
        document.querySelector('.tmdb-info-loading').style.display = 'none';
        document.getElementById('tmdb-error').style.display = 'block';
    });
}
</script>
@endpush