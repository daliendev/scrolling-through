<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;

uses()->group('books');

it('can create a paragraph post', function () {
    $book = Book::factory()->create();
    $post = Post::factory()->create([
        'book_id' => $book->id,
        'text' => 'This is a paragraph.',
        'type' => 'paragraph',
        'position' => 0,
    ]);

    expect($post->text)->toBe('This is a paragraph.')
        ->and($post->type)->toBe('paragraph')
        ->and($post->position)->toBe(0)
        ->and($post->isChapter())->toBeFalse();
});

it('can create a chapter post', function () {
    $book = Book::factory()->create();
    $post = Post::factory()->chapter()->create([
        'book_id' => $book->id,
        'chapter_title' => 'Chapter 1',
    ]);

    expect($post->type)->toBe('chapter')
        ->and($post->chapter_title)->toBe('Chapter 1')
        ->and($post->isChapter())->toBeTrue();
});

it('belongs to a book', function () {
    $book = Book::factory()->create();
    $post = Post::factory()->create(['book_id' => $book->id]);

    expect($post->book)->toBeInstanceOf(Book::class)
        ->and($post->book->id)->toBe($book->id);
});

it('can calculate text length', function () {
    $post = Post::factory()->create(['text' => 'Hello']);

    expect($post->textLength())->toBe(5);
});

it('handles multibyte characters in text length', function () {
    $post = Post::factory()->create(['text' => 'Héllo']);

    expect($post->textLength())->toBe(5);
});
