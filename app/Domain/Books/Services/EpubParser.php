<?php

namespace App\Domain\Books\Services;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class EpubParser
{
    protected string $tempDir;

    protected array $posts = [];

    protected int $position = 0;

    public function __construct()
    {
        // Use storage_path which NativePHP rewrites to app.getPath('appData')
        $this->tempDir = storage_path('app/temp');

        \Log::info('EpubParser temp dir', [
            'temp_dir' => $this->tempDir,
            'storage_base' => storage_path(),
            'exists' => is_dir($this->tempDir),
            'parent_writable' => is_writable(storage_path('app')),
            'app_writable' => is_dir(storage_path('app')) && is_writable(storage_path('app')),
        ]);

        if (! is_dir($this->tempDir)) {
            try {
                // Ensure parent directory exists first
                if (! is_dir(storage_path('app'))) {
                    mkdir(storage_path('app'), 0755, true);
                }

                mkdir($this->tempDir, 0755, true);
                \Log::info('Created temp directory', ['path' => $this->tempDir]);
            } catch (\Exception $e) {
                \Log::error('Failed to create temp directory', [
                    'path' => $this->tempDir,
                    'error' => $e->getMessage(),
                ]);
                throw new Exception('Could not create temp directory: '.$e->getMessage());
            }
        }
    }

    /**
     * Parse an EPUB file and create a Book with Posts
     */
    public function parse(string $filePath): Book
    {
        if (! file_exists($filePath)) {
            throw new Exception('File not found: '.$filePath);
        }

        // Extract EPUB (it's a ZIP file)
        $extractPath = $this->extractEpub($filePath);

        try {
            // Get book metadata
            $title = $this->extractTitle($extractPath);

            // Get content files
            $contentFiles = $this->getContentFiles($extractPath);

            // Parse content into posts
            $this->parseContent($contentFiles);

            // Create book in database
            $book = DB::transaction(function () use ($title, $filePath) {
                $book = Book::create([
                    'title' => $title,
                    'file_path' => $filePath,
                    'total_posts' => count($this->posts),
                ]);

                // Create all posts
                foreach ($this->posts as $postData) {
                    Post::create([
                        'book_id' => $book->id,
                        'text' => $postData['text'],
                        'type' => $postData['type'],
                        'chapter_title' => $postData['chapter_title'] ?? null,
                        'position' => $postData['position'],
                    ]);
                }

                return $book;
            });

            return $book;

        } finally {
            // Cleanup temp directory
            $this->cleanup($extractPath);
        }
    }

    /**
     * Extract EPUB file to temp directory
     */
    protected function extractEpub(string $filePath): string
    {
        \Log::info('Starting EPUB extraction', [
            'file_path' => $filePath,
            'file_exists' => file_exists($filePath),
            'file_size' => file_exists($filePath) ? filesize($filePath) : null,
            'file_readable' => is_readable($filePath),
        ]);

        $zip = new ZipArchive;
        $extractPath = $this->tempDir.'/'.uniqid('epub_');

        \Log::info('ZipArchive created', ['extract_path' => $extractPath]);

        $openResult = $zip->open($filePath);

        \Log::info('ZipArchive open result', [
            'result' => $openResult,
            'success' => $openResult === true,
        ]);

        if ($openResult !== true) {
            $errorCodes = [
                ZipArchive::ER_EXISTS => 'File already exists',
                ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                ZipArchive::ER_INVAL => 'Invalid argument',
                ZipArchive::ER_MEMORY => 'Malloc failure',
                ZipArchive::ER_NOENT => 'No such file',
                ZipArchive::ER_NOZIP => 'Not a zip archive',
                ZipArchive::ER_OPEN => 'Can\'t open file',
                ZipArchive::ER_READ => 'Read error',
                ZipArchive::ER_SEEK => 'Seek error',
            ];

            $errorMsg = $errorCodes[$openResult] ?? "Unknown error code: $openResult";
            \Log::error('Failed to open EPUB file', ['error' => $errorMsg]);

            throw new Exception('Failed to open EPUB file: '.$errorMsg);
        }

        \Log::info('Extracting archive', ['extract_path' => $extractPath]);
        $zip->extractTo($extractPath);
        $zip->close();

        \Log::info('EPUB extracted successfully', ['extract_path' => $extractPath]);

        return $extractPath;
    }

