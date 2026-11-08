<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\GenreMovie;
use App\Models\Movie;
use App\Models\Watchlist;
use Database\Seeders\MovieSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\ApiTestCase;

class DatabaseTest extends ApiTestCase
{
    public function test_seeder_loads_the_full_dataset(): void
    {
        $this->seedMovies();
        $this->assertSame(20, Movie::count());
        $this->assertSame(15, Genre::count());
        $this->assertSame(49, GenreMovie::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seedMovies();
        $this->seed(MovieSeeder::class);
        $this->assertSame(20, Movie::count());
        $this->assertSame(15, Genre::count());
        $this->assertSame(49, GenreMovie::count());
    }

    public function test_movie_casts_and_genre_relationship(): void
    {
        $this->seedMovies();
        $movie = Movie::with('genres')->findOrFail(1);
        $this->assertSame('Inception', $movie->title);
        $this->assertIsFloat($movie->rating);
        $this->assertSame(8.8, $movie->rating);
        $this->assertIsArray($movie->cast_members);
        $this->assertContains('Tom Hardy', $movie->cast_members);
        $this->assertEqualsCanonicalizing(['Sci-Fi', 'Action', 'Thriller'], $movie->genres->pluck('name')->all());
    }

    public function test_genre_has_many_movies(): void
    {
        $this->seedMovies();
        $this->assertSame(7, Genre::where('name', 'Sci-Fi')->firstOrFail()->movies()->count());
    }

    public function test_database_rejects_duplicate_watchlist_rows(): void
    {
        $this->seedMovies();
        $user = $this->makeUser();
        $user->watchlists()->create(['movie_id' => 1]);
        $this->expectException(UniqueConstraintViolationException::class);
        $user->watchlists()->create(['movie_id' => 1]);
    }

    public function test_database_rejects_duplicate_reviews_for_the_same_user_and_movie(): void
    {
        $this->seedMovies();
        $user = $this->makeUser();
        $user->reviews()->create(['movie_id' => 1, 'rating' => 5, 'review' => 'x']);
        $this->expectException(UniqueConstraintViolationException::class);
        $user->reviews()->create(['movie_id' => 1, 'rating' => 4, 'review' => 'y']);
    }

    public function test_deleting_a_user_cascades_to_their_data(): void
    {
        $this->seedMovies();
        $user = $this->makeUser();
        $user->watchlists()->create(['movie_id' => 1]);
        $user->reviews()->create(['movie_id' => 1, 'rating' => 5, 'review' => 'x']);
        $user->delete();
        $this->assertSame(0, Watchlist::count());
        $this->assertDatabaseCount('reviews', 0);
    }
}