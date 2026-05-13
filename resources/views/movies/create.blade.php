@extends('layouts.app')

@section('title', 'Add Movie - Movie Tracker')

@section('content')
<div class="form-container">
    <h2>Add New Movie</h2>
    <form action="{{ route('movies.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="name">Movie Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="categories">Categories *</label>
            <input type="text" id="categories" name="categories" value="{{ old('categories') }}" required>
            @error('categories') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="5" required>{{ old('description') }}</textarea>
            @error('description') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="poster">Poster Image</label>
            <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp">
            <small>Accepted formats: JPG, PNG, WEBP (max 5MB)</small>
            @error('poster') <span class="error-msg active">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Add Movie</button>
            <a href="{{ route('movies.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection