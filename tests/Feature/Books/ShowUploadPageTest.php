<?php

use App\Models\User;

test('user can view upload page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/upload');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('UploadEpub'));
});
