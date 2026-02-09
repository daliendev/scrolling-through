<?php

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use App\Domain\Reading\Models\UserState;
use App\Models\User;

uses()->group('reading');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->book = Book::factory()->create();
    $this->post = Post::factory()->create(['book_id' => $this->book->id]);
    $this->state = UserState::factory()->create([
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
    ]);
});

it('can toggle favorite on a post', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/posts/{$this->post->id}/toggle-star");

    $response->assertSuccessful()
        ->assertJson(['starred' => true]);

    $this->state->refresh();
    expect($this->state->starred_post_ids)->toContain($this->post->id);
});

it('can untoggle favorite on a post', function () {
    $this->state->toggleStar($this->post->id);

    $response = $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/posts/{$this->post->id}/toggle-star");

    $response->assertSuccessful()
        ->assertJson(['starred' => false]);

    $this->state->refresh();
    expect($this->state->starred_post_ids)->not->toContain($this->post->id);
});

it('can add a note to a post', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/posts/{$this->post->id}/notes", [
            'text' => 'Great paragraph!',
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['note' => ['text', 'timestamp']]);

    $this->state->refresh();
    expect($this->state->notes)
        ->toHaveKey((string) $this->post->id)
        ->and($this->state->notes[(string) $this->post->id])->toHaveCount(1)
        ->and($this->state->notes[(string) $this->post->id][0]['text'])->toBe('Great paragraph!');
});

it('validates note text is required', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/posts/{$this->post->id}/notes", [
            'text' => '',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['text']);
});

it('can get share text for a post', function () {
    $post = Post::factory()->create([
        'book_id' => $this->book->id,
        'text' => 'This is a wonderful quote.',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/books/{$this->book->id}/posts/{$post->id}/share");

    $response->assertSuccessful()
        ->assertJson([
            'share_text' => '"This is a wonderful quote." — '.$this->book->title,
        ]);
});

it('requires authentication for all interactions', function () {
    $endpoints = [
        ['method' => 'post', 'url' => "/api/books/{$this->book->id}/posts/{$this->post->id}/toggle-star"],
        ['method' => 'post', 'url' => "/api/books/{$this->book->id}/posts/{$this->post->id}/notes", 'data' => ['text' => 'note']],
        ['method' => 'get', 'url' => "/api/books/{$this->book->id}/posts/{$this->post->id}/share"],
    ];

    foreach ($endpoints as $endpoint) {
        $response = $endpoint['method'] === 'get'
            ? $this->getJson($endpoint['url'])
            : $this->postJson($endpoint['url'], $endpoint['data'] ?? []);

        $response->assertUnauthorized();
    }
});

it('creates user state if not exists when toggling star', function () {
    $this->state->delete();
    expect(UserState::count())->toBe(0);

    $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/posts/{$this->post->id}/toggle-star");

    expect(UserState::count())->toBe(1);
});

it('creates user state if not exists when adding note', function () {
    $this->state->delete();
    expect(UserState::count())->toBe(0);

    $this->actingAs($this->user)
        ->postJson("/api/books/{$this->book->id}/posts/{$this->post->id}/notes", [
            'text' => 'Note text',
        ]);

    expect(UserState::count())->toBe(1);
});
