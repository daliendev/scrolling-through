<?php

namespace App\Domain\Reading\Models;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Models\User;
use Database\Factories\UserStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserState extends Model
{
    /** @use HasFactory<\Database\Factories\UserStateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'current_post_id',
        'starred_post_ids',
        'notes',
        'posts_read',
    ];

    protected static function newFactory(): UserStateFactory
    {
        return UserStateFactory::new();
    }

    protected function casts(): array
    {
        return [
            'starred_post_ids' => 'array',
            'notes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function currentPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'current_post_id');
    }

    public function toggleStar(int $postId): bool
    {
        $starred = $this->starred_post_ids ?? [];

        if (in_array($postId, $starred)) {
            $this->starred_post_ids = array_values(array_diff($starred, [$postId]));
            $this->save();

            return false;
        } else {
            $starred[] = $postId;
            $this->starred_post_ids = $starred;
            $this->save();

            return true;
        }
    }

    public function addNote(int $postId, string $text): array
    {
        $notes = $this->notes ?? [];

        if (! isset($notes[$postId])) {
            $notes[$postId] = [];
        }

        $note = [
            'text' => $text,
            'timestamp' => now()->format('H:i'),
        ];

        $notes[$postId][] = $note;

        $this->notes = $notes;
        $this->save();

        return $note;
    }

    public function progressPercentage(): int
    {
        if ($this->book->total_posts === 0) {
            return 0;
        }

        return (int) round(($this->posts_read / $this->book->total_posts) * 100);
    }
}
