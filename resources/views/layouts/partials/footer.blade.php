<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="footer-logo">🎬 Movie<span class="logo-accent">Tracker</span></span>
            <p class="footer-tagline">Discover, rate, and track your favorite movies.</p>
        </div>

        <div class="footer-links">
            <div class="footer-col">
                <h4>Explore</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('movies.index') }}">All Movies</a>
                @auth
                    <a href="{{ route('wishlist.index') }}">Wishlist</a>
                    <a href="{{ route('profile') }}">Profile</a>
                @endauth
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                @auth
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="footer-link-btn">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('signup') }}">Sign Up</a>
                @endauth
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; {{ date('Y') }} MovieTracker. All rights reserved.</p>
        <p class="footer-team">Built with ❤️ using Laravel</p>
    </div>
</footer>
