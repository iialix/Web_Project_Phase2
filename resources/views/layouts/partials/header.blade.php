<header class="site-header" id="site-header">
    <a href="{{ route('home') }}" class="logo">
        <span class="logo-icon">🎬</span>
        <span class="logo-text">Movie<span class="logo-accent">Tracker</span></span>
    </a>

    <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <nav class="main-nav" id="main-nav">
        <a href="{{ route('home') }}"
           class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
            Home
        </a>
        <a href="{{ route('movies.index') }}"
           class="nav-link {{ request()->routeIs('movies.*') ? 'active' : '' }}">
            Movies
        </a>

        @auth
            <a href="{{ route('wishlist.index') }}"
               class="nav-link {{ request()->routeIs('wishlist.*') ? 'active' : '' }}">
                <span class="nav-icon">♡</span> Wishlist
            </a>

            @if(Auth::user()->isAdmin())
                <a href="{{ route('movies.create') }}"
                   class="nav-link nav-link-add {{ request()->routeIs('movies.create') ? 'active' : '' }}">
                    + Add Movie
                </a>
            @endif

            <a href="{{ route('profile') }}"
               class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                <span class="nav-avatar">{{ strtoupper(substr(Auth::user()->UserName, 0, 1)) }}</span>
                {{ Auth::user()->UserName }}
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="inline-form">
                @csrf
                <button type="submit" class="btn-nav-logout">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}"
               class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                Login
            </a>
            <a href="{{ route('signup') }}"
               class="btn-nav-signup {{ request()->routeIs('signup') ? 'active' : '' }}">
                Sign Up
            </a>
        @endauth
    </nav>
</header>
