@extends('layouts.app')

@section('title', 'Sign Up - MovieTracker')
@section('meta_description', 'Create a free MovieTracker account and start tracking your favorite movies.')

@section('content')
<div class="auth-page">
    <div class="auth-decoration">
        <div class="auth-shape auth-shape-1"></div>
        <div class="auth-shape auth-shape-2"></div>
    </div>

    <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-logo">MovieTracker</div>
            <h1>Create Account</h1>
            <p>Join MovieTracker and start tracking today</p>
        </div>

        <form action="{{ route('signup') }}" method="POST" id="signup-form" novalidate>
            @csrf

            <div class="form-group">
                <label for="userName">Username</label>
                <div class="input-icon-wrapper">
                    <input
                        type="text"
                        id="userName"
                        name="userName"
                        value="{{ old('userName') }}"
                        placeholder="Choose a username"
                        autocomplete="username"
                        maxlength="255"
                        required
                    >
                </div>
                @error('userName') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="userName-error"></span>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon-wrapper">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        autocomplete="email"
                        maxlength="255"
                        required
                    >
                </div>
                @error('email') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="email-error"></span>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="hint-text">(min. 6 characters)</span></label>
                <div class="input-icon-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a strong password"
                        autocomplete="new-password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePwd('password', this)" tabindex="-1">Show</button>
                </div>
                @error('password') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="password-error"></span>
                <div class="password-strength" id="pwd-strength-bar">
                    <div class="strength-fill" id="strength-fill"></div>
                </div>
                <span class="strength-label" id="strength-label"></span>
            </div>

            <div class="form-group">
                <label for="birthDate">Date of Birth <span class="hint-text">(must be 13+)</span></label>
                <div class="input-icon-wrapper">
                    <input
                        type="date"
                        id="birthDate"
                        name="birthDate"
                        value="{{ old('birthDate') }}"
                        max="{{ date('Y-m-d', strtotime('-13 years')) }}"
                        required
                    >
                </div>
                @error('birthDate') <span class="error-msg active">{{ $message }}</span> @enderror
                <span class="error-msg" id="birthDate-error"></span>
            </div>

            <button type="submit" class="btn-primary btn-full">
                Create Account
            </button>
        </form>

        <div class="auth-divider"><span>Already a member?</span></div>

        <a href="{{ route('login') }}" class="btn-secondary btn-full">Sign In</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; btn.textContent = 'Hide'; }
    else { inp.type = 'password'; btn.textContent = 'Show'; }
}

// Password strength meter
document.getElementById('password').addEventListener('input', function() {
    const val    = this.value;
    const fill   = document.getElementById('strength-fill');
    const label  = document.getElementById('strength-label');
    let strength = 0;
    if (val.length >= 6)  strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const pct = (strength / 5) * 100;
    fill.style.width = pct + '%';
    fill.style.background = strength <= 1 ? '#e94560' : strength <= 3 ? '#f39c12' : '#4ecca3';
    label.textContent = strength <= 1 ? 'Weak' : strength <= 3 ? 'Fair' : 'Strong';
    label.style.color = fill.style.background;
});

// Client-side validation
document.getElementById('signup-form').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.error-msg').forEach(el => { el.textContent = ''; el.classList.remove('active'); });

    const uname = document.getElementById('userName').value.trim();
    if (!uname || uname.length < 3) {
        showFieldError('userName-error', 'Username must be at least 3 characters.'); valid = false;
    }

    const email = document.getElementById('email').value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError('email-error', 'Please enter a valid email address.'); valid = false;
    }

    const pwd = document.getElementById('password').value;
    if (!pwd || pwd.length < 6) {
        showFieldError('password-error', 'Password must be at least 6 characters.'); valid = false;
    }

    const bd = document.getElementById('birthDate').value;
    if (!bd) {
        showFieldError('birthDate-error', 'Birth date is required.'); valid = false;
    } else {
        const minAge = new Date();
        minAge.setFullYear(minAge.getFullYear() - 13);
        if (new Date(bd) > minAge) {
            showFieldError('birthDate-error', 'You must be at least 13 years old.'); valid = false;
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