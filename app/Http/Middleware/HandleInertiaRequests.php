<?php

namespace App\Http\Middleware;

use App\Http\Resources\InertiaUserResource;
use App\Services\SuperAdminImpersonationService;
use App\Support\UserAbilities;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            // Wave 1 / M1 — narrow shape via InertiaUserResource. Never share
            // the full Eloquent model here; pages that need more fields
            // should fetch them via a page-specific prop.
            'auth' => [
                'user' => $request->user()
                    ? (new InertiaUserResource($request->user()))->toArray($request)
                    : null,
                // Coarse, page-level UI abilities. The React layer reads
                // this via `useCan()` to hide pages and action buttons
                // for personas that have no business clicking them. The
                // backend still enforces authorization via Policies.
                'abilities' => UserAbilities::for($request->user()),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
                'password_reset_url' => fn () => $request->session()->get('password_reset_url'),
                'invitation_url' => fn () => $request->session()->get('invitation_url'),
            ],
            'notifications' => fn () => $request->user() ? $this->notificationsPayload($request) : null,
            'structure' => fn () => $this->structurePayload(),
            'systemBanners' => fn () => $this->systemBannersPayload(),
            // Eager — the banner + sidebar switch are layout-level and need
            // the value on the first paint, not behind a partial reload.
            'impersonation' => $this->impersonationPayload(),
        ];
    }

    /**
     * When a platform admin has opted into "view as dirigeant", expose the
     * target structure + start time so the React layout can render the
     * persistent red banner and swap the sidebar to the tenant nav. Returns
     * null in every other case (regular tenant users, signed-out, super-admin
     * with no active session).
     *
     * @return array{structure_id: string, structure_name: string, started_at: string|null}|null
     */
    private function impersonationPayload(): ?array
    {
        $service = app(SuperAdminImpersonationService::class);

        if (! $service->isActive()) {
            return null;
        }

        $structure = $service->structure();

        if ($structure === null) {
            return null;
        }

        return [
            'structure_id' => (string) $structure->getKey(),
            'structure_name' => (string) $structure->name,
            'started_at' => $service->startedAt()?->toIso8601String(),
        ];
    }

    /**
     * @return array{trial_ends_at: string|null, status: string}|null
     */
    private function structurePayload(): ?array
    {
        $s = app()->bound('current_structure') ? app('current_structure') : null;
        if (! $s) {
            return null;
        }

        return [
            'trial_ends_at' => $s->trial_ends_at?->toIso8601String(),
            'status' => $s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status,
        ];
    }

    /**
     * Ad-hoc admin-driven banners (maintenance windows, release notes…).
     * Driven by config('app.system_banners', []) so DevOps can flip them on
     * via env without a deploy.
     *
     * @return array{items: array<int, array<string, mixed>>}
     */
    private function systemBannersPayload(): array
    {
        return [
            'items' => (array) config('app.system_banners', []),
        ];
    }

    /**
     * Build a compact payload of recent in-app notifications for the header
     * dropdown. We cap to 15 most recent and surface only the fields the UI
     * needs — never serialise the full Eloquent model.
     *
     * @return array{unread_count: int, items: array<int, array<string, mixed>>}
     */
    private function notificationsPayload(Request $request): array
    {
        $user = $request->user();

        $items = $user
            ->notifications()
            ->latest()
            ->limit(15)
            ->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $items
                ->map(function ($notification) {
                    $data = is_array($notification->data) ? $notification->data : [];

                    return [
                        'id' => $notification->id,
                        'type' => class_basename($notification->type),
                        'title' => $data['title'] ?? __('notifications.untitled', [], 'fr') ?? 'Notification',
                        'message' => $data['message'] ?? null,
                        'href' => $data['href'] ?? null,
                        'level' => $data['level'] ?? 'info',
                        'created_at' => $notification->created_at?->toIso8601String(),
                        'read_at' => $notification->read_at?->toIso8601String(),
                    ];
                })
                ->toArray(),
        ];
    }
}
