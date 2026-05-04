<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\EditMessageRequest;
use App\Http\Requests\Communication\SendMessageRequest;
use App\Models\DiscussionGroup;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;

/**
 * Mobile + web JSON endpoints for direct messaging in a discussion
 * group. Authorization through the MessagePolicy + DiscussionGroupPolicy
 * (membership check). Real-time fan-out happens through the
 * MessagePosted Reverb event dispatched from MessageService.
 */
class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    public function index(DiscussionGroup $group): JsonResponse
    {
        $this->authorize('view', $group);

        return response()->json([
            'data' => $group->messages()
                ->with('author:id,first_name,last_name')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(SendMessageRequest $request, DiscussionGroup $group): JsonResponse
    {
        $this->authorize('view', $group);

        $message = $this->messages->send(
            $group,
            $request->user(),
            $request->validated('body'),
            $request->validated('attachments'),
        );

        return response()->json($message, 201);
    }

    public function update(EditMessageRequest $request, Message $message): JsonResponse
    {
        $this->authorize('update', $message);

        $updated = $this->messages->edit(
            $message,
            $request->user(),
            $request->validated('body'),
        );

        return response()->json($updated);
    }

    public function destroy(Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->messages->delete($message);

        return response()->json(null, 204);
    }
}
