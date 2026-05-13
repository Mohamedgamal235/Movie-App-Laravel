<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MovieController::class, 'index'])->name('home');

Route::prefix('movies')->name('movies.')->group(function (): void {
    Route::get('/', [MovieController::class, 'all'])->name('all');
    Route::post('/', [MovieController::class, 'store'])->name('store');
    Route::put('/{movie}', [MovieController::class, 'update'])->name('update');
    Route::delete('/{movie}', [MovieController::class, 'destroy'])->name('destroy');
});

Route::post('/posters/upload', [MovieController::class, 'uploadPoster'])->name('posters.upload');

Route::prefix('tmdb')->name('tmdb.')->group(function (): void {
    Route::get('/search', [MovieController::class, 'searchTmdb'])->name('search');
    Route::get('/movies/{tmdbId}', [MovieController::class, 'tmdbDetails'])->name('details');
});
