@extends('layouts.app')

@section('title', 'Login - MovieTracker')
@section('meta_description', 'Login to MovieTracker to rate and track your favorite movies.')

@section('content')
<div class="auth-page">
    <div class="auth-decoration">
        <div class="auth-shape auth-shape-1"></div>
        <div class="auth-shape auth-shape-2"></div>
    </div>

    <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-logo">🎬</div>
            <h1>Welcome Back</h1>
            <p>Sign in to your MovieTracker account</p>
        </div>

        <form action="{{ route('login') }}" method="POST" id="login-form" novalidate>
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon-wrapper">
                    <span class="input-icon">✉</span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                    >
                </div>
                @error('email') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="email-error"></span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrapper">
                    <span class="input-icon">🔒</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Your password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePwd('password', this)" tabindex="-1">👁</button>
                </div>
                @error('password') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="password-error"></span>
            </div>

            <button type="submit" class="btn-primary btn-full" id="login-btn">
                Sign In
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>

        <div class="auth-divider"><span>New here?</span></div>

        <a href="{{ route('signup') }}" class="btn-secondary btn-full">Create Free Account</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
    else { inp.type = 'password'; btn.textContent = '👁'; }
}

document.getElementById('login-form').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.error-msg').forEach(el => { el.textContent = ''; el.classList.remove('active'); });

    const email = document.getElementById('email').value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError('email-error', 'Please enter a valid email address.');
        valid = false;
    }

    const pwd = document.getElementById('password').value;
    if (!pwd) {
        showFieldError('password-error', 'Password is required.');
        valid = false;
    }

    if (!valid) e.preventDefault();
});

function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.add('active'); }
}
</script>
@endpush