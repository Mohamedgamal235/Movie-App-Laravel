@extends('layouts.app')

@section('content')

<div style="max-width: 550px; margin: 0 auto;">
    <h2 style="margin-bottom: 1.5rem;">Add New Movie</h2>

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('movie.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" value="{{ old('title') }}" required>
        </div>
        <div class="form-group">
            <label>Genre *</label>
            <input type="text" name="genre" value="{{ old('genre') }}" required>
        </div>
        <div class="form-group">
            <label>Rating (0–10) *</label>
            <input type="number" name="rating" value="{{ old('rating') }}" min="0" max="10" step="0.1" required>
        </div>
        <div class="form-group">
            <label>Status *</label>
            <select name="status" required>
                <option value="">— Select —</option>
                <option value="Watching"      {{ old('status') == 'Watching'       ? 'selected' : '' }}>Watching</option>
                <option value="Watched"       {{ old('status') == 'Watched'        ? 'selected' : '' }}>Watched</option>
                <option value="Plan to Watch" {{ old('status') == 'Plan to Watch'  ? 'selected' : '' }}>Plan to Watch</option>
            </select>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes">{{ old('notes') }}</textarea>
        </div>
        <div class="form-group">
            <label>Poster Path (from TMDB)</label>
            <input type="text" name="poster_path" value="{{ old('poster_path') }}" placeholder="/xxxxxx.jpg">
        </div>

        <div style="display:flex; gap:1rem; margin-top:1rem;">
            <button type="submit" class="btn btn-primary">Add Movie</button>
            <a href="{{ route('movie.index') }}" class="btn btn-edit">Cancel</a>
        </div>
    </form>
</div>

@endsection
