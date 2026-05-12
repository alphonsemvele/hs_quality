<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditGrid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only browser over the audit grid library (HAS / ISO 9001 / AFNOR /
 * custom). The référent qualité uses this to pre-inspect a referential
 * before launching an audit run.
 *
 * Tenant scoping is enforced by `BelongsToStructure` on AuditGrid — each
 * structure sees its own grids plus the system-seeded HAS/ISO/AFNOR
 * templates that have been copied into the tenant on subscription.
 */
class AuditGridController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditGrid::class);

        $source = $request->string('source')->toString();
        $search = $request->string('q')->toString();

        $query = AuditGrid::query()
            ->where('is_active', true)
            ->withCount('items')
            ->orderBy('source')
            ->orderBy('title');

        if ($source !== '') {
            $query->where('source', $source);
        }

        if ($search !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $driver = DB::connection()->getDriverName();
            $op = $driver === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($op, $like) {
                $q->where('title', $op, $like)->orWhere('description', $op, $like);
            });
        }

        $grids = $query->get(['id', 'title', 'description', 'source']);

        return Inertia::render('dashboard/audits/grids/index', [
            'grids' => $grids->map(fn (AuditGrid $g) => [
                'id' => $g->id,
                'title' => $g->title,
                'description' => $g->description,
                'source' => $g->source instanceof \BackedEnum ? $g->source->value : (string) $g->source,
                'items_count' => $g->items_count,
            ])->values()->all(),
            'filters' => [
                'source' => $source !== '' ? $source : null,
                'q' => $search !== '' ? $search : null,
            ],
            'sources' => $this->sourceCounts(),
        ]);
    }

    public function show(AuditGrid $auditGrid): Response
    {
        $this->authorize('view', $auditGrid);

        $auditGrid->load('items');

        return Inertia::render('dashboard/audits/grids/show', [
            'grid' => [
                'id' => $auditGrid->id,
                'title' => $auditGrid->title,
                'description' => $auditGrid->description,
                'source' => $auditGrid->source instanceof \BackedEnum ? $auditGrid->source->value : (string) $auditGrid->source,
                'items_count' => $auditGrid->items->count(),
                'evidence_required_count' => $auditGrid->items->where('evidence_required', true)->count(),
                'max_points_total' => $auditGrid->items->sum('max_points'),
            ],
            'items' => $auditGrid->items->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'scale' => $i->scale instanceof \BackedEnum ? $i->scale->value : (string) $i->scale,
                'max_points' => (float) $i->max_points,
                'evidence_required' => (bool) $i->evidence_required,
                'position' => (int) $i->position,
            ])->all(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function sourceCounts(): array
    {
        return AuditGrid::query()
            ->where('is_active', true)
            ->selectRaw('source, count(*) as c')
            ->groupBy('source')
            ->pluck('c', 'source')
            ->all();
    }
}
