<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Features\QvctWeakSignalAlerts;
use App\Http\Controllers\Controller;
use App\Models\Structure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Pennant\Feature;

/**
 * Super-admin surface for Laravel Pennant feature flags. Lists every flag
 * defined in `app/Features/*`, shows its per-structure activation state and
 * lets a platform operator toggle it tenant by tenant.
 *
 * Why not config: per-structure granularity is required (a pilot tenant
 * reporting alert fatigue must be mutable without code changes). Pennant's
 * scoped resolver is the canonical mechanism for this.
 */
class FeatureFlagController extends Controller
{
    /**
     * Catalogue of platform-managed flags. Keep in sync with `app/Features/*`.
     *
     * @var array<int, array{class: class-string, label: string, description: string, default_on: bool}>
     */
    private const FLAGS = [
        [
            'class' => QvctWeakSignalAlerts::class,
            'label' => 'Alertes signaux faibles QVCT',
            'description' => "Fan-out e-mail vers le référent RH, le dirigeant et le référent qualité quand un signal faible QVCT est détecté. Les lignes en base et le journal d'audit persistent indépendamment — désactiver ce flag suspend uniquement les notifications.",
            'default_on' => true,
        ],
    ];

    public function index(): Response
    {
        $structures = Structure::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status']);

        $flags = collect(self::FLAGS)->map(function (array $flag) use ($structures): array {
            $perStructure = $structures->map(fn (Structure $structure): array => [
                'structure_id' => $structure->id,
                'structure_code' => $structure->code,
                'structure_name' => $structure->name,
                'structure_status' => $structure->status,
                'active' => Feature::for($structure)->active($flag['class']),
            ])->all();

            return [
                'key' => $flag['class'],
                'short_key' => class_basename($flag['class']),
                'label' => $flag['label'],
                'description' => $flag['description'],
                'default_on' => $flag['default_on'],
                'per_structure' => $perStructure,
                'active_count' => collect($perStructure)->where('active', true)->count(),
                'total_count' => count($perStructure),
            ];
        })->all();

        return Inertia::render('admin/feature-flags/index', [
            'flags' => $flags,
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'flag' => ['required', 'string'],
            'structure_id' => ['required', 'integer', 'exists:structures,id'],
            'active' => ['required', 'boolean'],
        ]);

        $allowed = collect(self::FLAGS)->pluck('class')->all();
        abort_unless(in_array($data['flag'], $allowed, true), 404);

        $structure = Structure::query()->findOrFail($data['structure_id']);

        if ($data['active']) {
            Feature::for($structure)->activate($data['flag']);
        } else {
            Feature::for($structure)->deactivate($data['flag']);
        }

        return back()->with('flash.success', sprintf(
            'Flag « %s » %s pour %s.',
            class_basename($data['flag']),
            $data['active'] ? 'activé' : 'désactivé',
            $structure->name,
        ));
    }
}
