<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\PublishNewsRequest;
use App\Models\NewsFeedPost;
use App\Services\NewsFeedService;
use Illuminate\Http\JsonResponse;

class NewsFeedController extends Controller
{
    public function __construct(private readonly NewsFeedService $news) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', NewsFeedPost::class);

        return response()->json([
            'data' => NewsFeedPost::query()
                ->with('author:id,first_name,last_name')
                ->whereNull('archived_at')
                ->orderByDesc('pinned')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(PublishNewsRequest $request): JsonResponse
    {
        $post = $this->news->publish(
            currentStructure(),
            $request->user(),
            $request->validated('title'),
            $request->validated('body'),
        );

        return response()->json($post, 201);
    }

    public function pin(NewsFeedPost $post): JsonResponse
    {
        $this->authorize('pin', $post);

        return response()->json($this->news->pin($post));
    }

    public function unpin(NewsFeedPost $post): JsonResponse
    {
        $this->authorize('pin', $post);

        return response()->json($this->news->unpin($post));
    }

    public function archive(NewsFeedPost $post): JsonResponse
    {
        $this->authorize('archive', $post);

        return response()->json($this->news->archive($post));
    }
}
