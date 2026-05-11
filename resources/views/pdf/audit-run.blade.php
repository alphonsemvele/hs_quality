<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Audit — {{ $run->title }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 2px; }
        .meta { color: #4b5563; font-size: 10px; margin-bottom: 16px; }
        .meta-row { margin: 2px 0; }
        .meta-label { display: inline-block; width: 140px; color: #6b7280; }
        .score-box { border: 1px solid #d1d5db; padding: 10px; margin: 12px 0; background: #f9fafb; }
        .score-line { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: bold; }
        .num { text-align: right; white-space: nowrap; }
        .footer { margin-top: 24px; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $run->title }}</h1>
    <div class="meta">
        <div class="meta-row"><span class="meta-label">Structure :</span> {{ $structure->name }} ({{ $structure->code }})</div>
        <div class="meta-row"><span class="meta-label">Référentiel :</span> {{ $grid->title }} — {{ $grid->source?->value ?? $grid->source }}</div>
        <div class="meta-row"><span class="meta-label">Date de l'audit :</span> {{ $run->run_date?->format('d/m/Y') }}</div>
        <div class="meta-row"><span class="meta-label">Finalisé le :</span> {{ $run->finalised_at?->format('d/m/Y H:i') }}</div>
        <div class="meta-row"><span class="meta-label">Finalisé par :</span> {{ $finalisedBy?->email ?? '—' }}</div>
    </div>

    <div class="score-box">
        <div class="score-line">
            Score : {{ number_format((float) $run->score, 2, ',', ' ') }} / {{ number_format((float) $run->max_score, 2, ',', ' ') }}
            @if ((float) $run->max_score > 0)
                ({{ number_format(((float) $run->score / (float) $run->max_score) * 100, 1, ',', ' ') }} %)
            @endif
        </div>
    </div>

    <h2>Détail des items</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 60%;">Item</th>
                <th class="num" style="width: 12%;">Score</th>
                <th class="num" style="width: 12%;">Maximum</th>
                <th style="width: 16%;">Commentaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($responses as $response)
                <tr>
                    <td>{{ $response->item?->title ?? '—' }}</td>
                    <td class="num">{{ number_format((float) $response->score, 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format((float) ($response->item?->max_points ?? 0), 2, ',', ' ') }}</td>
                    <td>{{ $response->comment ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Rapport généré le {{ $generatedAt->format('d/m/Y H:i') }} — QualitéDomicile · Document confidentiel à usage interne.
    </div>
</body>
</html>
