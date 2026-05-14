<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Movie Tracker')</title>
    <meta name="description" content="@yield('meta_description', 'Discover, rate, and track your favorite movies with Movie Tracker.')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('style.css') }}">

    @stack('styles')
</head>
<body>

    @include('layouts.partials.header')

    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success" id="flash-success">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error" id="flash-error">
                <span>✕</span> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @include('layouts.partials.footer')

    <!-- Scripts -->
    <script src="{{ asset('validation.js') }}"></script>
    <script src="{{ asset('API_Ops.js') }}"></script>
    <script src="{{ asset('app.js') }}"></script>

    @stack('scripts')

    <script>
        // Auto-dismiss flash messages
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => {
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-10px)';
                setTimeout(() => el.remove(), 500);
            });
        }, 4000);
    </script>
</body>
</html>