<?php

namespace App\Http\Controllers\Reading;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetShareTextController extends Controller
{
    public function __invoke(Request $request, Book $book, Post $post): JsonResponse
    {
        $shareText = sprintf('"%s" — %s', $post->text, $book->title);

        return response()->json([
            'share_text' => $shareText,
        ]);
    }
}
