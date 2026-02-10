<?php

use App\Models\User;

uses()->group('books');

beforeEach(function () {
    $this->user = User::factory()->create();
});

// These tests are for the old API approach - now using Inertia exclusively
// See ShowBookFeedTest for Inertia page tests

it('can get book feed with all posts', function () {
    $this->markTestSkipped('API routes removed - using Inertia exclusively. See ShowBookFeedTest.');
});

it('includes user state in feed response', function () {
    $this->markTestSkipped('API routes removed - using Inertia exclusively. See ShowBookFeedTest.');
});

it('returns posts ordered by position', function () {
    $this->markTestSkipped('API routes removed - using Inertia exclusively. See ShowBookFeedTest.');
});

it('requires authentication to view feed', function () {
    $this->markTestSkipped('API routes removed - using Inertia exclusively. See ShowBookFeedTest.');
});
