<?php

use App\Http\Controllers\Books\UploadEpubController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Reading\AddNoteController;
use App\Http\Controllers\Reading\ToggleStarController;
use App\Http\Controllers\Reading\UpdateProgressController;
use App\Http\Controllers\ShowBookFeedController;
use App\Http\Controllers\ShowUploadPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/upload', ShowUploadPageController::class)->name('books.upload');
Route::post('/upload', UploadEpubController::class)->middleware('auth')->name('books.upload.store');
Route::get('/books/{book}', ShowBookFeedController::class)->middleware('auth')->name('books.show');
// Book interactions (all use Inertia)
Route::post('/books/{book}/posts/{post}/toggle-star', ToggleStarController::class)->middleware('auth')->name('posts.toggle-star');
Route::post('/books/{book}/posts/{post}/notes', AddNoteController::class)->middleware('auth')->name('posts.add-note');
Route::post('/books/{book}/progress', UpdateProgressController::class)->middleware('auth')->name('books.progress.update');
// Dummy login route for tests (not used in MVP with auto-auth)
Route::get('/login', fn () => abort(403, 'Authentication required'))->name('login');
