# CineTrack Movie App - Laravel

This is the completed Laravel version of the original plain PHP `Movie-App` project.

## Features

- Single-page movie list dashboard
- Add movie
- Edit movie
- Delete movie
- Search, genre filter, status filter, and sorting
- Upload local poster images
- Search TMDB movies and fill the add form automatically
- Laravel validation for all CRUD and upload operations
- SQLite by default for easy local testing

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## TMDB Setup

To enable the Discover Movies search, add your TMDB API key in `.env`:

```env
TMDB_API_KEY=your_tmdb_api_key_here
```

Then clear cached config if needed:

```bash
php artisan config:clear
```

## Main Files

- `routes/web.php` — web and AJAX routes
- `app/Http/Controllers/MovieController.php` — CRUD, poster upload, and TMDB API logic
- `app/Models/Movie.php` — Movie model
- `database/migrations/2026_05_06_143943_create_movies_table.php` — movies table
- `resources/views/index.blade.php` — main page
- `resources/views/layouts/app.blade.php` — layout
- `public/js/movie-app.js` — frontend AJAX logic
- `public/css/style.css` — app styling
