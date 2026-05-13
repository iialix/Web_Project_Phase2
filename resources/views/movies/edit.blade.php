@extends('layouts.app')

@section('title', 'Edit Movie - Movie Tracker')

@section('content')
<div class="form-container">
    <h2>Edit Movie</h2>
    <form action="{{ route('movies.update', $movie->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Movie Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $movie->name) }}" required>
            @error('name') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="categories">Categories *</label>
            <input type="text" id="categories" name="categories" value="{{ old('categories', $movie->categories) }}" required>
            @error('categories') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="5" required>{{ old('description', $movie->description) }}</textarea>
            @error('description') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="poster">New Poster (optional)</label>
            <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp">
            @error('poster') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="{{ route('movies.show', $movie->id) }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection