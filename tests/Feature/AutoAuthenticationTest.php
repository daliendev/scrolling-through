<?php

use App\Models\User;

test('guest is automatically authenticated when accessing web pages', function () {
    // Ensure we start with no authentication
    $this->assertGuest();

    // Access the home page
    $response = $this->get('/upload');

    // Should be successful (not redirected to login)
    $response->assertOk();

    // Should now be authenticated
    $this->assertAuthenticated();
});

test('auto authentication creates user if none exists', function () {
    // Delete all users
    User::query()->delete();

    expect(User::count())->toBe(0);

    // Access a web page
    $this->get('/upload');

    // A user should have been created
    expect(User::count())->toBe(1);

    $user = User::first();
    expect($user->email)->toBe('user@scrolling-through.app');
    expect($user->name)->toBe('Mobile User');
});

test('auto authentication reuses existing user', function () {
    // Create a user
    $user = User::factory()->create();
    $initialCount = User::count();

    // Access a web page as guest
    $this->get('/upload');

    // Should not create a new user
    expect(User::count())->toBe($initialCount);

    // Should be logged in as the existing user
    expect(auth()->id())->toBe($user->id);
});
