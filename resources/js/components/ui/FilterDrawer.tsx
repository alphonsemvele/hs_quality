import { cn } from '@/lib/utils';
import { useSavedViews, type SavedView } from '@/lib/saved-views';
import { router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';
import { Badge } from './Badge';
import { Button } from './Button';
import { Sheet } from './Sheet';

interface FilterDrawerProps {
    open: boolean;
    onClose: () => void;
    pageKey: string;
    basePath: string;
    currentQuery: Record<string, string | null | undefined>;
    appliedCount: number;
    onApply: (query: Record<string, string>) => void;
    onReset: () => void;
    title?: string;
    children: ReactNode;
}

export function FilterDrawer({
    open,
    onClose,
    pageKey,
    basePath,
    currentQuery,
    appliedCount,
    onApply: _onApply,
    onReset,
    title = 'Filtres avancés',
    children,
}: FilterDrawerProps) {
    const { views, save, remove } = useSavedViews(pageKey);
    const [savingName, setSavingName] = useState('');
    const [showSaveInput, setShowSaveInput] = useState(false);

    const currentSearch = buildSearch(currentQuery);

    const applyView = (view: SavedView) => {
        router.get(basePath + view.query, {}, { preserveScroll: true });
        onClose();
    };

    const saveCurrent = () => {
        if (!savingName.trim()) return;
        save(savingName, currentSearch);
        setSavingName('');
        setShowSaveInput(false);
    };

    return (
        <Sheet
            open={open}
            onClose={onClose}
            size="lg"
            title={title}
            description={appliedCount > 0 ? `${appliedCount} filtre${appliedCount > 1 ? 's' : ''} actif${appliedCount > 1 ? 's' : ''}` : 'Aucun filtre actif'}
            iconTone="brand"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                </svg>
            }
            footer={
                <>
                    <Button variant="ghost" onClick={() => { onReset(); onClose(); }} disabled={appliedCount === 0}>
                        Tout effacer
                    </Button>
                    <Button onClick={onClose}>Fermer</Button>
                </>
            }
        >
            <div className="space-y-6">
                {/* Saved views */}
                {(views.length > 0 || appliedCount > 0) && (
                    <section>
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                Vues sauvegardées
                            </p>
                            {appliedCount > 0 && !showSaveInput && (
                                <button
                                    type="button"
                                    onClick={() => setShowSaveInput(true)}
                                    className="text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400"
                                >
                                    + Sauvegarder cette vue
                                </button>
                            )}
                        </div>

                        {showSaveInput && (
                            <div className="mb-3 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/40 p-2 dark:border-brand-700/40 dark:bg-brand-900/15">
                                <input
                                    autoFocus
                                    value={savingName}
                                    onChange={(e) => setSavingName(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') saveCurrent();
                                        if (e.key === 'Escape') { setShowSaveInput(false); setSavingName(''); }
                                    }}
                                    placeholder="Ex: Tournée du matin, Incidents non clos…"
                                    maxLength={64}
                                    className="h-8 flex-1 rounded-md border border-brand-200 bg-white px-2 text-xs text-ink-900 focus:border-brand-400 focus:outline-none dark:border-brand-700/40 dark:bg-ink-800 dark:text-white"
                                />
                                <Button size="sm" onClick={saveCurrent} disabled={!savingName.trim()}>
                                    Sauver
                                </Button>
                                <Button size="sm" variant="ghost" onClick={() => { setShowSaveInput(false); setSavingName(''); }}>
                                    ✕
                                </Button>
                            </div>
                        )}

                        {views.length > 0 ? (
                            <ul className="space-y-1.5">
                                {views.map((v) => (
                                    <li key={v.id} className="group flex items-center gap-2 rounded-lg border border-ink-100 bg-white px-2.5 py-1.5 hover:border-brand-300 dark:border-ink-700 dark:bg-ink-800 dark:hover:border-brand-500">
                                        <button
                                            type="button"
                                            onClick={() => applyView(v)}
                                            className="flex flex-1 items-center gap-2 text-left"
                                        >
                                            <span className="size-1.5 rounded-full bg-brand-500" />
                                            <span className="truncate text-sm font-medium text-ink-900 dark:text-white">{v.name}</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => remove(v.id)}
                                            aria-label={`Supprimer la vue ${v.name}`}
                                            className="rounded-md p-1 text-ink-400 opacity-0 transition-opacity hover:bg-danger-50 hover:text-danger-600 group-hover:opacity-100 dark:text-ink-500 dark:hover:bg-danger-900/30 dark:hover:text-danger-400"
                                        >
                                            <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                                            </svg>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="rounded-lg border border-dashed border-ink-200 bg-ink-50/40 px-3 py-2 text-[11px] text-ink-500 dark:border-ink-700 dark:bg-ink-900/30 dark:text-ink-400">
                                Aucune vue sauvegardée. Appliquez des filtres ci-dessous puis sauvegardez-les pour un accès rapide.
                            </p>
                        )}
                    </section>
                )}

                {/* Filter fields (delegated to children) */}
                <section>
                    <p className="mb-3 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        Critères
                    </p>
                    <div className="space-y-4">{children}</div>
                </section>
            </div>
        </Sheet>
    );
}

/**
 * Light helper component to surface active filters as chips in the page header.
 */
export function FilterChipsBar({
    chips,
    onRemove,
    onOpenDrawer,
    onResetAll,
}: {
    chips: Array<{ key: string; label: string; value: string }>;
    onRemove: (key: string) => void;
    onOpenDrawer: () => void;
    onResetAll?: () => void;
}) {
    return (
        <div className="flex flex-wrap items-center gap-2">
            <button
                type="button"
                onClick={onOpenDrawer}
                className={cn(
                    'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                    chips.length > 0
                        ? 'border-brand-300 bg-brand-50 text-brand-700 hover:bg-brand-100 dark:border-brand-700 dark:bg-brand-900/30 dark:text-brand-200'
                        : 'border-ink-200 bg-white text-ink-600 hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:bg-ink-700/40',
                )}
            >
                <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                </svg>
                Filtres
                {chips.length > 0 && (
                    <Badge tone="brand" size="xs">
                        {chips.length}
                    </Badge>
                )}
            </button>

            {chips.map((c) => (
                <span
                    key={c.key}
                    className="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-200"
                >
                    <span className="font-semibold">{c.label}:</span>
                    <span>{c.value}</span>
                    <button
                        type="button"
                        onClick={() => onRemove(c.key)}
                        aria-label={`Retirer le filtre ${c.label}`}
                        className="ml-0.5 rounded-full p-0.5 text-brand-500 hover:bg-brand-100 hover:text-brand-800 dark:hover:bg-brand-900/50"
                    >
                        <svg className="size-2.5" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18" strokeLinecap="round" />
                            <line x1="6" y1="6" x2="18" y2="18" strokeLinecap="round" />
                        </svg>
                    </button>
                </span>
            ))}

            {chips.length > 1 && onResetAll && (
                <button
                    type="button"
                    onClick={onResetAll}
                    className="text-[11px] font-medium text-ink-500 hover:text-danger-600 dark:text-ink-400 dark:hover:text-danger-400"
                >
                    Tout effacer
                </button>
            )}
        </div>
    );
}

function buildSearch(query: Record<string, string | null | undefined>): string {
    const params = new URLSearchParams();
    Object.entries(query).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') params.set(k, String(v));
    });
    const s = params.toString();
    return s ? `?${s}` : '';
}
