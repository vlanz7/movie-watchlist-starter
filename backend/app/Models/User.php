<?php

namespace App\Models;

use App\Models\Review;
use App\Models\Watchlist;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['username', 'email', 'password'];
    protected $hidden = ['password'];

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}