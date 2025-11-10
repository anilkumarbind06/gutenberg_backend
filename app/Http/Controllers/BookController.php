<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Resources\BookResource;

/**
 * @OA\Get(
 *     path="/api/books",
 *     tags={"Books"},
 *     summary="Get list of books",
 *     description="Retrieve Gutenberg books with filters",
 *     @OA\Parameter(
 *         name="language",
 *         in="query",
 *         description="Filter by language (comma separated)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="topic",
 *         in="query",
 *         description="Filter by subject or bookshelf",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     )
 * )
 */

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query()
            ->with(['authors', 'subjects', 'bookshelves', 'languages', 'formats'])
            ->where('title', '<>', null)
            ->orderBy('download_count', 'desc');

        // Gutenberg ID filter
        if ($request->filled('id')) {
            $ids = explode(',', $request->id);
            $query->whereIn('id', $ids);
        }

        // Language filter
        if ($request->filled('language')) {
            $langs = explode(',', $request->language);

            $query->whereHas('languages', function ($q) use ($langs) {
                foreach ($langs as $lang) {
                    $q->orWhere('language', 'ilike', "%{$lang}%");
                }
            });
        }

        // Mime-type filter
        if ($request->filled('mime_type')) {
            $mimeTypes = explode(',', $request->mime_type);

            $query->whereHas('formats', function ($q) use ($mimeTypes) {
                $q->whereIn('mime_type', $mimeTypes);
            });
        }

        // Topic filter (subject OR bookshelf)
        if ($request->filled('topic')) {
            $topics = explode(',', $request->topic);

            $query->where(function ($q) use ($topics) {
                foreach ($topics as $topic) {
                    $q->orWhereHas('subjects', fn($sq) =>
                    $sq->where('name', 'ilike', "%{$topic}%"))
                        ->orWhereHas('bookshelves', fn($bq) =>
                        $bq->where('name', 'ilike', "%{$topic}%"));
                }
            });
        }

        // Author filter (partial match)
        if ($request->filled('search')) {
            $authors = explode(',', $request->search);

            $query->whereHas('authors', function ($q) use ($authors) {
                foreach ($authors as $author) {
                    $q->orWhere('name', 'ilike', "%{$author}%");
                }
            });
        }

        // Title filter (partial match)
        if ($request->filled('search')) {
            $titles = explode(',', $request->search);

            foreach ($titles as $title) {
                $query->where('title', 'ilike', "%{$title}%");
            }
        }

        // Paginate 25 per page
        $books = $query->paginate(25);

        return response()->json([
            'total' => $books->total(),
            'data' => BookResource::collection($books),
            'current_page' => $books->currentPage(),
            'next_page_url' => $books->nextPageUrl()
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
