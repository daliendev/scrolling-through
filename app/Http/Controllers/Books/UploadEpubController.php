<?php

namespace App\Http\Controllers\Books;

use App\Domain\Books\Services\EpubParser;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadEpubRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class UploadEpubController extends Controller
{
    public function __invoke(UploadEpubRequest $request, EpubParser $parser): RedirectResponse
    {
        try {
            // Log the actual log file location for debugging
            \Log::info('LOG FILE LOCATION', [
                'log_path' => storage_path('logs/laravel.log'),
                'storage_path' => storage_path(),
            ]);

            $file = $request->file('epub');

            if (! $file) {
                throw new \Exception('No file uploaded');
            }

            \Log::info('EPUB upload started', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            $filePath = $file->store('books', 'local');
            \Log::info('File stored', ['path' => $filePath]);

            $absolutePath = Storage::disk('local')->path($filePath);
            \Log::info('Absolute path resolved', [
                'absolute_path' => $absolutePath,
                'exists' => file_exists($absolutePath),
            ]);

            // Check if ZipArchive is available
            if (! class_exists('ZipArchive')) {
                throw new \Exception('ZipArchive extension not available in this environment');
            }

            \Log::info('Starting EPUB parsing');
            $book = $parser->parse($absolutePath);

            \Log::info('EPUB parsed successfully', ['book_id' => $book->id]);

            return redirect()->route('books.show', $book);
        } catch (\Exception $e) {
            \Log::error('EPUB upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'epub' => 'Failed to parse EPUB file: '.$e->getMessage(),
            ]);
        }
    }
}
