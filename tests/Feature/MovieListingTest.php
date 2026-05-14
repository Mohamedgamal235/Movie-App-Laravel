<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Movie;
use GuzzleHttp\Promise\Create;

class MovieListingTest extends TestCase
{
    use RefreshDatabase;
    public function test_user_can_get_all_movies(): void
    {
        Movie::create([
            'title' => 'Dune: Part Two',
            'genre' => 'Sci-Fi',
            'rating' => 8.5,
            'status' => 'Watched',
            'poster_path' => '/poster1.jpg',
            'tmdb_id' => 693134,
        ]);

        Movie::create([
            'title' => 'The Batman',
            'genre' => 'Action',
            'rating' => 7.5,
            'status' => 'Watching',
            'poster_path' => '/poster2.jpg',
            'tmdb_id' => 414906,
        ]);

        $response = $this->getJson(route('movies.all'));

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['title' => 'Dune: Part Two'])
            ->assertJsonFragment(['title' => 'The Batman']);
    }
}
