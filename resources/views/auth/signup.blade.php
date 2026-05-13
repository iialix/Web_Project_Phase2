@extends('layouts.app')

@section('title', 'Sign Up - Movie Tracker')

@section('content')
<div class="auth-container">
    <div class="auth-form">
        <h2>Create Account</h2>
        <p class="auth-subtitle">Join Movie Tracker today</p>
        <form action="{{ route('signup') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="userName">Username</label>
                <input type="text" id="userName" name="userName" value="{{ old('userName') }}" required>
                @error('userName') <span class="error-msg active">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <span class="error-msg active">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password (min. 6 characters)</label>
                <input type="password" id="password" name="password" required>
                @error('password') <span class="error-msg active">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="birthDate">Birth Date</label>
                <input type="date" id="birthDate" name="birthDate" value="{{ old('birthDate') }}" required>
                @error('birthDate') <span class="error-msg active">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn-primary btn-full">Sign Up</button>
            <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Login</a></p>
        </form>
    </div>
</div>
@endsection