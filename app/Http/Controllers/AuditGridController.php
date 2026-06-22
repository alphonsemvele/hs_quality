<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AuditGridSource;
use App\Http\Requests\Audits\StoreAuditGridRequest;
use App\Http\Requests\Audits\UpdateAuditGridRequest;
use App\Models\AuditGrid;
use App\Models\AuditGridAxis;
use Illuminate\Http\RedirectResponse;
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

        $canConfigure = $request->user()?->can('create', AuditGrid::class) ?? false;

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
            'can' => [
                'configure' => $canConfigure,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AuditGrid::class);

        return Inertia::render('dashboard/audits/grids/create');
    }

    public function store(StoreAuditGridRequest $request): RedirectResponse
    {
        $grid = AuditGrid::create([
            'title' => $request->string('title')->toString(),
            'description' => $request->input('description'),
            'source' => AuditGridSource::Custom,
            'weight_scheme' => ['type' => 'equal'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('audits.grids.show', $grid)
            ->with('success', 'Référentiel créé.');
    }

    public function edit(AuditGrid $auditGrid): Response
    {
        $this->authorize('update', $auditGrid);

        return Inertia::render('dashboard/audits/grids/edit', [
            'grid' => [
                'id' => $auditGrid->id,
                'title' => $auditGrid->title,
                'description' => $auditGrid->description,
                'source' => $auditGrid->source instanceof \BackedEnum ? $auditGrid->source->value : (string) $auditGrid->source,
                'is_active' => (bool) $auditGrid->is_active,
            ],
        ]);
    }

    public function update(UpdateAuditGridRequest $request, AuditGrid $auditGrid): RedirectResponse
    {
        $auditGrid->update([
            'title' => $request->string('title')->toString(),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('audits.grids.show', $auditGrid)
            ->with('success', 'Référentiel mis à jour.');
    }

    public function destroy(AuditGrid $auditGrid): RedirectResponse
    {
        $this->authorize('delete', $auditGrid);

        $auditGrid->delete();

        return redirect()
            ->route('audits.grids.index')
            ->with('success', 'Référentiel archivé.');
    }

    public function show(AuditGrid $auditGrid): Response
    {
        $this->authorize('view', $auditGrid);

        $auditGrid->load(['items', 'items.axis']);

        $axes = AuditGridAxis::query()
            ->where('audit_grid_id', $auditGrid->id)
            ->orderBy('position')
            ->get(['id', 'code', 'title', 'position'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'title' => $a->title,
                'position' => (int) $a->position,
            ])
            ->all();

        $imperatifCount = $auditGrid->items
            ->filter(fn ($i) => $i->isImperatif())
            ->count();

        return Inertia::render('dashboard/audits/grids/show', [
            'grid' => [
                'id' => $auditGrid->id,
                'title' => $auditGrid->title,
                'description' => $auditGrid->description,
                'source' => $auditGrid->source instanceof \BackedEnum ? $auditGrid->source->value : (string) $auditGrid->source,
                'items_count' => $auditGrid->items->count(),
                'evidence_required_count' => $auditGrid->items->where('evidence_required', true)->count(),
                'imperatif_count' => $imperatifCount,
                'max_points_total' => $auditGrid->items->sum('max_points'),
            ],
            'axes' => $axes,
            'items' => $auditGrid->items->map(fn ($i) => [
                'id' => $i->id,
                'axis_id' => $i->axis_id,
                'title' => $i->title,
                'description' => $i->description,
                'scale' => $i->scale instanceof \BackedEnum ? $i->scale->value : (string) $i->scale,
                'max_points' => (float) $i->max_points,
                'evidence_required' => (bool) $i->evidence_required,
                'position' => (int) $i->position,
                'level' => $i->level instanceof \BackedEnum ? $i->level->value : ($i->level !== null ? (string) $i->level : null),
                'sources' => $i->sources,
            ])->all(),
            'can' => [
                'update' => request()->user()?->can('update', $auditGrid) ?? false,
                'delete' => request()->user()?->can('delete', $auditGrid) ?? false,
            ],
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
