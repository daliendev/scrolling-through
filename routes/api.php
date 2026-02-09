<?php

use App\Http\Controllers\Books\BookFeedController;
use App\Http\Controllers\Books\UploadEpubController;
use App\Http\Controllers\Reading\AddNoteController;
use App\Http\Controllers\Reading\GetShareTextController;
use App\Http\Controllers\Reading\ToggleStarController;
use App\Http\Controllers\Reading\UpdateProgressController;
use Illuminate\Support\Facades\Route;

// AutoAuthenticateUser middleware handles authentication globally
// No need for explicit auth middleware here

// Book endpoints
Route::post('/books/upload', UploadEpubController::class)->name('api.books.upload');
Route::get('/books/{book}/feed', BookFeedController::class)->name('api.books.feed');

// Progress tracking
Route::post('/books/{book}/progress', UpdateProgressController::class)->name('api.books.progress.update');

// User interactions
Route::post('/books/{book}/posts/{post}/toggle-star', ToggleStarController::class)->name('api.posts.toggle-star');
Route::post('/books/{book}/posts/{post}/notes', AddNoteController::class)->name('api.posts.add-note');
Route::get('/books/{book}/posts/{post}/share', GetShareTextController::class)->name('api.posts.share');
