<?php

namespace App\Http\Controllers\Books;

use App\Domain\Books\Models\Book;
use App\Domain\Reading\Models\UserState;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookFeedController extends Controller
{
    public function __invoke(Request $request, Book $book): JsonResponse
    {
        $book->load('posts');

        $userState = UserState::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
            ],
            [
                'starred_post_ids' => [],
                'notes' => [],
                'posts_read' => 0,
            ]
        );

        return response()->json([
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'total_posts' => $book->total_posts,
            ],
            'posts' => $book->posts->map(fn ($post) => [
                'id' => $post->id,
                'text' => $post->text,
                'type' => $post->type,
                'chapter_title' => $post->chapter_title,
                'position' => $post->position,
            ]),
            'userState' => [
                'current_post_id' => $userState->current_post_id,
                'starred_post_ids' => $userState->starred_post_ids ?? [],
                'notes' => $userState->notes ?? [],
                'posts_read' => $userState->posts_read,
                'progress_percentage' => $userState->progressPercentage(),
            ],
        ]);
    }
}
