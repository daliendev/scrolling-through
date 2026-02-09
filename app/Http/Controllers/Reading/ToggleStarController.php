<?php

namespace App\Http\Controllers\Reading;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleStarController extends Controller
{
    public function __invoke(Request $request, Book $book, Post $post): RedirectResponse
    {
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

        $userState->toggleStar($post->id);

        return back();
    }
}
