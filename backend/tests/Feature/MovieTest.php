<?php

namespace Tests\Feature;

use Tests\ApiTestCase;

class MovieTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMovies();
    }

    public function test_lists_all_twenty_movies_without_authentication(): void
    {
        $this->getJson('/api/movies')
            ->assertOk()
            ->assertJsonCount(20, 'data');
    }

    public function test_movie_shape_matches_what_the_frontend_expects(): void
    {
        $movie = $this->getJson('/api/movies')->json('data.0');
        $this->assertSame(1, $movie['id']);
        $this->assertSame('Inception', $movie['title']);
        $this->assertContains('Sci-Fi', $movie['genres']);
        $this->assertCount(3, $movie['genres']);
        $this->assertIsFloat($movie['rating']);
        $this->assertIsArray($movie['cast']);
        $this->assertCount(4, $movie['cast']);
        $this->assertArrayHasKey('poster_url', $movie);
        $this->assertArrayHasKey('duration', $movie);
    }

    public function test_filters_by_genre(): void
    {
        $this->getJson('/api/movies?genre=Sci-Fi')
            ->assertOk()
            ->assertJsonCount(7, 'data');
    }

    public function test_unknown_genre_returns_empty_list(): void
    {
        $this->getJson('/api/movies?genre=TidakAda')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_searches_title_and_director(): void
    {
        $this->getJson('/api/movies?search=nolan')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/movies?search=inception')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_shuffle_returns_the_same_movies_in_any_order(): void
    {
        $ids = collect($this->getJson('/api/movies?shuffle=1')->assertOk()->json('data'))
            ->pluck('id')->sort()->values()->all();
        $this->assertSame(range(1, 20), $ids);
    }

    public function test_default_order_is_by_id(): void
    {
        $ids = collect($this->getJson('/api/movies')->json('data'))->pluck('id')->all();
        $this->assertSame(range(1, 20), $ids);
    }

    public function test_shows_single_movie(): void
    {
        $this->getJson('/api/movies/10')
            ->assertOk()
            ->assertJsonPath('data.title', 'The Godfather')
            ->assertJsonPath('data.year', 1972);
    }

    public function test_unknown_movie_returns_404(): void
    {
        $this->getJson('/api/movies/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Film tidak ditemukan');
    }

    public function test_non_numeric_id_returns_json_404(): void
    {
        $this->getJson('/api/movies/abc')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}