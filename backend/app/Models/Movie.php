<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    protected $fillable = [
        'title', 'year', 'director', 'rating', 'duration',
        'synopsis', 'poster_url', 'cast_members',
    ];

    protected $casts = [
        'year'         => 'integer',
        'rating'       => 'float',
        'cast_members' => 'array',
    ];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->using(GenreMovie::class);
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}