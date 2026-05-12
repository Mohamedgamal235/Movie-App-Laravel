@extends('layouts.app')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header">
    <h2>My Movie List</h2>
    <a href="{{ route('movie.create') }}" class="btn btn-primary">+ Add Movie</a>
</div>

@if($data->count())
    <div class="movies-grid">
        @foreach($data as $movie)
            <div class="movie-card" data-id="{{ $movie->id }}">
                {{-- Poster --}}
                @if($movie->poster_path)
                    <img class="card-poster"
                         src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                         alt="{{ $movie->title }}"
                         onerror="this.style.display='none'">
                @else
                    <div class="no-poster">🎬</div>
                @endif

                <div class="card-body">
                    <div class="card-title">{{ $movie->title }}</div>
                    <div class="card-meta">{{ $movie->genre }}</div>
                    <div class="card-meta">⭐ {{ $movie->rating }}/10</div>

                    @php
                        $statusClass = match($movie->status) {
                            'Watched'      => 'status-watched',
                            'Watching'     => 'status-watching',
                            'Plan to Watch'=> 'status-plan',
                            default        => ''
                        };
                    @endphp
                    <span class="card-status {{ $statusClass }}">{{ $movie->status }}</span>

                    @if($movie->notes)
                        <p class="card-notes">{{ Str::limit($movie->notes, 80) }}</p>
                    @endif

                    <div class="card-actions">
                        <button class="btn btn-edit" onclick="openEditModal(
                            {{ $movie->id }},
                            '{{ addslashes($movie->title) }}',
                            '{{ addslashes($movie->genre) }}',
                            {{ $movie->rating }},
                            '{{ addslashes($movie->notes ?? '') }}',
                            '{{ $movie->status }}',
                            '{{ $movie->poster_path ?? '' }}'
                        )">Edit</button>

                        <button class="btn btn-delete" onclick="deleteMovie({{ $movie->id }})">Delete</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div style="margin-top:2rem; display:flex; justify-content:center;">
        {{ $data->links() }}
    </div>

@else
    <p style="text-align:center; color:#888; margin-top:3rem;">No movies yet. <a href="{{ route('movie.create') }}" style="color:#e94560;">Add one!</a></p>
@endif

{{-- Edit Modal --}}
<div class="modal-overlay" id="edit-modal">
    <div class="modal">
        <h2>Edit Movie</h2>
        <input type="hidden" id="edit-id">

        <div class="form-group">
            <label>Title</label>
            <input type="text" id="edit-title">
        </div>
        <div class="form-group">
            <label>Genre</label>
            <input type="text" id="edit-genre">
        </div>
        <div class="form-group">
            <label>Rating (0–10)</label>
            <input type="number" id="edit-rating" min="0" max="10" step="0.1">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select id="edit-status">
                <option value="Watching">Watching</option>
                <option value="Watched">Watched</option>
                <option value="Plan to Watch">Plan to Watch</option>
            </select>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea id="edit-notes"></textarea>
        </div>
        <div class="form-group">
            <label>Poster Path</label>
            <input type="text" id="edit-poster" placeholder="/xxxxxx.jpg">
        </div>

        <p class="error-msg" id="edit-error"></p>

        <div class="modal-actions">
            <button class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
            <button class="btn btn-edit" onclick="closeEditModal()">Cancel</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ── Open modal pre-filled ────────────────────────────────────
function openEditModal(id, title, genre, rating, notes, status, poster) {
    document.getElementById('edit-id').value     = id;
    document.getElementById('edit-title').value  = title;
    document.getElementById('edit-genre').value  = genre;
    document.getElementById('edit-rating').value = rating;
    document.getElementById('edit-notes').value  = notes;
    document.getElementById('edit-status').value = status;
    document.getElementById('edit-poster').value = poster;
    document.getElementById('edit-error').textContent = '';
    document.getElementById('edit-modal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('active');
}

// ── Submit Edit ──────────────────────────────────────────────
async function submitEdit() {
    const id          = document.getElementById('edit-id').value;
    const title       = document.getElementById('edit-title').value.trim();
    const genre       = document.getElementById('edit-genre').value.trim();
    const rating      = document.getElementById('edit-rating').value;
    const notes       = document.getElementById('edit-notes').value.trim();
    const status      = document.getElementById('edit-status').value;
    const poster_path = document.getElementById('edit-poster').value.trim();

    document.getElementById('edit-error').textContent = '';

    if (!title || !genre || !rating || !status) {
        document.getElementById('edit-error').textContent = 'Please fill all required fields.';
        return;
    }

    try {
        const res = await fetch(`/movie/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                _method: 'PUT',
                _token: getCsrfToken(),
                title, genre, rating, notes, status, poster_path
            })
        });

        const data = await res.json();

        if (data.success) {
            updateCardInDOM(id, title, genre, rating, notes, status, poster_path);
            closeEditModal();
            showToast('Movie updated successfully!', 'success');
        } else {
            const errors = data.errors
                ? Object.values(data.errors).flat().join(' | ')
                : (data.message || 'Update failed.');
            document.getElementById('edit-error').textContent = errors;
        }
    } catch (err) {
        document.getElementById('edit-error').textContent = 'Network error. Try again.';
    }
}

// ── Delete ───────────────────────────────────────────────────
async function deleteMovie(id) {
    if (!confirm('Delete this movie? This cannot be undone.')) return;

    try {
        const res = await fetch(`/movie/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                _method: 'DELETE',
                _token: getCsrfToken()
            })
        });

        const data = await res.json();

        if (data.success) {
            removeCardFromDOM(id);
            showToast('Movie deleted.', 'success');
        } else {
            showToast(data.message || 'Delete failed.', 'error');
        }
    } catch (err) {
        showToast('Network error. Try again.', 'error');
    }
}

// ── DOM helpers ──────────────────────────────────────────────
function updateCardInDOM(id, title, genre, rating, notes, status, poster) {
    const card = document.querySelector(`.movie-card[data-id="${id}"]`);
    if (!card) return;

    card.querySelector('.card-title').textContent = title;
    card.querySelectorAll('.card-meta')[0].textContent = `🎭 ${genre}`;
    card.querySelectorAll('.card-meta')[1].textContent = `⭐ ${rating}/10`;
    card.querySelector('.card-status').textContent = status;

    const notesEl = card.querySelector('.card-notes');
    if (notesEl) notesEl.textContent = notes.substring(0, 80);

    // Update the edit button with new values
    card.querySelector('.btn-edit').setAttribute('onclick',
        `openEditModal(${id}, '${escapeQ(title)}', '${escapeQ(genre)}', ${rating}, '${escapeQ(notes)}', '${status}', '${escapeQ(poster)}')`
    );
}

function removeCardFromDOM(id) {
    const card = document.querySelector(`.movie-card[data-id="${id}"]`);
    if (!card) return;
    card.style.transition = 'opacity 0.3s';
    card.style.opacity = '0';
    setTimeout(() => card.remove(), 300);
}

function escapeQ(str) {
    return String(str).replace(/'/g, "\\'");
}

// Close modal on overlay click
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endsection