    /**
     * Extract book title from metadata
     */
    protected function extractTitle(string $extractPath): string
    {
        // Try to find content.opf or similar
        $opfFile = $this->findOpfFile($extractPath);

        if ($opfFile && file_exists($opfFile)) {
            $xml = @simplexml_load_file($opfFile);

            if ($xml) {
                $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
                $titles = $xml->xpath('//dc:title');

                if (! empty($titles)) {
                    return (string) $titles[0];
                }
            }
        }

        // Fallback to filename
        return 'Untitled Book';
    }

    /**
     * Find the OPF file (content.opf)
     */
    protected function findOpfFile(string $extractPath): ?string
    {
        // Check META-INF/container.xml first
        $containerPath = $extractPath.'/META-INF/container.xml';

        if (file_exists($containerPath)) {
            $xml = @simplexml_load_file($containerPath);

            if ($xml) {
                $xml->registerXPathNamespace('c', 'urn:oasis:names:tc:opendocument:xmlns:container');
                $rootfiles = $xml->xpath('//c:rootfile[@media-type="application/oebps-package+xml"]');

                if (! empty($rootfiles)) {
                    $opfPath = (string) $rootfiles[0]['full-path'];

                    return $extractPath.'/'.$opfPath;
                }
            }
        }

        // Fallback: search for .opf file
        $files = $this->recursiveGlob($extractPath, '*.opf');

        return $files[0] ?? null;
    }

    /**
     * Get content files (HTML/XHTML) from EPUB
     */
    protected function getContentFiles(string $extractPath): array
    {
        $contentFiles = [];

        // Find OPF file to get spine order
        $opfFile = $this->findOpfFile($extractPath);

        if ($opfFile && file_exists($opfFile)) {
            $opfDir = dirname($opfFile);
            $xml = @simplexml_load_file($opfFile);

            if ($xml) {
                $xml->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');

                // Get manifest items
                $manifest = [];
                foreach ($xml->manifest->item as $item) {
                    $id = (string) $item['id'];
                    $href = (string) $item['href'];
                    $manifest[$id] = $opfDir.'/'.$href;
                }

                // Get spine order
                foreach ($xml->spine->itemref as $itemref) {
                    $idref = (string) $itemref['idref'];

                    if (isset($manifest[$idref])) {
                        $contentFiles[] = $manifest[$idref];
                    }
                }
            }
        }

        // Fallback: find all HTML/XHTML files
        if (empty($contentFiles)) {
            $contentFiles = array_merge(
                $this->recursiveGlob($extractPath, '*.html'),
                $this->recursiveGlob($extractPath, '*.xhtml'),
                $this->recursiveGlob($extractPath, '*.htm')
            );
        }

        return $contentFiles;
    }

    /**
     * Parse HTML content files into posts
     */
    protected function parseContent(array $contentFiles): void
    {
        foreach ($contentFiles as $file) {
            if (! file_exists($file)) {
                continue;
            }

            $html = file_get_contents($file);
            $this->parseHtmlContent($html);
        }

        // Chunk posts for optimal reading
        $this->chunkPosts();

        // If no posts found, create a fallback
        if (empty($this->posts)) {
            $this->posts[] = [
                'text' => 'This book could not be parsed properly.',
                'type' => 'paragraph',
                'position' => 0,
            ];
        }
    }

