<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WatchlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'movie_id'   => $this->movie_id,
            'movie'      => new MovieResource($this->whenLoaded('movie')),
            'created_at' => $this->created_at,
        ];
    }
}