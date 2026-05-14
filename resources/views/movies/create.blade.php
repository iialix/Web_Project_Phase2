@extends('layouts.app')

@section('title', 'Add Movie - MovieTracker')

@section('content')
<div class="page-wrapper">
    <a href="{{ route('movies.index') }}" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Movies
    </a>

    <div class="form-card">
        <div class="form-card-header">
            <h1>Add New Movie</h1>
            <p class="form-subtitle">Fill in the details below or search TMDB to auto-fill.</p>
        </div>

        {{-- ─── TMDB Search ──────────────────────────── --}}
        <div class="tmdb-search-section">
            <h3>🌐 Search from TMDB</h3>
            <div class="tmdb-search-bar">
                <input type="text" id="tmdb-search-input" placeholder="Search movie on TMDB…" autocomplete="off">
                <button type="button" class="btn-primary btn-sm" onclick="searchTMDB()">Search</button>
            </div>
            <div id="tmdb-error-msg" class="tmdb-api-error" style="display:none;">
                ⚠ TMDB search unavailable right now. Please fill in details manually.
            </div>
            <div id="tmdb-results" class="tmdb-results"></div>
        </div>

        {{-- ─── Poster Preview ──────────────────────── --}}
        <div id="poster-preview-section" class="poster-preview" style="display:none;">
            <h3>📸 Selected Poster</h3>
            <img id="poster-preview-img" src="" alt="Poster Preview">
            <div class="poster-actions">
                <button type="button" class="btn-secondary btn-sm" onclick="clearPosterPreview()">✕ Clear</button>
            </div>
            <input type="hidden" id="tmdb-poster-url" name="tmdb_poster_url" value="">
        </div>

        {{-- ─── Movie Form ──────────────────────────── --}}
        <form
            action="{{ route('movies.store') }}"
            method="POST"
            enctype="multipart/form-data"
            id="add-movie-form"
            novalidate
        >
            @csrf

            <div class="form-group">
                <label for="name">Movie Name <span class="required">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="e.g. Inception"
                    maxlength="255"
                    required
                >
                @error('name') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="name-error"></span>
            </div>

            <div class="form-group">
                <label for="categories">Categories <span class="required">*</span></label>
                <input
                    type="text"
                    id="categories"
                    name="categories"
                    value="{{ old('categories') }}"
                    placeholder="e.g. Action, Sci-Fi, Thriller"
                    maxlength="255"
                    required
                >
                @error('categories') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="categories-error"></span>
            </div>

            <div class="form-group">
                <label for="description">Description <span class="required">*</span></label>
                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    placeholder="Write a brief description of the movie (min. 10 characters)…"
                    required
                >{{ old('description') }}</textarea>
                @error('description') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="description-error"></span>
            </div>

            <div class="form-group">
                <label for="poster">Poster Image</label>
                <div class="file-input-wrapper">
                    <input
                        type="file"
                        id="poster"
                        name="poster"
                        accept="image/jpeg,image/png,image/webp"
                        onchange="previewLocalPoster(this)"
                    >
                    <div class="file-input-ui">
                        <span class="file-input-icon">📁</span>
                        <span class="file-input-text">Choose file or drag here</span>
                        <span class="file-input-hint">JPG, PNG, WEBP — max 5 MB</span>
                    </div>
                </div>
                @error('poster') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="poster-error"></span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="submit-movie-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Add Movie
                </button>
                <a href="{{ route('movies.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── TMDB Search ──────────────────────────────────
async function searchTMDB() {
    const query = document.getElementById('tmdb-search-input').value.trim();
    const resultsEl = document.getElementById('tmdb-results');
    const errEl     = document.getElementById('tmdb-error-msg');

    if (!query) { showToast('Enter a movie title to search', 'error'); return; }

    errEl.style.display = 'none';
    resultsEl.innerHTML = '<div class="spinner"></div>';

    try {
        const res  = await fetch(`/api/tmdb/search?query=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success && data.data && data.data.length > 0) {
            resultsEl.innerHTML = data.data.slice(0, 8).map(m => `
                <div class="tmdb-result-card" onclick="selectTMDBMovie(${JSON.stringify(m).replace(/"/g,'&quot;')})">
                    <img src="${m.poster || 'https://placehold.co/100x150/1a1a2e/e94560?text=No+Img'}" alt="${m.title}" loading="lazy">
                    <h4>${m.title}</h4>
                    <p>${m.year} · ⭐ ${m.vote_average}</p>
                </div>
            `).join('');
        } else {
            resultsEl.innerHTML = '<p class="tmdb-no-results">No results found on TMDB.</p>';
        }
    } catch {
        resultsEl.innerHTML = '';
        errEl.style.display = 'block';
    }
}

function selectTMDBMovie(movie) {
    document.getElementById('name').value        = movie.title || '';
    document.getElementById('description').value = movie.overview || '';
    document.getElementById('categories').value  = movie.genres ? movie.genres.join(', ') : 'Drama';

    if (movie.poster) {
        document.getElementById('poster-preview-img').src = movie.poster;
        document.getElementById('tmdb-poster-url').value  = movie.poster;
        document.getElementById('poster-preview-section').style.display = 'block';
    }

    document.getElementById('tmdb-results').innerHTML = '';
    showToast('Movie details filled from TMDB!', 'success');
}

function clearPosterPreview() {
    document.getElementById('poster-preview-section').style.display = 'none';
    document.getElementById('poster-preview-img').src = '';
    document.getElementById('tmdb-poster-url').value  = '';
    document.getElementById('poster').value = '';
}

function previewLocalPoster(input) {
    if (input.files && input.files[0]) {
        const url = URL.createObjectURL(input.files[0]);
        document.getElementById('poster-preview-img').src = url;
        document.getElementById('poster-preview-section').style.display = 'block';
        document.getElementById('tmdb-poster-url').value = '';
    }
}

// ── Client-side validation ────────────────────
document.getElementById('add-movie-form').addEventListener('submit', function(e) {
    let valid = true;

    // Clear previous errors
    document.querySelectorAll('.error-msg').forEach(el => {
        el.textContent = '';
        el.classList.remove('active');
    });

    const name = document.getElementById('name').value.trim();
    if (!name) {
        showFieldError('name-error', 'Movie name is required.');
        valid = false;
    }

    const cats = document.getElementById('categories').value.trim();
    if (!cats) {
        showFieldError('categories-error', 'Categories are required.');
        valid = false;
    }

    const desc = document.getElementById('description').value.trim();
    if (!desc || desc.length < 10) {
        showFieldError('description-error', 'Description must be at least 10 characters.');
        valid = false;
    }

    const posterFile = document.getElementById('poster').files[0];
    const posterUrl  = document.getElementById('tmdb-poster-url').value;

    if (posterFile) {
        if (!['image/jpeg','image/png','image/webp'].includes(posterFile.type)) {
            showFieldError('poster-error', 'Only JPG, PNG, or WEBP allowed.');
            valid = false;
        }
        if (posterFile.size > 5 * 1024 * 1024) {
            showFieldError('poster-error', 'File must be under 5 MB.');
            valid = false;
        }
    }

    if (!valid) e.preventDefault();
});

function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.add('active'); }
}

// Allow Enter key on TMDB search input
document.getElementById('tmdb-search-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchTMDB(); }
});
</script>
@endpush