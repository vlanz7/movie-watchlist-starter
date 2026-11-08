<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWatchedRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Movie;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class WatchedController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reviews = $request->user()
            ->reviews()
            ->with('movie.genres')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        return ReviewResource::collection($reviews);
    }

    public function store(StoreWatchedRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (! Movie::whereKey($data['movie_id'])->exists()) {
            return response()->json(['message' => 'Film tidak ditemukan'], 404);
        }
        $user = $request->user();
        try {
            [$review, $removed] = DB::transaction(function () use ($user, $data) {
                $review = $user->reviews()->create([
                    'movie_id' => $data['movie_id'],
                    'rating'   => $data['rating'],
                    'review'   => $data['review'],
                ]);
                $removed = $user->watchlists()->where('movie_id', $data['movie_id'])->delete() > 0;
                return [$review, $removed];
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Film ini sudah pernah kamu review'], 409);
        }
        $review->load('movie.genres');
        return (new ReviewResource($review))
            ->additional(['meta' => ['removed_from_watchlist' => $removed]])
            ->response()
            ->setStatusCode(201);
    }
}