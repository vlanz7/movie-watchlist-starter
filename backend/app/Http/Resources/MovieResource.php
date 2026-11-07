<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'year'       => $this->year,
            'director'   => $this->director,
            'genres'     => $this->whenLoaded('genres', fn () => $this->genres->pluck('name')->values()->all(), []),
            'rating'     => $this->rating,            
            'duration'   => $this->duration,
            'synopsis'   => $this->synopsis,
            'poster_url' => $this->poster_url,
            'cast'       => $this->cast_members,     
        ];
    }
}