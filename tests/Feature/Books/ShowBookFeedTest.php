<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Models\User;

test('user can view book feed', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();
    $posts = Post::factory()->count(5)->create(['book_id' => $book->id]);

    $response = $this->actingAs($user)->get("/books/{$book->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('BookFeed')
        ->has('book')
        ->has('posts', 5)
    );
});

test('book feed includes user state when available', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();
    $posts = Post::factory()->count(3)->create(['book_id' => $book->id]);

    $userState = UserState::factory()->create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'current_post_id' => $posts->first()->id,
        'starred_post_ids' => [$posts->first()->id],
        'posts_read' => 1,
    ]);

    $response = $this->actingAs($user)->get("/books/{$book->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('BookFeed')
        ->where('userState.id', $userState->id)
        ->where('userState.current_post_id', $posts->first()->id)
    );
});

test('book feed includes null user state when not available', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();
    Post::factory()->count(3)->create(['book_id' => $book->id]);

    $response = $this->actingAs($user)->get("/books/{$book->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('BookFeed')
        ->where('userState', null)
    );
});
