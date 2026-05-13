<?php

namespace App\Models;

use Database\Factories\MovieFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    /** @use HasFactory<MovieFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'genre',
        'rating',
        'notes',
        'status',
        'poster_path',
        'tmdb_id',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'tmdb_id' => 'integer',
        ];
    }
}
