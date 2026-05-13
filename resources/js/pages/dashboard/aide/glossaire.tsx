import { Badge, Card, EmptyState, PageHeader } from '@/components/ui';
import { glossary, type GlossaryEntry } from '@/lib/glossary';
import { useMemo, useState } from 'react';
import DashboardLayout from '../layout';

const CATEGORY_LABEL: Record<GlossaryEntry['category'], string> = {
    autonomie: "Autonomie & bénéficiaires",
    structure: 'Structures médico-sociales',
    metier: 'Métier intervenants',
    qualite: 'Qualité, contrôle & HAS',
    rh: 'RH & formation',
    tech: 'Sécurité technique',
    compliance: 'Réglementation & RGPD',
};

const CATEGORY_TONE: Record<GlossaryEntry['category'], 'brand' | 'sage' | 'warning' | 'neutral'> = {
    autonomie: 'brand',
    structure: 'sage',
    metier: 'sage',
    qualite: 'warning',
    rh: 'neutral',
    tech: 'neutral',
    compliance: 'brand',
};

const ALL_ENTRIES: GlossaryEntry[] = Object.values(glossary).sort((a, b) => a.term.localeCompare(b.term, 'fr'));

export default function GlossaireIndex() {
    const [search, setSearch] = useState('');
    const [activeCategory, setActiveCategory] = useState<GlossaryEntry['category'] | 'all'>('all');

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();
        return ALL_ENTRIES.filter((e) => {
            if (activeCategory !== 'all' && e.category !== activeCategory) {
                return false;
            }
            if (!needle) {
                return true;
            }
            return (
                e.term.toLowerCase().includes(needle)
                || e.full?.toLowerCase().includes(needle)
                || e.definition.toLowerCase().includes(needle)
            );
        });
    }, [search, activeCategory]);

    return (
        <DashboardLayout title="Glossaire métier" subtitle="Acronymes et termes techniques de l'aide à domicile">
            <PageHeader
                title="Glossaire métier"
                subtitle="Toutes les abréviations qui apparaissent dans la plateforme, expliquées en français clair"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Aide' }, { label: 'Glossaire' }]}
            />

            <Card>
                <div className="space-y-4 p-5">
                    {/* Search bar */}
                    <div className="relative">
                        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400 dark:text-ink-500">
                            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                                <circle cx="11" cy="11" r="8" />
                                <path d="M21 21l-4.35-4.35" strokeLinecap="round" />
                            </svg>
                        </span>
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher un terme (GIR, HAS, QVCT, RGPD…)"
                            className="block w-full rounded-lg border border-ink-200 bg-white py-2.5 pl-10 pr-3 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white dark:placeholder:text-ink-500"
                        />
                    </div>

                    {/* Category filter */}
                    <div className="flex flex-wrap gap-2">
                        <CategoryChip
                            active={activeCategory === 'all'}
                            onClick={() => setActiveCategory('all')}
                            label={`Tous (${ALL_ENTRIES.length})`}
                        />
                        {(Object.keys(CATEGORY_LABEL) as GlossaryEntry['category'][]).map((cat) => {
                            const count = ALL_ENTRIES.filter((e) => e.category === cat).length;
                            return (
                                <CategoryChip
                                    key={cat}
                                    active={activeCategory === cat}
                                    onClick={() => setActiveCategory(cat)}
                                    label={`${CATEGORY_LABEL[cat]} (${count})`}
                                />
                            );
                        })}
                    </div>
                </div>

                {filtered.length === 0 ? (
                    <EmptyState
                        title="Aucun terme"
                        description={search ? `Aucun résultat pour « ${search} ».` : 'Aucun terme dans cette catégorie.'}
                    />
                ) : (
                    <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                        {filtered.map((entry) => (
                            <li key={entry.term} className="px-5 py-4">
                                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h3 className="font-mono text-base font-semibold text-ink-900 dark:text-white">
                                        {entry.term}
                                    </h3>
                                    {entry.full && (
                                        <span className="text-sm text-ink-500 dark:text-ink-400">
                                            {entry.full}
                                        </span>
                                    )}
                                    <Badge tone={CATEGORY_TONE[entry.category]} size="xs">
                                        {CATEGORY_LABEL[entry.category]}
                                    </Badge>
                                </div>
                                <p className="mt-1.5 text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                                    {entry.definition}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </DashboardLayout>
    );
}

function CategoryChip({ active, onClick, label }: { active: boolean; onClick: () => void; label: string }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                active
                    ? 'inline-flex items-center rounded-full bg-brand-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40'
                    : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:border-brand-500 dark:hover:bg-brand-900/30 dark:hover:text-brand-300'
            }
        >
            {label}
        </button>
    );
}
