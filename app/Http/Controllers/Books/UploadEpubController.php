<?php

namespace App\Http\Controllers\Books;

use App\Domain\Books\Services\EpubParser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadEpubController extends Controller
{
    /**
     * Upload and parse an EPUB file.
     *
     * Supports two upload methods:
     * 1. Base64 encoded data (NativePHP iOS/Android) - used because multipart/form-data
     *    body is not transmitted over php:// protocol
     * 2. Traditional file upload (web browsers) - standard multipart/form-data
     */
    public function __invoke(Request $request, EpubParser $parser): RedirectResponse
    {
        // Validate base64 upload (NativePHP) or traditional file upload (web)
        $request->validate([
            'epub' => 'required_without:epub_data|file|mimes:epub,application/epub+zip|max:10240',
            'epub_data' => 'required_without:epub|string',
            'epub_name' => 'required_with:epub_data|string',
            'epub_size' => 'required_with:epub_data|integer|max:10485760', // 10MB in bytes
            'epub_type' => 'required_with:epub_data|string',
        ], [
            'epub.required_without' => 'Please upload an EPUB file',
            'epub_data.required_without' => 'Please upload an EPUB file',
            'epub.file' => 'The uploaded file is not valid',
            'epub.mimes' => 'Only EPUB files are allowed',
            'epub.max' => 'The file is too large (maximum 10MB)',
            'epub_size.max' => 'The file is too large (maximum 10MB)',
        ]);

        try {
            // Handle base64 upload (NativePHP iOS/Android)
            if ($request->has('epub_data')) {
                $fileData = base64_decode($request->input('epub_data'));
                $filePath = 'books/'.$request->input('epub_name');
                Storage::disk('local')->put($filePath, $fileData);
                $absolutePath = Storage::disk('local')->path($filePath);
            }
            // Handle traditional file upload (web browser)
            else {
                $file = $request->file('epub');
                $filePath = $file->store('books', 'local');
                $absolutePath = Storage::disk('local')->path($filePath);
            }

            $book = $parser->parse($absolutePath);

            return redirect()->route('books.show', $book);
        } catch (\Exception $e) {
            return back()->withErrors([
                'epub' => 'Failed to parse EPUB file: '.$e->getMessage(),
            ]);
        }
    }
}
