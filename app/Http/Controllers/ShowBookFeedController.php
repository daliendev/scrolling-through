<?php

namespace App\Http\Controllers;

use App\Domain\Books\Models\Book;
use Inertia\Inertia;
use Inertia\Response;

class ShowBookFeedController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Book $book): Response
    {
        $book->load(['posts' => fn ($query) => $query->orderBy('position')]);

        $userState = $book->userStates()
            ->where('user_id', auth()->id())
            ->first();

        return Inertia::render('BookFeed', [
            'book' => $book,
            'posts' => $book->posts,
            'userState' => $userState,
        ]);
    }
}
