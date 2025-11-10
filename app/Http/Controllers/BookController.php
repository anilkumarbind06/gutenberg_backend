<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Resources\BookResource;

/**
 * @OA\Get(
 *     path="/api/books",
 *     tags={"Books"},
 *     summary="Get list of Gutenberg books",
 *     description="Retrieve books with multiple filter options. Results are sorted by downloads (popularity) and paginated (25 per page).",
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="Filter by Project Gutenberg ID (comma-separated)",
 *         required=false,
 *         example="1,2,3",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="language",
 *         in="query",
 *         description="Filter by language code (comma-separated)",
 *         required=false,
 *         example="en,fr",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="mime_type",
 *         in="query",
 *         description="Filter by file MIME type (comma-separated)",
 *         required=false,
 *         example="application/pdf,text/plain",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="topic",
 *         in="query",
 *         description="Filter by subject or bookshelf (case-insensitive, comma-separated)",
 *         required=false,
 *         example="child,education",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="author",
 *         in="query",
 *         description="Filter by author name (partial match, comma-separated)",
 *         required=false,
 *         example="doyle,shakespeare",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="title",
 *         in="query",
 *         description="Filter by book title (partial match, comma-separated)",
 *         required=false,
 *         example="sherlock,adventures",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number for pagination (defaults to 1)",
 *         required=false,
 *         example=2,
 *         @OA\Schema(type="integer")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful fetch",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="total", type="integer", example=1234),
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1342),
 *                     @OA\Property(property="title", type="string", example="The Adventures of Sherlock Holmes"),
 *                     @OA\Property(property="authors", type="array", @OA\Items(type="string"), example={"Arthur Conan Doyle"}),
 *                     @OA\Property(property="genre", type="string", example="Fiction"),
 *                     @OA\Property(property="language", type="array", @OA\Items(type="string"), example={"en"}),
 *                     @OA\Property(property="subjects", type="array", @OA\Items(type="string"), example={"Detective and mystery stories"}),
 *                     @OA\Property(property="bookshelves", type="array", @OA\Items(type="string"), example={"Mystery"}),
 *                     @OA\Property(property="downloads", type="integer", example=54512),
 *                     @OA\Property(
 *                         property="formats",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="mime_type", type="string", example="application/pdf"),
 *                             @OA\Property(property="url", type="string", example="https://www.gutenberg.org/files/1661/1661-pdf.pdf")
 *                         )
 *                     )
 *                 )
 *             ),
 *             @OA\Property(property="next_page_url", type="string", example="http://localhost:8000/api/books?page=2")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
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
                    $q->orWhere('code', 'ilike', "%{$lang}%");
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
