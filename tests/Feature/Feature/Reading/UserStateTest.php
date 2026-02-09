<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Models\User;

uses()->group('reading');

it('can create a user state for a book', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $state = UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);

    expect($state->user_id)->toBe($user->id)
        ->and($state->book_id)->toBe($book->id)
        ->and($state->posts_read)->toBe(0);
});

it('can toggle starred posts', function () {
    $state = UserState::factory()->create();

    // Star a post
    $state->toggleStar(1);
    expect($state->starred_post_ids)->toContain(1);

    // Unstar the same post
    $state->toggleStar(1);
    expect($state->starred_post_ids)->not->toContain(1);
});

it('can add notes to posts', function () {
    $state = UserState::factory()->create();

    $state->addNote(1, 'This is a great paragraph!');

    expect($state->notes)->toHaveKey('1')
        ->and($state->notes['1'])->toHaveCount(1)
        ->and($state->notes['1'][0]['text'])->toBe('This is a great paragraph!')
        ->and($state->notes['1'][0])->toHaveKey('timestamp');
});

it('can add multiple notes to the same post', function () {
    $state = UserState::factory()->create();

    $state->addNote(1, 'First note');
    $state->addNote(1, 'Second note');

    expect($state->notes['1'])->toHaveCount(2)
        ->and($state->notes['1'][0]['text'])->toBe('First note')
        ->and($state->notes['1'][1]['text'])->toBe('Second note');
});

it('calculates progress percentage', function () {
    $book = Book::factory()->create(['total_posts' => 100]);
    $state = UserState::factory()->create([
        'book_id' => $book->id,
        'posts_read' => 50,
    ]);

    expect($state->progressPercentage())->toBe(50);
});

it('returns zero progress when no posts exist', function () {
    $book = Book::factory()->create(['total_posts' => 0]);
    $state = UserState::factory()->create([
        'book_id' => $book->id,
        'posts_read' => 0,
    ]);

    expect($state->progressPercentage())->toBe(0);
});

it('has unique constraint on user_id and book_id', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);

    // Trying to create duplicate should fail
    expect(fn () => UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