    /**
     * Chunk posts for optimal fullscreen reading (250-500 chars per post)
     * - Combines short paragraphs (< 250 chars)
     * - Splits long paragraphs (> 500 chars) at sentence boundaries
     * - Preserves chapters as separate posts
     */
    protected function chunkPosts(): void
    {
        $chunked = [];
        $buffer = '';
        $bufferedPosts = [];

        foreach ($this->posts as $post) {
            // Chapters always stay separate
            if ($post['type'] === 'chapter') {
                // Flush buffer if any
                if (! empty($buffer)) {
                    $chunked[] = $this->createChunkedPost($buffer, 'paragraph');
                    $buffer = '';
                    $bufferedPosts = [];
                }

                $chunked[] = $post;

                continue;
            }

            // For paragraphs, add to buffer
            $text = $post['text'];
            $textLength = strlen($text);

            // If adding this text keeps us under 500 chars, add it
            if (strlen($buffer) + $textLength + 2 < 500) { // +2 for double newline
                $buffer .= ($buffer ? "\n\n" : '').$text;
                $bufferedPosts[] = $post;

                continue;
            }

            // Buffer would exceed 500 chars
            // If buffer has content and is >= 250 chars, flush it
            if (! empty($buffer) && strlen($buffer) >= 250) {
                $chunked[] = $this->createChunkedPost($buffer, 'paragraph');
                $buffer = '';
                $bufferedPosts = [];
            }

            // Handle current text
            if ($textLength > 500) {
                // Split long paragraph at sentence boundaries
                $sentences = $this->splitIntoSentences($text);
                $tempBuffer = '';

                foreach ($sentences as $sentence) {
                    if (strlen($tempBuffer) + strlen($sentence) + 1 > 500 && ! empty($tempBuffer)) {
                        // Flush temp buffer
                        $chunked[] = $this->createChunkedPost($tempBuffer, 'paragraph');
                        $tempBuffer = '';
                    }

                    $tempBuffer .= ($tempBuffer ? ' ' : '').$sentence;
                }

                // Flush remaining
                if (! empty($tempBuffer)) {
                    if (strlen($tempBuffer) < 250 && empty($buffer)) {
                        // Start new buffer if too short
                        $buffer = $tempBuffer;
                    } else {
                        $chunked[] = $this->createChunkedPost($tempBuffer, 'paragraph');
                    }
                }
            } else {
                // Text fits, start new buffer
                $buffer = $text;
                $bufferedPosts = [$post];
            }
        }

        // Flush remaining buffer
        if (! empty($buffer)) {
            $chunked[] = $this->createChunkedPost($buffer, 'paragraph');
        }

        // Update positions
        foreach ($chunked as $index => $post) {
            $chunked[$index]['position'] = $index;
        }

        $this->posts = $chunked;
    }

    /**
     * Create a chunked post
     */
    protected function createChunkedPost(string $text, string $type): array
    {
        return [
            'text' => $text,
            'type' => $type,
            'position' => 0, // Will be updated later
        ];
    }

    /**
     * Split text into sentences
     */
    protected function splitIntoSentences(string $text): array
    {
        // Split on sentence boundaries: . ! ? followed by space or end
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $sentences ?: [$text];
    }

    /**
     * Parse HTML content and extract paragraphs and chapters
     */
    protected function parseHtmlContent(string $html): void
    {
        $doc = new DOMDocument;

        // Suppress warnings for malformed HTML
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $xpath = new DOMXPath($doc);

        // Find all h1, h2, and p tags
        $elements = $xpath->query('//h1 | //h2 | //p');

        foreach ($elements as $element) {
            $text = trim($element->textContent);

            if (empty($text)) {
                continue;
            }

            $tagName = strtolower($element->tagName);

            if (in_array($tagName, ['h1', 'h2'])) {
                // Chapter heading
                $this->posts[] = [
                    'text' => $text,
                    'type' => 'chapter',
                    'chapter_title' => $text,
                    'position' => $this->position++,
                ];
            } else {
                // Paragraph
                $this->posts[] = [
                    'text' => $text,
                    'type' => 'paragraph',
                    'position' => $this->position++,
                ];
            }
        }
    }

    /**
     * Recursive glob function
     */
    protected function recursiveGlob(string $pattern, string $extension, int $flags = 0): array
    {
        $files = glob($pattern.'/'.$extension, $flags);

        foreach (glob($pattern.'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->recursiveGlob($dir, $extension, $flags));
        }

        return $files;
    }

    /**
     * Cleanup temp directory
     */
    protected function cleanup(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }

        @rmdir($path);
    }
}
