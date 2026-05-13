<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Movie Tracker')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="logo">Movie Tracker</div>
        <nav class="main-nav">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('movies.index') }}" class="{{ request()->routeIs('movies.*') ? 'active' : '' }}">Movies</a>
            @auth
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('movies.create') }}">Add Movie</a>
                @endif
                <a href="{{ route('wishlist.index') }}">Wishlist</a>
                <a href="{{ route('profile') }}">Profile</a>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endauth
        </nav>
    </header>
    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="footer-content">
            <p>&copy; 2026 Movie Tracker. All rights reserved.</p>
        </div>
    </footer>
    <script src="{{ asset('validation.js') }}"></script>
    <script src="{{ asset('API_Ops.js') }}"></script>
    <script src="{{ asset('main.js') }}"></script>
</body>
</html>