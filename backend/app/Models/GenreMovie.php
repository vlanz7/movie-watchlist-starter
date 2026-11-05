<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GenreMovie extends Pivot
{
    protected $table = 'genre_movie';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['movie_id', 'genre_id'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }
}