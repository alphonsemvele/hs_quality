<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app notifications surface. The header drawer uses the shared Inertia
 * payload (see HandleInertiaRequests); this controller owns the full-page
 * inbox at /notifications and the per-item write actions (mark/destroy).
 */
class NotificationController extends Controller
{
    private const VALID_LEVELS = ['info', 'success', 'warning', 'danger'];

    public function index(Request $request): Response
    {
        $user = Auth::user();

        $status = $request->query('status', 'all');
        $level = $request->query('level', 'all');
        $search = trim((string) $request->query('q', ''));

        $query = $user->notifications();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $paginator = $query->latest()->paginate(20)->withQueryString();

        $items = collect($paginator->items())
            ->map(function (DatabaseNotification $n) {
                $data = is_array($n->data) ? $n->data : [];

                return [
                    'id' => $n->id,
                    'type' => class_basename($n->type),
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? null,
                    'href' => $data['href'] ?? null,
                    'level' => $data['level'] ?? 'info',
                    'created_at' => $n->created_at?->toIso8601String(),
                    'read_at' => $n->read_at?->toIso8601String(),
                ];
            })
            ->when(
                in_array($level, self::VALID_LEVELS, true),
                fn ($c) => $c->where('level', $level)->values(),
            )
            ->when(
                $search !== '',
                fn ($c) => $c->filter(function (array $i) use ($search): bool {
                    $needle = mb_strtolower($search);

                    return str_contains(mb_strtolower((string) $i['title']), $needle)
                        || str_contains(mb_strtolower((string) ($i['message'] ?? '')), $needle);
                })->values(),
            )
            ->all();

        return Inertia::render('dashboard/notifications/index', [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'links' => [
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
            'filters' => [
                'status' => in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all',
                'level' => in_array($level, self::VALID_LEVELS, true) ? $level : 'all',
                'q' => $search,
            ],
            'unread_total' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($id)->first();
        if ($notification && $notification->unread()) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $user->unreadNotifications->markAsRead();

        return back();
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($id)->first();
        abort_unless($notification !== null, 404);

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }
}
