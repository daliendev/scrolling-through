<?php

use App\Domain\Books\Models\Book;
use App\Domain\Reading\Models\UserState;
use App\Models\User;

test('home redirects to upload page when user has no books', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('books.upload'));
});

test('home redirects to last read book when user has reading history', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $userState = UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('books.show', $book->id));
});

test('home redirects to most recently updated book when multiple exist', function () {
    $user = User::factory()->create();
    $book1 = Book::factory()->create();
    $book2 = Book::factory()->create();

    // Create first state
    UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book1->id,
        'updated_at' => now()->subDay(),
    ]);

    // Create second state (more recent)
    UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book2->id,
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('books.show', $book2->id));
});
