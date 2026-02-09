<?php

namespace App\Http\Controllers\Reading;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddNoteRequest;
use Illuminate\Http\RedirectResponse;

class AddNoteController extends Controller
{
    public function __invoke(AddNoteRequest $request, Book $book, Post $post): RedirectResponse
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

        $userState->addNote($post->id, $request->input('text'));

        return back();
    }
}
