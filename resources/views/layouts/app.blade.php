<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CineTrack — My Movie List')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="logo" href="#hero" aria-label="CineTrack home">
                <span class="logo-icon">🎬</span>
                <span class="logo-text">CineTrack</span>
            </a>
            <nav class="header-nav" aria-label="Main navigation">
                <a href="#my-list" class="nav-link">My List</a>
                <a href="#add-section" class="nav-link">Add Movie</a>
                <a href="#api-section" class="nav-link">Discover</a>
            </nav>
            <button class="hamburger" id="hamburger" aria-label="Toggle menu" type="button">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <div id="toast" class="toast" role="alert" aria-live="polite"></div>
    <div id="modal-overlay" class="modal-overlay hidden"></div>

    @yield('content')

    <footer class="site-footer">
        <div class="footer-inner">
            <p class="footer-brand">🎬 CineTrack</p>
            <p class="footer-copy">&copy; {{ date('Y') }} Team &mdash; All rights reserved.</p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
