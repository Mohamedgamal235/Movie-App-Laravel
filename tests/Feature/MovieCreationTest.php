<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Movie;

class MovieCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_movie(){
        $movieData = [
            'title' => 'Inception',
            'genre' => 'Sci-Fi',
            'rating' => 8.5,
            'notes' => 'A mind-bending masterpiece',
            'status' => 'Watched',
            'poster_path' => '/uploads/posters/test.jpg',
            'tmdb_id' => 27205
        ];
        $response = $this->postJson(route('movies.store'),$movieData);
        $response->assertStatus(201)->assertJson(
            [
                'success' => true,
                'message' => 'Movie added successfully.'
            ]
        );
        $this->assertDatabaseHas('movies',[
            'title' => 'Inception',
            'genre' => 'Sci-Fi',
            'rating' => 8.5,
            'tmdb_id' => 27205
        ]);
    }

    public function test_movie_title_must_be_unique()
    {
        Movie::create([
            'title' => 'Inception',
            'genre' => 'Sci-Fi',
            'rating' => 8.5,
            'status' => 'Watched',
            'poster_path' => '/test.jpg',
            'tmdb_id' => 27205,
        ]);

        $response = $this->postJson(route('movies.store'), [
            'title' => 'Inception',
            'genre' => 'Drama',
            'rating' => 8.0,
            'status' => 'Watching',
            'poster_path' => '/test2.jpg',
        ]);

        $response->assertStatus(422);
    }
}

