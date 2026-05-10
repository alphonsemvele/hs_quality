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
use Illuminate\Http\Request;

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

    /**
     * Phase 2 / M4.9 — mark the message as read for the authenticated user.
     *
     * Advances the user's read cursor to this message; if the cursor is
     * already past this message the call is a silent no-op (idempotent).
     * Returns the updated unread count for the group so the mobile client
     * can update its badge without a second round-trip.
     */
    public function markRead(Request $request, Message $message): JsonResponse
    {
        $this->authorize('view', $message->group);

        $this->messages->markRead($message, $request->user());

        return response()->json([
            'unread_count' => $this->messages->unreadCount($message->group, $request->user()),
        ]);
    }
}
