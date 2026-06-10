import { deletePreset, listPresets, savePreset, type FilterPreset } from '@/lib/filter-presets';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface FilterPresetsProps {
    /** Stable key used to namespace localStorage entries (e.g. "incidents"). */
    pageKey: string;
    /** Base href for the page (e.g. "/incidents"). The preset's qs is appended. */
    basePath: string;
    /** Optional className passthrough for parent layout. */
    className?: string;
    /** Whether any filter is currently active. Drives the "Sauvegarder" button. */
    hasActiveFilter?: boolean;
}

/**
 * Per-page bar of saved filter presets.
 *
 * Renders nothing on first render when the user has no saved presets
 * AND no filter is currently active — the affordance is opt-in, it
 * shouldn't add noise to a clean list view.
 *
 * Click a preset → navigates to `basePath?qs` (replaces current URL,
 * triggers an Inertia visit so server-driven filtering re-runs).
 */
export function FilterPresets({ pageKey, basePath, className, hasActiveFilter }: FilterPresetsProps) {
    const [presets, setPresets] = useState<FilterPreset[]>([]);
    const [adding, setAdding] = useState(false);
    const [draftName, setDraftName] = useState('');

    useEffect(() => {
        const sync = () => setPresets(listPresets(pageKey));
        sync();
        const handler = (e: Event) => {
            const detail = (e as CustomEvent<{ pageKey: string }>).detail;
            if (!detail || detail.pageKey === pageKey) sync();
        };
        window.addEventListener('hsq:filter-presets-changed', handler);
        return () => window.removeEventListener('hsq:filter-presets-changed', handler);
    }, [pageKey]);

    const applyPreset = (preset: FilterPreset) => {
        const target = preset.qs ? `${basePath}?${preset.qs}` : basePath;
        router.visit(target);
    };

    const handleSave = () => {
        const name = draftName.trim();
        if (!name) return;
        const qs = typeof window !== 'undefined' ? window.location.search : '';
        savePreset(pageKey, name, qs);
        setDraftName('');
        setAdding(false);
    };

    if (presets.length === 0 && !hasActiveFilter) return null;

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                Filtres sauvegardés
            </span>
            {presets.map((p) => (
                <span
                    key={p.id}
                    className="inline-flex items-center gap-1 rounded-full border border-ink-200 bg-white pl-3 pr-1 py-0.5 text-[11px] font-medium text-ink-700 transition-colors hover:border-ink-300 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:border-ink-600"
                >
                    <button
                        type="button"
                        onClick={() => applyPreset(p)}
                        className="py-1"
                        title={`Appliquer : ${p.qs || 'aucun filtre'}`}
                    >
                        {p.name}
                    </button>
                    <button
                        type="button"
                        onClick={() => deletePreset(pageKey, p.id)}
                        aria-label={`Supprimer le filtre « ${p.name} »`}
                        className="inline-flex size-5 items-center justify-center rounded-full text-ink-400 hover:bg-ink-100 hover:text-danger-600 dark:hover:bg-ink-700"
                    >
                        <svg className="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden>
                            <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
                        </svg>
                    </button>
                </span>
            ))}

            {adding ? (
                <span className="inline-flex items-center gap-1 rounded-full border border-brand-200 bg-brand-50 px-2 py-0.5 dark:border-brand-700/40 dark:bg-brand-900/30">
                    <input
                        type="text"
                        autoFocus
                        value={draftName}
                        onChange={(e) => setDraftName(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') handleSave();
                            if (e.key === 'Escape') {
                                setAdding(false);
                                setDraftName('');
                            }
                        }}
                        placeholder="Nom du filtre"
                        maxLength={40}
                        aria-label="Nom du filtre sauvegardé"
                        className="h-6 w-32 bg-transparent text-[11px] text-ink-900 placeholder:text-ink-400 focus:outline-none dark:text-white"
                    />
                    <button
                        type="button"
                        onClick={handleSave}
                        disabled={!draftName.trim()}
                        className="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-semibold text-white disabled:opacity-50 dark:bg-brand-500"
                    >
                        OK
                    </button>
                </span>
            ) : (
                hasActiveFilter && presets.length < 6 && (
                    <button
                        type="button"
                        onClick={() => setAdding(true)}
                        className="inline-flex items-center gap-1 rounded-full border border-dashed border-ink-300 px-3 py-1 text-[11px] font-medium text-ink-500 hover:border-brand-400 hover:text-brand-600 dark:border-ink-600 dark:text-ink-400 dark:hover:border-brand-400 dark:hover:text-brand-300"
                    >
                        + Sauvegarder le filtre actuel
                    </button>
                )
            )}
        </div>
    );
}
