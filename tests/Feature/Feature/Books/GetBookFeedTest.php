<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Models\User;

uses()->group('books');

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can get book feed with all posts', function () {
    $book = Book::factory()->create(['title' => 'Test Book']);
    Post::factory()->chapter()->create([
        'book_id' => $book->id,
        'chapter_title' => 'Chapter 1',
        'position' => 0,
    ]);
    Post::factory()->count(5)->create([
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/books/{$book->id}/feed");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'book' => ['id', 'title', 'total_posts'],
            'posts' => [
                '*' => ['id', 'text', 'type', 'position'],
            ],
        ])
        ->assertJsonCount(6, 'posts');
});

it('includes user state in feed response', function () {
    $book = Book::factory()->create();
    Post::factory()->count(3)->create(['book_id' => $book->id]);

    $state = UserState::factory()->create([
        'user_id' => $this->user->id,
        'book_id' => $book->id,
        'posts_read' => 2,
    ]);
    $state->toggleStar(1);

    $response = $this->actingAs($this->user)
        ->getJson("/api/books/{$book->id}/feed");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'userState' => [
                'current_post_id',
                'starred_post_ids',
                'notes',
                'posts_read',
                'progress_percentage',
            ],
        ]);
});

it('returns posts ordered by position', function () {
    $book = Book::factory()->create();
    Post::factory()->create(['book_id' => $book->id, 'position' => 2, 'text' => 'Third']);
    Post::factory()->create(['book_id' => $book->id, 'position' => 0, 'text' => 'First']);
    Post::factory()->create(['book_id' => $book->id, 'position' => 1, 'text' => 'Second']);

    $response = $this->actingAs($this->user)
        ->getJson("/api/books/{$book->id}/feed");

    $posts = $response->json('posts');
    expect($posts[0]['text'])->toBe('First')
        ->and($posts[1]['text'])->toBe('Second')
        ->and($posts[2]['text'])->toBe('Third');
});

it('requires authentication to view feed', function () {
    $book = Book::factory()->create();

    $response = $this->getJson("/api/books/{$book->id}/feed");

    $response->assertUnauthorized();
});
