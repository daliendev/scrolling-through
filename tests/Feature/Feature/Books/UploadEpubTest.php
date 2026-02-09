<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('books');

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('local');
});

it('can upload an epub file and create book with posts', function () {
    $file = UploadedFile::fake()->create('test-book.epub', 100, 'application/epub+zip');

    $response = $this->actingAs($this->user)
        ->postJson('/api/books/upload', [
            'epub' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'book' => ['id', 'title', 'total_posts'],
        ]);
})->skip('EPUB parsing requires real EPUB file');

it('requires authentication to upload epub', function () {
    $file = UploadedFile::fake()->create('test-book.epub', 100, 'application/epub+zip');

    $response = $this->postJson('/api/books/upload', [
        'epub' => $file,
    ]);

    $response->assertUnauthorized();
});

it('validates epub file is required', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/books/upload', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['epub']);
});

it('validates file must be epub format', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->postJson('/api/books/upload', [
            'epub' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['epub']);
});
