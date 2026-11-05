<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['movie_id', 'rating', 'review'];
    protected $casts = ['rating' => 'integer',];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movies(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}