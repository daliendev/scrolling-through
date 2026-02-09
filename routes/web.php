<?php

use App\Http\Controllers\Books\UploadEpubController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShowBookFeedController;
use App\Http\Controllers\ShowUploadPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/upload', ShowUploadPageController::class)->name('books.upload');
Route::post('/upload', UploadEpubController::class)->name('books.upload.store');
Route::get('/books/{book}', ShowBookFeedController::class)->name('books.show');
