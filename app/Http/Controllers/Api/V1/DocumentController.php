<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\UploadDocumentRequest;
use App\Models\Document;
use App\Services\DocumentLibraryService;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentLibraryService $library) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $user = request()->user();

        $docs = Document::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->filter(fn (Document $d) => $d->isVisibleTo($user))
            ->values();

        return response()->json(['data' => $docs]);
    }

    public function store(UploadDocumentRequest $request): JsonResponse
    {
        $document = $this->library->upload(
            currentStructure(),
            $request->user(),
            $request->file('file'),
            $request->validated('title'),
            $request->validated('description'),
            $request->validated('roles_acl'),
        );

        return response()->json($document, 201);
    }

    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json($document);
    }

    public function download(Document $document): JsonResponse
    {
        // The DocumentLibraryService::downloadUrl re-checks isVisibleTo
        // — defense in depth alongside the policy gate above.
        $this->authorize('view', $document);

        return response()->json([
            'url' => $this->library->downloadUrl($document, request()->user()),
        ]);
    }
}
