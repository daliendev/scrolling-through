<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;

uses()->group('books');

it('can create a book with title and file path', function () {
    $book = Book::factory()->create([
        'title' => 'Test Book',
        'file_path' => 'books/test.epub',
    ]);

    expect($book->title)->toBe('Test Book')
        ->and($book->file_path)->toBe('books/test.epub')
        ->and($book->total_posts)->toBe(0);
});

it('has many posts relationship', function () {
    $book = Book::factory()->create();
    Post::factory()->count(3)->create(['book_id' => $book->id]);

    expect($book->posts)->toHaveCount(3)
        ->and($book->posts->first())->toBeInstanceOf(Post::class);
});

it('orders posts by position', function () {
    $book = Book::factory()->create();
    Post::factory()->create(['book_id' => $book->id, 'position' => 2]);
    Post::factory()->create(['book_id' => $book->id, 'position' => 0]);
    Post::factory()->create(['book_id' => $book->id, 'position' => 1]);

    $positions = $book->posts->pluck('position')->toArray();

    expect($positions)->toBe([0, 1, 2]);
});
