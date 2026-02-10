<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('books');

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('local');
});

it('can upload an epub file via base64 (NativePHP)', function () {
    $fakeEpubContent = 'fake epub content';
    $base64Data = base64_encode($fakeEpubContent);

    $response = $this->actingAs($this->user)
        ->post('/upload', [
            'epub_name' => 'test-book.epub',
            'epub_size' => strlen($fakeEpubContent),
            'epub_type' => 'application/epub+zip',
            'epub_data' => $base64Data,
        ]);

    $response->assertRedirect();
    Storage::disk('local')->assertExists('books/test-book.epub');
})->skip('EPUB parsing requires real EPUB file');

it('can upload an epub file via traditional upload (web)', function () {
    $file = UploadedFile::fake()->create('test-book.epub', 100, 'application/epub+zip');

    $response = $this->actingAs($this->user)
        ->post('/upload', [
            'epub' => $file,
        ]);

    $response->assertRedirect();
})->skip('EPUB parsing requires real EPUB file');

it('requires authentication to upload epub', function () {
    $base64Data = base64_encode('fake content');

    $response = $this->post('/upload', [
        'epub_name' => 'test.epub',
        'epub_size' => 12,
        'epub_type' => 'application/epub+zip',
        'epub_data' => $base64Data,
    ]);

    $response->assertRedirect(route('login'));
});

it('validates epub data is required when no file provided', function () {
    $response = $this->actingAs($this->user)
        ->post('/upload', []);

    $response->assertSessionHasErrors(['epub']);
});

it('validates epub_name is required with epub_data', function () {
    $response = $this->actingAs($this->user)
        ->post('/upload', [
            'epub_data' => base64_encode('fake'),
            'epub_size' => 4,
            'epub_type' => 'application/epub+zip',
        ]);

    $response->assertSessionHasErrors(['epub_name']);
});

it('validates file size limit (10MB)', function () {
    $response = $this->actingAs($this->user)
        ->post('/upload', [
            'epub_name' => 'large.epub',
            'epub_size' => 11 * 1024 * 1024, // 11MB
            'epub_type' => 'application/epub+zip',
            'epub_data' => base64_encode('fake'),
        ]);

    $response->assertSessionHasErrors(['epub_size']);
});

it('validates traditional file must be epub format', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->post('/upload', [
            'epub' => $file,
        ]);

    $response->assertSessionHasErrors(['epub']);
});
