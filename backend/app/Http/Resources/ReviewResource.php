<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'movie_id'   => $this->movie_id,
            'rating'     => $this->rating,   // 1-5 (integer)
            'review'     => $this->review,
            'created_at' => $this->created_at,
            'movie'      => new MovieResource($this->whenLoaded('movie')),
        ];
    }
}