<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'book_id',
        'text',
        'type',
        'chapter_title',
        'position',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function isChapter(): bool
    {
        return $this->type === 'chapter';
    }

    public function textLength(): int
    {
        return mb_strlen($this->text);
    }
}
