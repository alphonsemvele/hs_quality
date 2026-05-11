export function AnonymityBanner({
    variant = 'inline',
    threshold,
}: {
    variant?: 'inline' | 'callout';
    threshold?: number;
}) {
    if (variant === 'callout') {
        return (
            <div className="rounded-2xl border border-sage-200 bg-sage-50/60 p-4 dark:border-sage-700/50 dark:bg-sage-900/15">
                <div className="flex gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300">
                        <ShieldIcon />
                    </span>
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-sage-900 dark:text-sage-100">Réponses anonymes</p>
                        <p className="mt-1 text-xs leading-relaxed text-sage-800/80 dark:text-sage-200/80">
                            Vos réponses sont collectées de façon anonyme. Aucune information ne permettra de vous identifier dans les
                            résultats {threshold ? `(seuil de ${threshold} répondants minimum par équipe pour publier les résultats)` : ''}.
                            Seules des statistiques agrégées seront partagées avec votre direction.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="inline-flex items-center gap-2 rounded-full border border-sage-200 bg-sage-50 px-2.5 py-1 text-[11px] font-medium text-sage-800 dark:border-sage-700/40 dark:bg-sage-900/25 dark:text-sage-200">
            <ShieldIcon />
            Anonyme · Non traçable
        </div>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
        </svg>
    );
}
