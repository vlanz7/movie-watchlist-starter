<?php

namespace Tests\Feature;

use Tests\ApiTestCase;

class WatchedTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMovies();
    }

    public function test_watched_routes_require_a_token(): void
    {
        $this->getJson('/api/watched')->assertUnauthorized();
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 5, 'review' => 'x'])->assertUnauthorized();
    }

    public function test_logs_a_movie_as_watched_with_rating_and_review(): void
    {
        $user = $this->makeUser();
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 5, 'review' => 'Keren banget'], $this->bearer($user))
            ->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.review', 'Keren banget')
            ->assertJsonPath('data.movie_id', 1)
            ->assertJsonPath('data.movie.title', 'Inception');
        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'movie_id' => 1, 'rating' => 5]);
    }

    public function test_accepts_the_opinion_alias_for_review(): void
    {
        $this->postJson('/api/watched', ['movie_id' => 2, 'rating' => 4, 'opinion' => 'Bagus'], $this->bearer($this->makeUser()))
            ->assertCreated()
            ->assertJsonPath('data.review', 'Bagus');
    }

    public function test_rating_must_be_an_integer_between_1_and_5(): void
    {
        $headers = $this->bearer($this->makeUser());
        foreach ([0, 6, -1, 3.5, 'bagus'] as $badRating) {
            $this->postJson('/api/watched', ['movieId' => 1, 'rating' => $badRating, 'review' => 'x'], $headers)
                ->assertStatus(422)
                ->assertJsonValidationErrors('rating');
        }
        foreach ([1, 2, 3, 4, 5] as $goodRating) {
            $this->postJson('/api/watched', ['movieId' => 1, 'rating' => $goodRating, 'review' => 'x'], $headers)
                ->assertCreated();
        }
    }

    public function test_review_text_is_required(): void
    {
        $headers = $this->bearer($this->makeUser());
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 5], $headers)
            ->assertStatus(422)->assertJsonValidationErrors('review');
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 5, 'review' => '   '], $headers)
            ->assertStatus(422)->assertJsonValidationErrors('review');
    }

    public function test_unknown_movie_returns_404(): void
    {
        $this->postJson('/api/watched', ['movieId' => 999, 'rating' => 5, 'review' => 'x'], $this->bearer($this->makeUser()))
            ->assertNotFound();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_the_same_movie_can_be_logged_more_than_once_diary_model(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($user);
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 4, 'review' => 'Tontonan pertama'], $headers)->assertCreated();
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 5, 'review' => 'Nonton ulang'], $headers)->assertCreated();
        $this->assertDatabaseCount('reviews', 2);
        $this->getJson('/api/watched', $headers)->assertJsonCount(2, 'data');
    }

    public function test_owner_is_taken_from_the_token_not_from_the_request_body(): void
    {
        $me = $this->makeUser();
        $victim = $this->makeUser();
        $this->postJson('/api/watched', [
            'movieId' => 1, 'rating' => 5, 'review' => 'x',
            'user_id' => $victim->id, // upaya menulis atas nama user lain
        ], $this->bearer($me))->assertCreated();
        $this->assertDatabaseHas('reviews', ['user_id' => $me->id]);
        $this->assertDatabaseMissing('reviews', ['user_id' => $victim->id]);
    }

    public function test_history_contains_only_the_current_users_entries(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 5, 'review' => 'milik A'], $this->bearer($a));
        $this->postJson('/api/watched', ['movieId' => 2, 'rating' => 3, 'review' => 'milik B'], $this->bearer($b));
        $this->getJson('/api/watched', $this->bearer($a))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.review', 'milik A');
    }

    public function test_history_is_ordered_newest_first(): void
    {
        $headers = $this->bearer($this->makeUser());
        $this->postJson('/api/watched', ['movieId' => 1, 'rating' => 3, 'review' => 'pertama'], $headers);
        $this->travel(5)->minutes();
        $this->postJson('/api/watched', ['movieId' => 2, 'rating' => 4, 'review' => 'kedua'], $headers);
        $this->getJson('/api/watched', $headers)
            ->assertJsonPath('data.0.review', 'kedua')
            ->assertJsonPath('data.1.review', 'pertama');
    }
}