<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'file_path',
        'total_posts',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->orderBy('position');
    }

    public function userStates(): HasMany
    {
        return $this->hasMany(UserState::class);
    }
}
