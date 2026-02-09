<?php

namespace App\Http\Controllers\Reading;

use App\Domain\Books\Models\Book;
use App\Domain\Reading\Models\UserState;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProgressRequest;
use Illuminate\Http\RedirectResponse;

class UpdateProgressController extends Controller
{
    public function __invoke(UpdateProgressRequest $request, Book $book): RedirectResponse
    {
        UserState::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
            ],
            [
                'current_post_id' => $request->input('current_post_id'),
                'posts_read' => $request->input('posts_read'),
            ]
        );

        return back();
    }
}
