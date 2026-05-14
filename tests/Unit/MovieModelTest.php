<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Movie;

class MovieModelTest extends TestCase
{

    use RefreshDatabase;
    public function test_movie_can_be_created_with_tmdb_id()
    {
        $movie = Movie::create([
            'title' => 'Interstellar',
            'genre' => 'Sci-Fi',
            'rating' => 9.0,
            'notes' => 'Amazing space movie',
            'status' => 'Watched',
            'poster_path' => '/posters/interstellar.jpg',
            'tmdb_id' => 157336,
        ]);

        $this->assertDatabaseHas('movies', ['title' => 'Interstellar']);
        $this->assertEquals('Sci-Fi', $movie->genre);
        $this->assertEquals(9.0, $movie->rating);
        $this->assertEquals(157336, $movie->tmdb_id);
    }

    public function test_tmdb_id_is_cast_to_integer()
    {
        $movie = Movie::create([
            'title' => 'Test',
            'genre' => 'Drama',
            'rating' => 8.0,
            'status' => 'Watching',
            'poster_path' => '/test.jpg',
            'tmdb_id' => '693134',
        ]);

        $this->assertIsInt($movie->tmdb_id);
        $this->assertEquals(693134, $movie->tmdb_id);
    }
}
