@extends('layouts.app')

@section('title', 'Login - Movie Tracker')

@section('content')
<div class="auth-container">
    <div class="auth-form">
        <h2>Welcome Back</h2>
        <p class="auth-subtitle">Login to your account</p>
        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <span class="error-msg active">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password') <span class="error-msg active">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn-primary btn-full">Login</button>
            <p class="auth-switch">Don't have an account? <a href="{{ route('signup') }}">Sign up</a></p>
        </form>
    </div>
</div>
@endsection