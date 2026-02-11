<?php

use App\Domain\Books\Services\EpubParser;
use Tests\TestCase;

uses(TestCase::class);

it('combines short paragraphs into a single post', function () {
    $parser = new class extends EpubParser
    {
        public function testChunking(array $rawPosts): array
        {
            $this->posts = $rawPosts;
            $this->chunkPosts();

            return $this->posts;
        }
    };

    $rawPosts = [
        ['text' => 'Short one.', 'type' => 'paragraph', 'position' => 0],
        ['text' => 'Another short.', 'type' => 'paragraph', 'position' => 1],
        ['text' => 'And one more.', 'type' => 'paragraph', 'position' => 2],
    ];

    $chunked = $parser->testChunking($rawPosts);

    // Should combine all three short paragraphs into one post
    expect($chunked)->toHaveCount(1)
        ->and($chunked[0]['text'])->toContain('Short one')
        ->and($chunked[0]['text'])->toContain('Another short')
        ->and($chunked[0]['text'])->toContain('And one more');
});

it('splits long paragraphs at sentence boundaries', function () {
    $parser = new class extends EpubParser
    {
        public function testChunking(array $rawPosts): array
        {
            $this->posts = $rawPosts;
            $this->chunkPosts();

            return $this->posts;
        }
    };

    // Create a long paragraph (>500 chars) with multiple sentences
    $longText = str_repeat('This is a sentence that adds length. ', 20); // ~760 chars

    $rawPosts = [
        ['text' => $longText, 'type' => 'paragraph', 'position' => 0],
    ];

    $chunked = $parser->testChunking($rawPosts);

    // Should be split into multiple posts
    expect($chunked)->toHaveCount(2)
        ->and(strlen($chunked[0]['text']))->toBeLessThanOrEqual(500)
        ->and(strlen($chunked[1]['text']))->toBeLessThanOrEqual(500);
});

it('preserves chapters as separate posts', function () {
    $parser = new class extends EpubParser
    {
        public function testChunking(array $rawPosts): array
        {
            $this->posts = $rawPosts;
            $this->chunkPosts();

            return $this->posts;
        }
    };

    $rawPosts = [
        ['text' => 'Chapter One', 'type' => 'chapter', 'chapter_title' => 'Chapter One', 'position' => 0],
        ['text' => 'Short paragraph.', 'type' => 'paragraph', 'position' => 1],
        ['text' => 'Another short.', 'type' => 'paragraph', 'position' => 2],
    ];

    $chunked = $parser->testChunking($rawPosts);

    // Chapter should remain separate, paragraphs should combine
    expect($chunked)->toHaveCount(2)
        ->and($chunked[0]['type'])->toBe('chapter')
        ->and($chunked[0]['text'])->toBe('Chapter One')
        ->and($chunked[1]['type'])->toBe('paragraph')
        ->and($chunked[1]['text'])->toContain('Short paragraph')
        ->and($chunked[1]['text'])->toContain('Another short');
});

it('targets 250-500 character range per post', function () {
    $parser = new class extends EpubParser
    {
        public function testChunking(array $rawPosts): array
        {
            $this->posts = $rawPosts;
            $this->chunkPosts();

            return $this->posts;
        }
    };

    // Mix of short and medium paragraphs
    $rawPosts = [
        ['text' => str_repeat('Word ', 30), 'type' => 'paragraph', 'position' => 0], // ~150 chars
        ['text' => str_repeat('Text ', 30), 'type' => 'paragraph', 'position' => 1], // ~150 chars
        ['text' => str_repeat('More ', 40), 'type' => 'paragraph', 'position' => 2], // ~200 chars
    ];

    $chunked = $parser->testChunking($rawPosts);

    // Should combine to stay in target range
    foreach ($chunked as $post) {
        if ($post['type'] === 'paragraph') {
            $length = strlen($post['text']);
            expect($length)->toBeGreaterThanOrEqual(200)
                ->and($length)->toBeLessThanOrEqual(600);
        }
    }
});
