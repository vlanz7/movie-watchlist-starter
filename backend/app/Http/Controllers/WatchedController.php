<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWatchedRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $review = $request->user()->reviews()->create([
            'movie_id' => $data['movie_id'],
            'rating'   => $data['rating'],
            'review'   => $data['review'],
        ]);
        $review->load('movie.genres');
        return (new ReviewResource($review))->response()->setStatusCode(201);
    }
}