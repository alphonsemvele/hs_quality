<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditRun;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\PlanAmelioration;
use App\Support\UserAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cross-domain quick search for the command palette (Cmd+K).
 *
 * Tenant scoping is enforced by `BelongsToStructure` global scope on every
 * model below — we never bypass it. Ability gating mirrors the sidebar nav
 * so a user only sees groups they can already access from the menu.
 */
class SearchController extends Controller
{
    public function quick(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $query = trim((string) $request->query('q', ''));
        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json(['groups' => []]);
        }

        $abilities = UserAbilities::for($user);
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';
        // PostgreSQL exposes ILIKE for case-insensitive matching. SQLite (used
        // by the test DB) only supports LIKE, which is already case-insensitive
        // for ASCII. Pick the right operator per driver to stay portable.
        $isPg = DB::connection()->getDriverName() === 'pgsql';
        $ilike = $isPg ? 'ilike' : 'like';
        $groups = [];

        if (($abilities['beneficiaries.view'] ?? false) === true) {
            $rows = Beneficiary::query()
                ->where(function ($q) use ($like, $ilike, $isPg, $query) {
                    $q->where('first_name', $ilike, $like)
                        ->orWhere('last_name', $ilike, $like)
                        ->orWhereRaw("first_name || ' ' || last_name ".$ilike.' ?', [$like]);

                    // Trigram fuzzy match in Postgres catches typos and
                    // mis-accents that substring LIKE misses (e.g. "berge"
                    // → "Berger"). The GIN indexes from the enable_pg_trgm
                    // migration keep this cheap.
                    if ($isPg) {
                        $q->orWhereRaw('first_name % ?', [$query])
                            ->orWhereRaw('last_name % ?', [$query]);
                    }
                })
                ->when(
                    $isPg,
                    fn ($q) => $q->orderByRaw(
                        'GREATEST(similarity(first_name, ?), similarity(last_name, ?)) DESC',
                        [$query, $query],
                    ),
                )
                ->limit(5)
                ->get(['id', 'first_name', 'last_name', 'gir', 'status']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'key' => 'beneficiaries',
                    'label' => 'Bénéficiaires',
                    'items' => $rows
                        ->map(fn (Beneficiary $b) => [
                            'id' => $b->id,
                            'title' => trim($b->first_name.' '.$b->last_name),
                            'subtitle' => trim('GIR '.($b->gir ?? '—').' · '.($b->status?->value ?? '—')),
                            'href' => "/beneficiaries/{$b->id}",
                        ])
                        ->all(),
                ];
            }
        }

        if (($abilities['interventions.view'] ?? false) === true) {
            $rows = Intervention::query()
                ->with(['beneficiary:id,first_name,last_name', 'intervenant:id,first_name,last_name'])
                ->where(function ($q) use ($like, $ilike, $isPg, $query) {
                    $q->whereHas('beneficiary', function ($q2) use ($like, $ilike, $isPg, $query) {
                        $q2->where('first_name', $ilike, $like)
                            ->orWhere('last_name', $ilike, $like);
                        if ($isPg) {
                            $q2->orWhereRaw('first_name % ?', [$query])
                                ->orWhereRaw('last_name % ?', [$query]);
                        }
                    })->orWhereHas('intervenant', function ($q2) use ($like, $ilike, $isPg, $query) {
                        $q2->where('first_name', $ilike, $like)
                            ->orWhere('last_name', $ilike, $like);
                        if ($isPg) {
                            $q2->orWhereRaw('first_name % ?', [$query])
                                ->orWhereRaw('last_name % ?', [$query]);
                        }
                    });
                })
                ->latest('planned_date')
                ->limit(5)
                ->get();

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'key' => 'interventions',
                    'label' => 'Interventions',
                    'items' => $rows
                        ->map(fn (Intervention $i) => [
                            'id' => $i->id,
                            'title' => trim(
                                ($i->beneficiary ? $i->beneficiary->first_name.' '.$i->beneficiary->last_name : 'Bénéficiaire ?')
                                .' — '.
                                ($i->intervenant ? $i->intervenant->first_name.' '.$i->intervenant->last_name : '?')
                            ),
                            'subtitle' => trim(($i->planned_date?->format('d/m/Y') ?? '—').' · '.($i->status?->value ?? '—')),
                            'href' => "/interventions/{$i->id}",
                        ])
                        ->all(),
                ];
            }
        }

        if (($abilities['incidents.view'] ?? false) === true) {
            $rows = Incident::query()
                ->where('description', $ilike, $like)
                ->latest('occurred_at')
                ->limit(5)
                ->get(['id', 'occurred_at', 'categorie', 'gravite', 'statut']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'key' => 'incidents',
                    'label' => 'Incidents',
                    'items' => $rows
                        ->map(fn (Incident $i) => [
                            'id' => $i->id,
                            'title' => ($i->categorie?->label() ?? 'Incident').' · '.($i->gravite?->value ?? '—'),
                            'subtitle' => trim(($i->occurred_at?->format('d/m/Y') ?? '—').' · '.($i->statut?->value ?? '—')),
                            'href' => "/incidents/{$i->id}",
                        ])
                        ->all(),
                ];
            }
        }

        if (($abilities['audits.view'] ?? false) === true) {
            $rows = AuditRun::query()
                ->where(function ($q) use ($like, $ilike, $isPg, $query) {
                    $q->where('title', $ilike, $like);
                    if ($isPg) {
                        $q->orWhereRaw('title % ?', [$query]);
                    }
                })
                ->when($isPg, fn ($q) => $q->orderByRaw('similarity(title, ?) DESC', [$query]))
                ->latest('run_date')
                ->limit(5)
                ->get(['id', 'title', 'run_date', 'status']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'key' => 'audits',
                    'label' => 'Audits',
                    'items' => $rows
                        ->map(fn (AuditRun $a) => [
                            'id' => $a->id,
                            'title' => $a->title,
                            'subtitle' => trim(($a->run_date?->format('d/m/Y') ?? '—').' · '.($a->status?->value ?? '—')),
                            'href' => "/audits/{$a->id}",
                        ])
                        ->all(),
                ];
            }
        }

        if (($abilities['plans_amelioration.view'] ?? false) === true) {
            // Column is `titre` (French), not `title` — fix carried over from
            // the previous LIKE-only version which referenced a non-existent
            // column and silently returned zero rows.
            $rows = PlanAmelioration::query()
                ->where(function ($q) use ($like, $ilike, $isPg, $query) {
                    $q->where('titre', $ilike, $like);
                    if ($isPg) {
                        $q->orWhereRaw('titre % ?', [$query]);
                    }
                })
                ->when($isPg, fn ($q) => $q->orderByRaw('similarity(titre, ?) DESC', [$query]))
                ->latest()
                ->limit(5)
                ->get(['id', 'titre', 'statut']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'key' => 'plans_amelioration',
                    'label' => "Plans d'amélioration",
                    'items' => $rows
                        ->map(fn (PlanAmelioration $p) => [
                            'id' => $p->id,
                            'title' => $p->titre,
                            'subtitle' => (string) ($p->statut?->value ?? '—'),
                            'href' => "/plans-amelioration/{$p->id}",
                        ])
                        ->all(),
                ];
            }
        }

        return response()->json(['groups' => $groups]);
    }
}
