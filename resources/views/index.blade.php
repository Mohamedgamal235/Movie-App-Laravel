@extends('layouts.app')

@section('title', 'CineTrack — My Movie List')

@section('content')
    <section id="hero" class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Track Every Frame<br><span>You&rsquo;ve Ever Loved.</span></h1>
            <p class="hero-sub">Add movies, rate them, discover new ones — all in one place.</p>
            <a href="#my-list" class="btn-primary">My List ↓</a>
        </div>
        <div class="hero-filmstrip" aria-hidden="true">
            <div class="strip-track">
                <span>🎬</span><span>🎥</span><span>🍿</span><span>⭐</span>
                <span>🎬</span><span>🎥</span><span>🍿</span><span>⭐</span>
                <span>🎬</span><span>🎥</span><span>🍿</span><span>⭐</span>
            </div>
        </div>
    </section>

    <section id="my-list" class="section">
        <div class="container">
            <h2 class="section-title">My List</h2>

            <div class="filter-bar">
                <input type="text" id="search-input" class="filter-input" placeholder="🔍 Search by title…" autocomplete="off">

                <select id="filter-genre" class="filter-select">
                    <option value="">All Genres</option>
                    @foreach($genres as $genre)
                        <option value="{{ $genre }}">{{ $genre }}</option>
                    @endforeach
                </select>

                <select id="filter-status" class="filter-select">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>

                <select id="filter-sort" class="filter-select">
                    <option value="created_at">Newest First</option>
                    <option value="rating">Highest Rated</option>
                    <option value="title">A–Z</option>
                </select>

                <button id="btn-reset-filters" class="btn-ghost" type="button">Reset</button>
            </div>

            <div id="movies-grid" class="movies-grid">
                <div class="loading-spinner"><span></span></div>
            </div>
        </div>
    </section>

    <section id="add-section" class="section section-dark">
        <div class="container">
            <h2 class="section-title">Add a Movie</h2>

            <form id="add-form" class="movie-form" novalidate>
                <input type="hidden" id="form-tmdb-id" name="tmdb_id">
                <input type="hidden" id="form-poster" name="poster_path">

                <div class="form-row">
                    <div class="form-group">
                        <label for="form-title">Movie Title *</label>
                        <input type="text" id="form-title" name="title" placeholder="e.g. Inception" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label for="form-genre">Genre *</label>
                        <select id="form-genre" name="genre" required>
                            <option value="">Select genre…</option>
                            @foreach($genres as $genre)
                                <option value="{{ $genre }}">{{ $genre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="form-rating">My Rating (1–10) *</label>
                        <input type="number" id="form-rating" name="rating" min="1" max="10" step="0.1" placeholder="e.g. 8.5" required>
                    </div>

                    <div class="form-group">
                        <label for="form-status">Status *</label>
                        <select id="form-status" name="status" required>
                            <option value="">Select status…</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="form-notes">Notes</label>
                    <textarea id="form-notes" name="notes" rows="3" placeholder="Your thoughts about this movie…"></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="form-poster-file">Upload Poster</label>
                    <input type="file" id="form-poster-file" accept="image/*">
                    <small class="field-hint">Required unless you choose a poster from TMDB.</small>
                </div>

                <button type="submit" class="btn-primary">+ Add to My List</button>
            </form>
        </div>
    </section>

    <section id="api-section" class="section">
        <div class="container">
            <h2 class="section-title">Discover Movies</h2>
            <p class="section-sub">Search TMDB&rsquo;s database and add directly to your list.</p>

            <div class="api-search-bar">
                <input type="text" id="api-search-input" placeholder="🔍 Search TMDB… (e.g. Dune, Matrix)" autocomplete="off">
            </div>

            <div id="api-error" class="api-error hidden" role="alert"></div>
            <div id="api-results" class="api-results"></div>
        </div>
    </section>

    <div id="edit-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
        <div class="modal-header">
            <h3 id="edit-modal-title">Edit Movie</h3>
            <button class="modal-close" type="button" aria-label="Close">&times;</button>
        </div>

        <form id="edit-form" novalidate>
            <input type="hidden" id="edit-id">
            <input type="hidden" id="edit-poster">
            <input type="hidden" id="edit-tmdb-id">

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-title">Movie Title *</label>
                    <input type="text" id="edit-title" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label for="edit-genre">Genre *</label>
                    <select id="edit-genre" required>
                        <option value="">Select genre…</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre }}">{{ $genre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-rating">Rating (1–10) *</label>
                    <input type="number" id="edit-rating" min="1" max="10" step="0.1" required>
                </div>
                <div class="form-group">
                    <label for="edit-status">Status *</label>
                    <select id="edit-status" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="edit-notes">Notes</label>
                <textarea id="edit-notes" rows="3"></textarea>
            </div>

            <div class="form-group full-width">
                <label for="edit-poster-file">Change Poster</label>
                <input type="file" id="edit-poster-file" accept="image/*">
            </div>

            <div id="edit-error" class="field-error"></div>

            <div class="modal-footer">
                <button type="button" class="btn-ghost" id="btn-cancel-edit">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <div id="confirm-modal" class="modal modal-sm hidden" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3>Delete Movie?</h3>
            <button class="modal-close" type="button" id="btn-close-confirm" aria-label="Close">&times;</button>
        </div>
        <input type="hidden" id="confirm-delete-id">
        <p class="confirm-text">Remove <strong id="confirm-movie-title"></strong> from your list?</p>
        <div class="modal-footer">
            <button class="btn-ghost" id="btn-cancel-delete" type="button">Cancel</button>
            <button class="btn-danger" id="btn-execute-delete" type="button">Delete</button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.MovieApp = {
            routes: {
                list: @json(route('movies.all')),
                store: @json(route('movies.store')),
                update: @json(url('/movies')),
                destroy: @json(url('/movies')),
                upload: @json(route('posters.upload')),
                tmdbSearch: @json(route('tmdb.search')),
            },
            tmdbImageBaseUrl: @json($tmdbImageBaseUrl),
        };
    </script>
    <script src="{{ asset('js/movie-app.js') }}"></script>
@endsection
