<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWatchlistRequest;
use App\Http\Resources\WatchlistResource;
use App\Models\Movie;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WatchlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $request->user()
            ->watchlists()
            ->with('movie.genres')
            ->orderByDesc('id')
            ->get();
        return WatchlistResource::collection($items);
    }

    public function store(StoreWatchlistRequest $request): JsonResponse
    {
        $movieId = $request->integer('movie_id');
        if (! Movie::whereKey($movieId)->exists()) {
            return response()->json(['message' => 'Film tidak ditemukan'], 404);
        }
        try {
            $item = $request->user()->watchlists()->create(['movie_id' => $movieId]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Film sudah ada di watchlist'], 409);
        }
        $item->load('movie.genres');
        return (new WatchlistResource($item))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $movieId): JsonResponse
    {
        $deleted = $request->user()
            ->watchlists()
            ->where('movie_id', $movieId)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Film tidak ada di watchlist'], 404);
        }
        return response()->json(['message' => 'Film dihapus dari watchlist']);
    }
}