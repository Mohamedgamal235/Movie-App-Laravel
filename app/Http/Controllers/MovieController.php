<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MovieController extends Controller
{
    private const GENRES = [
        'Action',
        'Comedy',
        'Drama',
        'Horror',
        'Sci-Fi',
        'Romance',
        'Documentary',
        'Animation',
        'Thriller',
        'Fantasy',
        'Other',
    ];

    private const STATUSES = [
        'Watching',
        'Watched',
        'Plan to Watch',
    ];

    public function index(): View
    {
        return view('index', [
            'genres' => self::GENRES,
            'statuses' => self::STATUSES,
            'tmdbImageBaseUrl' => config('services.tmdb.image_base_url', 'https://image.tmdb.org/t/p/w500'),
        ]);
    }

    public function all(): JsonResponse
    {
        $movies = Movie::query()
            ->latest()
            ->get();

        return response()->json($movies);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateMovie($request);

        $movie = Movie::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Movie added successfully.',
                'data' => $movie,
            ], 201);
        }

        return redirect()->route('home')->with('success', 'Movie added successfully.');
    }

    public function update(Request $request, Movie $movie): JsonResponse|RedirectResponse
    {
        $validated = $this->validateMovie($request, $movie);

        $movie->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Movie updated successfully.',
                'data' => $movie->fresh(),
            ]);
        }

        return redirect()->route('home')->with('success', 'Movie updated successfully.');
    }

    public function destroy(Request $request, Movie $movie): JsonResponse|RedirectResponse
    {
        $movie->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Movie deleted successfully.',
            ]);
        }

        return redirect()->route('home')->with('success', 'Movie deleted successfully.');
    }

    public function uploadPoster(Request $request): JsonResponse
    {
        $request->validate([
            'poster_file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('poster_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = 'poster_' . Str::uuid() . '.' . $extension;

        $destination = public_path('uploads/posters');

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $file->move($destination, $fileName);

        return response()->json([
            'success' => true,
            'path' => '/uploads/posters/' . $fileName,
        ]);
    }

    public function searchTmdb(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $apiKey = config('services.tmdb.key');
        $baseUrl = rtrim(config('services.tmdb.base_url', 'https://api.themoviedb.org/3'), '/');
        $imageBaseUrl = rtrim(config('services.tmdb.image_base_url', 'https://image.tmdb.org/t/p/w500'), '/');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'TMDB API key is not configured. Add TMDB_API_KEY to your .env file.',
            ], 500);
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get($baseUrl . '/search/movie', [
                'api_key' => $apiKey,
                'language' => 'en-US',
                'query' => $validated['q'],
                'page' => 1,
                'include_adult' => 'false',
            ]);

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'error' => 'Could not reach TMDB right now.',
            ], 502);
        }

        $results = collect($response->json('results', []))
            ->take(8)
            ->map(function (array $movie) use ($imageBaseUrl): array {
                $posterPath = $movie['poster_path'] ?? '';

                return [
                    'tmdb_id' => (int) ($movie['id'] ?? 0),
                    'title' => (string) ($movie['title'] ?? ''),
                    'overview' => (string) ($movie['overview'] ?? ''),
                    'poster_path' => $posterPath,
                    'poster_url' => $posterPath ? $imageBaseUrl . $posterPath : '',
                    'vote_average' => round((float) ($movie['vote_average'] ?? 0), 1),
                    'release_year' => substr((string) ($movie['release_date'] ?? ''), 0, 4),
                    'genre_ids' => $movie['genre_ids'] ?? [],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function tmdbDetails(int $tmdbId): JsonResponse
    {
        if ($tmdbId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid TMDB ID.',
            ], 422);
        }

        $apiKey = config('services.tmdb.key');
        $baseUrl = rtrim(config('services.tmdb.base_url', 'https://api.themoviedb.org/3'), '/');
        $imageBaseUrl = rtrim(config('services.tmdb.image_base_url', 'https://image.tmdb.org/t/p/w500'), '/');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'TMDB API key is not configured. Add TMDB_API_KEY to your .env file.',
            ], 500);
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get($baseUrl . '/movie/' . $tmdbId, [
                'api_key' => $apiKey,
                'language' => 'en-US',
            ]);

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'error' => 'Movie details unavailable.',
            ], 502);
        }

        $data = $response->json();
        $posterPath = $data['poster_path'] ?? '';

        return response()->json([
            'success' => true,
            'data' => [
                'tmdb_id' => (int) ($data['id'] ?? 0),
                'title' => (string) ($data['title'] ?? ''),
                'overview' => (string) ($data['overview'] ?? ''),
                'poster_path' => $posterPath,
                'poster_url' => $posterPath ? $imageBaseUrl . $posterPath : '',
                'vote_average' => round((float) ($data['vote_average'] ?? 0), 1),
                'release_year' => substr((string) ($data['release_date'] ?? ''), 0, 4),
                'genres' => collect($data['genres'] ?? [])->pluck('name')->values(),
                'runtime' => (int) ($data['runtime'] ?? 0),
            ],
        ]);
    }

    private function validateMovie(Request $request, ?Movie $movie = null): array
    {
        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('movies', 'title')->ignore($movie?->id),
            ],
            'genre' => ['required', 'string', Rule::in(self::GENRES)],
            'rating' => ['required', 'numeric', 'between:1,10'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(self::STATUSES)],
            'poster_path' => ['required', 'string', 'max:2048'],
            'tmdb_id' => ['nullable', 'integer'],
        ]);
    }
}
