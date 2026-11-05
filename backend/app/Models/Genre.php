<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class)->using(GenreMovie::class);
    }
}