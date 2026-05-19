<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Annuel Qualité {{ $year }} — {{ $structure->name }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 20px; }
    h1 { font-size: 18px; color: #2563eb; margin-bottom: 4px; }
    h2 { font-size: 13px; color: #1e40af; border-bottom: 1px solid #bfdbfe; padding-bottom: 4px; margin-top: 20px; }
    .meta { color: #6b7280; font-size: 10px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #eff6ff; text-align: left; padding: 5px 8px; font-size: 10px; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    .kpi { font-size: 22px; font-weight: bold; color: #2563eb; }
    .kpi-label { font-size: 10px; color: #6b7280; }
    .grid-2 { display: table; width: 100%; }
    .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
    .badge-ok  { background: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 3px; }
    .badge-warn{ background: #fef9c3; color: #854d0e; padding: 2px 6px; border-radius: 3px; }
    .badge-bad { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 3px; }
    footer { margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 9px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>

<h1>Rapport Annuel Qualité {{ $year }}</h1>
<div class="meta">{{ $structure->name }} — {{ $structure->type?->value }} — Généré le {{ now()->format('d/m/Y') }}</div>

{{-- M1 — Interventions --}}
<h2>M1 — Traçabilité des interventions</h2>
<table>
    <tr><th>Statut</th><th>Nombre</th></tr>
    @forelse($interventionStats as $status => $count)
    <tr><td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td><td>{{ $count }}</td></tr>
    @empty
    <tr><td colspan="2">Aucune intervention sur la période.</td></tr>
    @endforelse
</table>

{{-- M2 — Incidents --}}
<h2>M2 — Gestion des incidents</h2>
<table>
    <tr><th>Gravité</th><th>Nombre</th></tr>
    @forelse($incidentStats as $gravite => $count)
    <tr><td>{{ ucfirst($gravite) }}</td><td>{{ $count }}</td></tr>
    @empty
    <tr><td colspan="2">Aucun incident sur la période.</td></tr>
    @endforelse
</table>

{{-- M3 — QVCT --}}
<h2>M3 — Qualité de vie au travail (QVCT)</h2>
<div>
    @if($avgQvct !== null)
        <span class="kpi">{{ round($avgQvct, 1) }}/5</span>
        <span class="kpi-label">Score QVCT moyen</span>
    @else
        <em>Aucune réponse enregistrée cette année.</em>
    @endif
</div>

{{-- M5 — Compétences --}}
<h2>M5 — Compétences & formation</h2>
<table>
    <tr><th>Indicateur</th><th>Valeur</th></tr>
    <tr><td>Formations suivies (attestées)</td><td>{{ $trainingCompletions }}</td></tr>
    <tr><td>Certifications expirées en {{ $year }}</td><td>{{ $expiringCerts }}</td></tr>
</table>

{{-- M6 — Audits --}}
<h2>M6 — Audits & conformité</h2>
@if($audits->isEmpty())
    <p><em>Aucun audit finalisé sur la période.</em></p>
@else
    <table>
        <tr><th>Audit</th><th>Score</th><th>Conformité</th></tr>
        @foreach($audits as $audit)
        <tr>
            <td>{{ $audit->title }}</td>
            <td>{{ $audit->score }} / {{ $audit->max_score }}</td>
            <td>
                @if($audit->max_score > 0)
                    @php $pct = round($audit->score / $audit->max_score * 100, 1) @endphp
                    <span class="{{ $pct >= 70 ? 'badge-ok' : ($pct >= 40 ? 'badge-warn' : 'badge-bad') }}">{{ $pct }}%</span>
                @else —
                @endif
            </td>
        </tr>
        @endforeach
    </table>
    @if($avgConformite !== null)
        <p><strong>Conformité moyenne : {{ $avgConformite }}%</strong></p>
    @endif
@endif

<footer>
    QualitéDomicile — Rapport généré automatiquement · Données {{ $year }} · {{ $structure->name }}
</footer>
</body>
</html>
