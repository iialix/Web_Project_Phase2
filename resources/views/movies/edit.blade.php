@extends('layouts.app')

@section('title', 'Edit Movie - MovieTracker')

@section('content')
<div class="page-wrapper">
    <a href="{{ route('movies.show', $movie->id) }}" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Movie
    </a>

    <div class="form-card">
        <div class="form-card-header">
            <h1>Edit Movie</h1>
            <p class="form-subtitle">Update the details for <strong>{{ e($movie->name) }}</strong></p>
        </div>

        {{-- Current Poster Preview --}}
        @if($movie->poster)
        <div class="current-poster-preview">
            <h4>Current Poster</h4>
            <img src="{{ asset('uploads/' . $movie->poster) }}" alt="Current poster" class="current-poster-img">
        </div>
        @endif

        <form
            action="{{ route('movies.update', $movie->id) }}"
            method="POST"
            enctype="multipart/form-data"
            id="edit-movie-form"
            novalidate
        >
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Movie Name <span class="required">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $movie->name) }}"
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
                    value="{{ old('categories', $movie->categories) }}"
                    maxlength="255"
                    required
                >
                @error('categories') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="categories-error"></span>
            </div>

            <div class="form-group">
                <label for="description">Description <span class="required">*</span></label>
                <textarea id="description" name="description" rows="5" required>{{ old('description', $movie->description) }}</textarea>
                @error('description') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="description-error"></span>
            </div>

            <div class="form-group">
                <label for="poster">Replace Poster (optional)</label>
                <div class="file-input-wrapper">
                    <input
                        type="file"
                        id="poster"
                        name="poster"
                        accept="image/jpeg,image/png,image/webp"
                        onchange="previewNewPoster(this)"
                    >
                    <div class="file-input-ui">
                        <span class="file-input-icon">📁</span>
                        <span class="file-input-text">Choose new poster</span>
                        <span class="file-input-hint">JPG, PNG, WEBP — max 5 MB</span>
                    </div>
                </div>
                <div id="new-poster-preview" style="display:none; margin-top:1rem;">
                    <img id="new-poster-img" src="" alt="New poster preview" style="max-width:180px; border-radius:8px;">
                </div>
                @error('poster') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="poster-error"></span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save Changes
                </button>
                <a href="{{ route('movies.show', $movie->id) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewNewPoster(input) {
    if (input.files && input.files[0]) {
        const url = URL.createObjectURL(input.files[0]);
        document.getElementById('new-poster-img').src = url;
        document.getElementById('new-poster-preview').style.display = 'block';
    }
}

document.getElementById('edit-movie-form').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.error-msg').forEach(el => { el.textContent = ''; el.classList.remove('active'); });

    const name = document.getElementById('name').value.trim();
    if (!name) { showFieldError('name-error', 'Movie name is required.'); valid = false; }

    const cats = document.getElementById('categories').value.trim();
    if (!cats) { showFieldError('categories-error', 'Categories are required.'); valid = false; }

    const desc = document.getElementById('description').value.trim();
    if (!desc || desc.length < 10) { showFieldError('description-error', 'Description must be at least 10 characters.'); valid = false; }

    const posterFile = document.getElementById('poster').files[0];
    if (posterFile) {
        if (!['image/jpeg','image/png','image/webp'].includes(posterFile.type)) {
            showFieldError('poster-error', 'Only JPG, PNG, or WEBP allowed.'); valid = false;
        }
        if (posterFile.size > 5 * 1024 * 1024) {
            showFieldError('poster-error', 'File must be under 5 MB.'); valid = false;
        }
    }

    if (!valid) e.preventDefault();
});

function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.add('active'); }
}
</script>
@endpush