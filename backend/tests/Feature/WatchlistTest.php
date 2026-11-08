<?php

namespace Tests\Feature;

use Tests\ApiTestCase;

class WatchlistTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMovies();
    }

    // ---------- auth guard ----------
    public function test_all_watchlist_routes_require_a_token(): void
    {
        $this->getJson('/api/watchlist')->assertUnauthorized();
        $this->postJson('/api/watchlist', ['movieId' => 1])->assertUnauthorized();
        $this->deleteJson('/api/watchlist/1')->assertUnauthorized();
    }

    // ---------- tambah ----------
    public function test_adds_a_movie_to_the_watchlist(): void
    {
        $user = $this->makeUser();
        $this->postJson('/api/watchlist', ['movieId' => 1], $this->bearer($user))
            ->assertCreated()
            ->assertJsonPath('data.movie_id', 1)
            ->assertJsonPath('data.movie.title', 'Inception');
        $this->assertDatabaseHas('watchlists', ['user_id' => $user->id, 'movie_id' => 1]);
    }

    public function test_accepts_snake_case_movie_id_too(): void
    {
        $user = $this->makeUser();
        $this->postJson('/api/watchlist', ['movie_id' => 2], $this->bearer($user))
            ->assertCreated()
            ->assertJsonPath('data.movie_id', 2);
    }

    public function test_duplicate_entry_returns_409_and_creates_no_second_row(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($user);
        $this->postJson('/api/watchlist', ['movieId' => 1], $headers)->assertCreated();
        $this->postJson('/api/watchlist', ['movieId' => 1], $headers)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Film sudah ada di watchlist');
        $this->assertDatabaseCount('watchlists', 1);
    }

    public function test_two_users_can_add_the_same_movie(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->postJson('/api/watchlist', ['movieId' => 1], $this->bearer($a))->assertCreated();
        $this->postJson('/api/watchlist', ['movieId' => 1], $this->bearer($b))->assertCreated();
        $this->assertDatabaseCount('watchlists', 2);
    }

    public function test_unknown_movie_returns_404_not_a_database_error(): void
    {
        $this->postJson('/api/watchlist', ['movieId' => 999], $this->bearer($this->makeUser()))
            ->assertNotFound()
            ->assertJsonPath('message', 'Film tidak ditemukan');
    }

    public function test_validates_movie_id(): void
    {
        $headers = $this->bearer($this->makeUser());
        $this->postJson('/api/watchlist', [], $headers)->assertStatus(422)->assertJsonValidationErrors('movie_id');
        $this->postJson('/api/watchlist', ['movieId' => 'abc'], $headers)->assertStatus(422);
        $this->postJson('/api/watchlist', ['movieId' => 0], $headers)->assertStatus(422);
    }

    // ---------- baca & isolasi ----------
    public function test_lists_only_the_current_users_watchlist(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->postJson('/api/watchlist', ['movieId' => 1], $this->bearer($a))->assertCreated();
        $this->postJson('/api/watchlist', ['movieId' => 2], $this->bearer($a))->assertCreated();
        $this->postJson('/api/watchlist', ['movieId' => 3], $this->bearer($b))->assertCreated();
        $this->getJson('/api/watchlist', $this->bearer($a))
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson('/api/watchlist', $this->bearer($b))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.movie_id', 3);
    }

    public function test_new_user_has_an_empty_watchlist(): void
    {
        $this->getJson('/api/watchlist', $this->bearer($this->makeUser()))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_watchlist_items_include_the_nested_movie(): void
    {
        $user = $this->makeUser();
        $this->postJson('/api/watchlist', ['movieId' => 4], $this->bearer($user));
        $this->getJson('/api/watchlist', $this->bearer($user))
            ->assertJsonPath('data.0.movie.title', 'Interstellar')
            ->assertJsonStructure(['data' => [['id', 'movie_id', 'created_at', 'movie' => ['id', 'title', 'genres']]]]);
    }

    // ---------- hapus ----------
    public function test_removes_a_movie_from_the_watchlist(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($user);
        $this->postJson('/api/watchlist', ['movieId' => 1], $headers);
        $this->deleteJson('/api/watchlist/1', [], $headers)->assertOk();
        $this->assertDatabaseMissing('watchlists', ['user_id' => $user->id, 'movie_id' => 1]);
    }

    public function test_removing_a_movie_that_is_not_in_the_watchlist_returns_404(): void
    {
        $this->deleteJson('/api/watchlist/1', [], $this->bearer($this->makeUser()))
            ->assertNotFound()
            ->assertJsonPath('message', 'Film tidak ada di watchlist');
    }

    public function test_user_cannot_remove_another_users_entry(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $this->postJson('/api/watchlist', ['movieId' => 1], $this->bearer($owner));
        $this->deleteJson('/api/watchlist/1', [], $this->bearer($intruder))->assertNotFound();
        $this->assertDatabaseHas('watchlists', ['user_id' => $owner->id, 'movie_id' => 1]);
    }
}