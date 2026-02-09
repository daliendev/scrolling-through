<?php

namespace App\Http\Controllers;

use App\Domain\Reading\Models\UserState;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * Redirect to the last book being read, or to upload page.
     */
    public function __invoke()
    {
        $userState = UserState::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($userState && $userState->book_id) {
            return redirect()->route('books.show', $userState->book_id);
        }

        return redirect()->route('books.upload');
    }
}
