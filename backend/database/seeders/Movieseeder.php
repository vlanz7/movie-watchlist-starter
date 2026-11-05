<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('../data/movies.json');

        if (! is_file($path)) {
            throw new \RuntimeException("File dataset tidak ditemukan: {$path}");
        }

        $movies = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $now = now();

        DB::transaction(function () use ($movies, $now) {
            $rows = array_map(fn ($m) => [
                'id'           => $m['id'],
                'title'        => $m['title'],
                'year'         => $m['year'],
                'director'     => $m['director'],
                'rating'       => $m['rating'],
                'duration'     => $m['duration'],
                'synopsis'     => $m['synopsis'],
                'poster_url'   => $m['poster_url'],
                'cast_members' => json_encode($m['cast'], JSON_UNESCAPED_UNICODE),
                'created_at'   => $now,
                'updated_at'   => $now,
            ], $movies);

            DB::table('movies')->upsert(
                $rows,
                ['id'],
                ['title', 'year', 'director', 'rating', 'duration', 'synopsis', 'poster_url', 'cast_members', 'updated_at']
            );

            $genreNames = collect($movies)->pluck('genres')->flatten()->unique()->values();
            DB::table('genres')->upsert(
                $genreNames->map(fn ($name) => ['name' => $name])->all(),
                ['name']
            );
            $genreIds = DB::table('genres')->pluck('id', 'name');

            DB::table('genre_movie')->whereIn('movie_id', collect($movies)->pluck('id'))->delete();

            $pivot = [];
            foreach ($movies as $m) {
                foreach (array_unique($m['genres']) as $name) {
                    $pivot[] = ['movie_id' => $m['id'], 'genre_id' => $genreIds[$name]];
                }
            }
            DB::table('genre_movie')->insert($pivot);
        });
    }
}