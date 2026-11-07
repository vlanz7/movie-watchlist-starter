<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MovieController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'genre'  => ['sometimes', 'string', 'max:50'],
            'search' => ['sometimes', 'string', 'max:100'],
        ]);
        $movies = Movie::query()
            ->with('genres') 
            ->when($filters['genre'] ?? null, function ($query, $genre) {
                $query->whereHas('genres', fn ($q) => $q->where('name', $genre));
            })
            ->when($filters['search'] ?? null, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                      ->orWhere('director', 'like', "%{$term}%");
                });
            })
            ->when(
                $request->boolean('shuffle'),
                fn ($query) => $query->inRandomOrder(),
                fn ($query) => $query->orderBy('id')
            )
            ->get();
        return MovieResource::collection($movies);
    }

    public function show(int $id): MovieResource|JsonResponse
    {
        $movie = Movie::with('genres')->find($id);
        if (! $movie) {
            return response()->json(['message' => 'Film tidak ditemukan'], 404);
        }
        return new MovieResource($movie);
    }
}