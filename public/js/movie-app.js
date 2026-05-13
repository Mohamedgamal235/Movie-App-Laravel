(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const config = window.MovieApp || { routes: {} };
    const routes = config.routes || {};

    let moviesCache = [];
    let debounceTimer = null;

    const allowedGenres = [
        'Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi',
        'Romance', 'Documentary', 'Animation', 'Thriller',
        'Fantasy', 'Other'
    ];

    const allowedStatuses = ['Watching', 'Watched', 'Plan to Watch'];

    function query(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/'/g, '&#039;');
    }

    function normalize(value) {
        return String(value || '').toLowerCase().trim();
    }

    function posterUrl(path) {
        if (!path) {
            return 'https://placehold.co/300x450/1a1a2e/e8e8f0?text=No+Poster';
        }

        if (path.startsWith('http')) {
            return path;
        }

        if (path.startsWith('/uploads/') || path.startsWith('uploads/')) {
            return path;
        }

        if (path.startsWith('/')) {
            return `${config.tmdbImageBaseUrl || 'https://image.tmdb.org/t/p/w500'}${path}`;
        }

        return path;
    }

    async function requestJson(url, options = {}) {
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...(options.headers || {}),
        };

        const response = await fetch(url, {
            ...options,
            headers,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationErrors = data.errors
                ? Object.values(data.errors).flat().join(' | ')
                : '';
            throw new Error(validationErrors || data.error || data.message || 'Request failed.');
        }

        return data;
    }

    function showToast(message, type = 'success') {
        const toast = query('toast');
        if (!toast) return;

        toast.textContent = message;
        toast.className = `toast toast-${type} show`;

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function showError(fieldId, message) {
        const field = query(fieldId);
        if (!field) return;

        field.classList.add('input-error');

        let errorEl = field.parentElement.querySelector('.field-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'field-error';
            field.parentElement.appendChild(errorEl);
        }

        errorEl.textContent = message;
    }

    function clearError(fieldId) {
        const field = query(fieldId);
        if (!field) return;

        field.classList.remove('input-error');

        const errorEl = field.parentElement.querySelector('.field-error');
        if (errorEl) {
            errorEl.textContent = '';
        }
    }

    function clearAllErrors(formId) {
        const form = query(formId);
        if (!form) return;

        form.querySelectorAll('.input-error').forEach((el) => el.classList.remove('input-error'));
        form.querySelectorAll('.field-error').forEach((el) => {
            el.textContent = '';
        });
    }

    function validateMovieForm(prefix) {
        const isEdit = prefix === 'edit';
        const formId = isEdit ? 'edit-form' : 'add-form';
        clearAllErrors(formId);

        if (isEdit) {
            const editError = query('edit-error');
            if (editError) editError.textContent = '';
        }

        let valid = true;
        const title = query(`${prefix}-title`)?.value.trim() || '';
        const genre = query(`${prefix}-genre`)?.value.trim() || '';
        const rating = query(`${prefix}-rating`)?.value.trim() || '';
        const status = query(`${prefix}-status`)?.value.trim() || '';
        const posterPath = query(`${prefix}-poster`)?.value.trim() || '';
        const posterFileInput = query(`${prefix}-poster-file`);
        const hasPosterFile = !!(posterFileInput && posterFileInput.files && posterFileInput.files.length);

        if (!title) {
            showError(`${prefix}-title`, 'Title is required.');
            valid = false;
        } else if (title.length > 255) {
            showError(`${prefix}-title`, 'Title must be under 255 characters.');
            valid = false;
        }

        if (!genre) {
            showError(`${prefix}-genre`, 'Genre is required.');
            valid = false;
        } else if (!allowedGenres.includes(genre)) {
            showError(`${prefix}-genre`, 'Invalid genre.');
            valid = false;
        }

        const numericRating = parseFloat(rating);
        if (!rating) {
            showError(`${prefix}-rating`, 'Rating is required.');
            valid = false;
        } else if (Number.isNaN(numericRating) || numericRating < 1 || numericRating > 10) {
            showError(`${prefix}-rating`, 'Rating must be between 1 and 10.');
            valid = false;
        }

        if (!status) {
            showError(`${prefix}-status`, 'Status is required.');
            valid = false;
        } else if (!allowedStatuses.includes(status)) {
            showError(`${prefix}-status`, 'Invalid status.');
            valid = false;
        }

        if (!posterPath && !hasPosterFile) {
            showError(`${prefix}-poster-file`, 'Poster image is required.');
            valid = false;
        }

        if (!valid && isEdit) {
            const editError = query('edit-error');
            if (editError) editError.textContent = 'Please fix the highlighted fields.';
        }

        return valid;
    }

    async function uploadPoster(file) {
        const formData = new FormData();
        formData.append('poster_file', file);

        const data = await requestJson(routes.upload, {
            method: 'POST',
            body: formData,
        });

        if (!data.success) {
            throw new Error(data.error || 'Image upload failed.');
        }

        return data.path;
    }

    function buildCard(movie) {
        const title = escapeHtml(movie.title);
        const genre = escapeHtml(movie.genre);
        const rating = Number.parseFloat(movie.rating || 0).toFixed(1);
        const status = escapeHtml(movie.status);
        const statusClass = `status-${normalize(movie.status).replaceAll(' ', '-')}`;
        const notes = escapeHtml(movie.notes || '');
        const poster = escapeAttribute(posterUrl(movie.poster_path));
        const id = movie.id;

        return `
            <article class="movie-card" data-id="${id}">
                <div class="poster-wrapper">
                    <img class="card-poster" src="${poster}" alt="${title}" loading="lazy" onerror="this.src='https://placehold.co/300x450/1a1a2e/e8e8f0?text=No+Poster'">
                    <div class="card-status ${statusClass}">${status}</div>
                </div>
                <div class="card-body">
                    <h3 class="card-title">${title}</h3>
                    <span class="card-genre">${genre}</span>
                    <div class="card-rating">⭐ ${rating}/10</div>
                    <p class="card-notes">${notes || 'No notes yet.'}</p>
                    <div class="card-actions">
                        <button class="btn-edit btn-ghost btn-sm" type="button" data-edit-id="${id}">Edit</button>
                        <button class="btn-danger btn-sm" type="button" data-delete-id="${id}" data-delete-title="${escapeAttribute(movie.title)}">Delete</button>
                    </div>
                </div>
            </article>
        `;
    }

    function renderMovies(movies) {
        const grid = query('movies-grid');
        if (!grid) return;

        if (!movies.length) {
            grid.innerHTML = `
                <div class="empty-state">
                    <p>No movies in your list yet.</p>
                    <a href="#add-section" class="btn-primary">Add Your First Movie</a>
                </div>
            `;
            return;
        }

        grid.innerHTML = movies.map(buildCard).join('');
    }

    async function listMovies() {
        const grid = query('movies-grid');
        if (!grid) return;

        grid.innerHTML = '<div class="loading-spinner"><span></span></div>';

        try {
            const movies = await requestJson(routes.list);
            moviesCache = Array.isArray(movies) ? movies : [];
            applyFilters();
        } catch (error) {
            grid.innerHTML = `
                <div class="error-state">
                    <p>Failed to load your movie list.</p>
                    <button class="btn-primary" type="button" id="retry-load">Retry</button>
                </div>
            `;
            query('retry-load')?.addEventListener('click', listMovies);
            showToast(error.message || 'Failed to load movies.', 'error');
        }
    }

    function sortMovies(movies, sort) {
        const copy = [...movies];

        if (sort === 'rating') {
            return copy.sort((a, b) => Number.parseFloat(b.rating || 0) - Number.parseFloat(a.rating || 0));
        }

        if (sort === 'title') {
            return copy.sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));
        }

        return copy.sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime());
    }

    function applyFilters() {
        const q = normalize(query('search-input')?.value);
        const genre = normalize(query('filter-genre')?.value);
        const status = normalize(query('filter-status')?.value);
        const sort = query('filter-sort')?.value || 'created_at';

        let filtered = [...moviesCache];

        if (q) {
            filtered = filtered.filter((movie) => normalize(movie.title).includes(q));
        }

        if (genre) {
            filtered = filtered.filter((movie) => normalize(movie.genre) === genre);
        }

        if (status) {
            filtered = filtered.filter((movie) => normalize(movie.status) === status);
        }

        renderMovies(sortMovies(filtered, sort));
    }

    function resetFilters() {
        query('search-input').value = '';
        query('filter-genre').value = '';
        query('filter-status').value = '';
        query('filter-sort').value = 'created_at';
        renderMovies(sortMovies([...moviesCache], 'created_at'));
    }

    async function createMovie(event) {
        event.preventDefault();

        if (!validateMovieForm('form')) {
            showToast('Please fix the form errors.', 'error');
            return;
        }

        const form = query('add-form');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        let posterPath = query('form-poster').value.trim();
        const posterFile = query('form-poster-file')?.files?.[0];

        submitButton.disabled = true;
        submitButton.textContent = 'Adding...';

        try {
            if (posterFile) {
                posterPath = await uploadPoster(posterFile);
                query('form-poster').value = posterPath;
            }

            const payload = new URLSearchParams({
                title: query('form-title').value.trim(),
                genre: query('form-genre').value,
                rating: query('form-rating').value,
                status: query('form-status').value,
                notes: query('form-notes').value.trim(),
                poster_path: posterPath,
            });

            const tmdbId = query('form-tmdb-id').value.trim();
            if (tmdbId) payload.append('tmdb_id', tmdbId);

            await requestJson(routes.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload,
            });

            showToast('Movie added successfully.', 'success');
            form.reset();
            query('form-poster').value = '';
            query('form-tmdb-id').value = '';
            await listMovies();
            query('my-list')?.scrollIntoView({ behavior: 'smooth' });
        } catch (error) {
            showToast(error.message || 'Insert failed.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }

    function openEditModal(id) {
        const movie = moviesCache.find((item) => String(item.id) === String(id));
        if (!movie) return;

        query('edit-id').value = movie.id;
        query('edit-title').value = movie.title || '';
        query('edit-genre').value = movie.genre || '';
        query('edit-rating').value = movie.rating || '';
        query('edit-notes').value = movie.notes || '';
        query('edit-status').value = movie.status || '';
        query('edit-poster').value = movie.poster_path || '';
        query('edit-tmdb-id').value = movie.tmdb_id || '';
        query('edit-poster-file').value = '';
        query('edit-error').textContent = '';

        query('modal-overlay').classList.remove('hidden');
        query('edit-modal').classList.remove('hidden');
    }

    function closeEditModal() {
        query('edit-modal').classList.add('hidden');
        query('modal-overlay').classList.add('hidden');
        query('edit-error').textContent = '';
    }

    async function submitEdit(event) {
        event.preventDefault();

        if (!validateMovieForm('edit')) return;

        const id = query('edit-id').value;
        let posterPath = query('edit-poster').value.trim();
        const posterFile = query('edit-poster-file')?.files?.[0];
        const submitButton = query('edit-form').querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;

        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        try {
            if (posterFile) {
                posterPath = await uploadPoster(posterFile);
                query('edit-poster').value = posterPath;
            }

            const payload = new URLSearchParams({
                title: query('edit-title').value.trim(),
                genre: query('edit-genre').value,
                rating: query('edit-rating').value,
                status: query('edit-status').value,
                notes: query('edit-notes').value.trim(),
                poster_path: posterPath,
                _method: 'PUT',
            });

            const tmdbId = query('edit-tmdb-id').value.trim();
            if (tmdbId) payload.append('tmdb_id', tmdbId);

            await requestJson(`${routes.update}/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload,
            });

            closeEditModal();
            showToast('Movie updated successfully.', 'success');
            await listMovies();
        } catch (error) {
            query('edit-error').textContent = error.message || 'Update failed.';
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }

    function openConfirmModal(id, title) {
        query('confirm-delete-id').value = id;
        query('confirm-movie-title').textContent = title || 'this movie';
        query('modal-overlay').classList.remove('hidden');
        query('confirm-modal').classList.remove('hidden');
    }

    function closeConfirmModal() {
        query('confirm-modal').classList.add('hidden');
        query('modal-overlay').classList.add('hidden');
    }

    async function executeDelete() {
        const id = query('confirm-delete-id').value;
        if (!id) return;

        try {
            await requestJson(`${routes.destroy}/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ _method: 'DELETE' }),
            });

            closeConfirmModal();
            showToast('Movie deleted.', 'success');
            await listMovies();
        } catch (error) {
            showToast(error.message || 'Delete failed.', 'error');
        }
    }

    function renderTmdbMovies(movies) {
        const resultsBox = query('api-results');

        if (!movies || !movies.length) {
            resultsBox.innerHTML = '<p class="no-results">No movies found.</p>';
            return;
        }

        resultsBox.innerHTML = movies.map((movie) => `
            <div class="api-card">
                <div class="api-card-poster">
                    ${movie.poster_url
                        ? `<img src="${escapeAttribute(movie.poster_url)}" alt="${escapeAttribute(movie.title)}">`
                        : '<div class="no-poster">🎬</div>'}
                </div>
                <div class="api-card-info">
                    <h4>${escapeHtml(movie.title)}</h4>
                    <span class="api-year">${escapeHtml(movie.release_year || '—')}</span>
                    <span class="api-rating">⭐ ${Number(movie.vote_average || 0).toFixed(1)}</span>
                    <p class="api-overview">${escapeHtml(movie.overview || 'No overview available.')}</p>
                    <button class="btn-use-movie" type="button"
                        data-tmdb-id="${movie.tmdb_id || ''}"
                        data-title="${escapeAttribute(movie.title)}"
                        data-rating="${movie.vote_average || ''}"
                        data-overview="${escapeAttribute(movie.overview || '')}"
                        data-poster="${escapeAttribute(movie.poster_path || '')}">
                        Use This Movie
                    </button>
                </div>
            </div>
        `).join('');
    }

    async function searchTmdb(queryText) {
        const resultsBox = query('api-results');
        const errorBox = query('api-error');

        errorBox.classList.add('hidden');
        errorBox.textContent = '';
        resultsBox.innerHTML = '<div class="loading-spinner"><span></span></div>';

        try {
            const url = `${routes.tmdbSearch}?q=${encodeURIComponent(queryText)}`;
            const result = await requestJson(url);

            if (!result.success) {
                resultsBox.innerHTML = '';
                errorBox.textContent = result.error || 'Search failed.';
                errorBox.classList.remove('hidden');
                return;
            }

            renderTmdbMovies(result.data || []);
        } catch (error) {
            resultsBox.innerHTML = '';
            errorBox.textContent = error.message || 'Network error.';
            errorBox.classList.remove('hidden');
        }
    }

    function fillFormFromTmdb(button) {
        query('form-tmdb-id').value = button.dataset.tmdbId || '';
        query('form-title').value = button.dataset.title || '';
        query('form-rating').value = Number(button.dataset.rating || 0).toFixed(1);
        query('form-notes').value = button.dataset.overview || '';
        query('form-poster').value = button.dataset.poster || '';
        query('form-genre').value = 'Other';
        query('form-poster-file').value = '';

        clearAllErrors('add-form');
        query('add-section')?.scrollIntoView({ behavior: 'smooth' });
        showToast('Movie data filled into the form.', 'success');
    }

    function attachEvents() {
        query('hamburger')?.addEventListener('click', () => {
            document.querySelector('.header-nav')?.classList.toggle('open');
        });

        query('add-form')?.addEventListener('submit', createMovie);
        query('edit-form')?.addEventListener('submit', submitEdit);
        query('btn-cancel-edit')?.addEventListener('click', closeEditModal);
        query('edit-modal')?.querySelector('.modal-close')?.addEventListener('click', closeEditModal);

        query('btn-close-confirm')?.addEventListener('click', closeConfirmModal);
        query('btn-cancel-delete')?.addEventListener('click', closeConfirmModal);
        query('btn-execute-delete')?.addEventListener('click', executeDelete);

        query('modal-overlay')?.addEventListener('click', () => {
            closeEditModal();
            closeConfirmModal();
        });

        query('search-input')?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilters, 300);
        });
        query('filter-genre')?.addEventListener('change', applyFilters);
        query('filter-status')?.addEventListener('change', applyFilters);
        query('filter-sort')?.addEventListener('change', applyFilters);
        query('btn-reset-filters')?.addEventListener('click', resetFilters);

        query('api-search-input')?.addEventListener('input', (event) => {
            clearTimeout(debounceTimer);
            const value = event.target.value.trim();

            if (value.length < 2) {
                query('api-results').innerHTML = '';
                query('api-error').classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => searchTmdb(value), 400);
        });

        document.addEventListener('click', (event) => {
            const editButton = event.target.closest('[data-edit-id]');
            if (editButton) {
                openEditModal(editButton.dataset.editId);
                return;
            }

            const deleteButton = event.target.closest('[data-delete-id]');
            if (deleteButton) {
                openConfirmModal(deleteButton.dataset.deleteId, deleteButton.dataset.deleteTitle);
                return;
            }

            const useMovieButton = event.target.closest('.btn-use-movie');
            if (useMovieButton) {
                fillFormFromTmdb(useMovieButton);
            }
        });

        ['form-title', 'form-genre', 'form-rating', 'form-status', 'form-poster-file', 'edit-title', 'edit-genre', 'edit-rating', 'edit-status', 'edit-poster-file'].forEach((id) => {
            const field = query(id);
            if (!field) return;

            field.addEventListener('input', () => clearError(id));
            field.addEventListener('change', () => clearError(id));
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        attachEvents();
        listMovies();
    });
})();
