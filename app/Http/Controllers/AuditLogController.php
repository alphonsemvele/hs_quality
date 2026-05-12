<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;

/**
 * Read-only viewer over the owen-it/laravel-auditing `audits` table.
 *
 * RGPD Article 30 (registre des traitements) + ANSSI hygiène §35 require
 * structures to keep an immutable trail of who-did-what on personal data.
 * This page surfaces it to the référent qualité — every row is already
 * tenant-scoped by `structure_id` so a tenant only sees its own events.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user(), 401);

        $event = $request->string('event')->toString();
        $auditableType = $request->string('type')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $query = Audit::query()
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at');

        if ($event !== '') {
            $query->where('event', $event);
        }
        if ($auditableType !== '') {
            $query->where('auditable_type', 'like', "%{$auditableType}%");
        }
        if ($from) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($to) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        $paginator = $query->paginate(50)->withQueryString();

        $rows = collect($paginator->items())->map(function (Audit $a): array {
            /** @var User|null $user */
            $user = $a->user;
            $old = is_array($a->old_values) ? $a->old_values : [];
            $new = is_array($a->new_values) ? $a->new_values : [];
            $changedKeys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));

            return [
                'id' => $a->id,
                'event' => $a->event,
                'auditable_type' => class_basename($a->auditable_type),
                'auditable_id' => (string) $a->auditable_id,
                'user_name' => $user ? trim($user->first_name.' '.$user->last_name) : null,
                'user_email' => $user?->email,
                'url' => $a->url,
                'ip_address' => $a->ip_address ? (string) $a->ip_address : null,
                'changed_keys' => array_slice($changedKeys, 0, 6),
                'created_at' => $a->created_at?->toIso8601String(),
                'created_at_human' => $a->created_at?->diffForHumans(),
            ];
        })->all();

        $types = Audit::query()
            ->select('auditable_type')
            ->distinct()
            ->pluck('auditable_type')
            ->map(fn ($t) => class_basename((string) $t))
            ->filter()
            ->sort()
            ->values()
            ->all();

        $eventCounts = Audit::query()
            ->select('event', DB::raw('count(*) as c'))
            ->groupBy('event')
            ->pluck('c', 'event')
            ->all();

        return Inertia::render('dashboard/audit-log/index', [
            'rows' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'event' => $event !== '' ? $event : null,
                'type' => $auditableType !== '' ? $auditableType : null,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'eventCounts' => $eventCounts,
            'auditableTypes' => $types,
        ]);
    }
}
