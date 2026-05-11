<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
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
}
