<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Models\User;

uses()->group('reading');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->book = Book::factory()->create(['total_posts' => 10]);
    $this->posts = Post::factory()->count(10)->create(['book_id' => $this->book->id]);
});

it('can update reading progress', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/progress", [
            'current_post_id' => $this->posts[5]->id,
            'posts_read' => 6,
        ]);

    $response->assertSuccessful();

    $state = UserState::where('user_id', $this->user->id)
        ->where('book_id', $this->book->id)
        ->first();

    expect($state->current_post_id)->toBe($this->posts[5]->id)
        ->and($state->posts_read)->toBe(6)
        ->and($state->progressPercentage())->toBe(60);
});

it('creates user state if not exists', function () {
    expect(UserState::count())->toBe(0);

    $response = $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/progress", [
            'current_post_id' => $this->posts[0]->id,
            'posts_read' => 1,
        ]);

    $response->assertSuccessful();
    expect(UserState::count())->toBe(1);
});

it('updates existing user state', function () {
    UserState::factory()->create([
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
        'posts_read' => 3,
    ]);

    $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/progress", [
            'current_post_id' => $this->posts[7]->id,
            'posts_read' => 8,
        ]);

    $state = UserState::where('user_id', $this->user->id)
        ->where('book_id', $this->book->id)
        ->first();

    expect($state->posts_read)->toBe(8);
    expect(UserState::count())->toBe(1);
});

it('requires authentication', function () {
    $response = $this->postJson("/api/books/{$this->book->id}/progress", [
        'current_post_id' => $this->posts[0]->id,
        'posts_read' => 1,
    ]);

    $response->assertUnauthorized();
});
