import { Modal } from '@/components/ui';
import { useEffect, useState } from 'react';

interface Shortcut {
    keys: string[];
    label: string;
}

interface ShortcutsGroup {
    title: string;
    items: Shortcut[];
}

const GROUPS: ShortcutsGroup[] = [
    {
        title: 'Recherche & navigation',
        items: [
            { keys: ['⌘', 'K'], label: 'Ouvrir la palette de recherche' },
            { keys: ['/'], label: 'Ouvrir la palette (alternative)' },
            { keys: ['Esc'], label: 'Fermer la palette / les modales' },
            { keys: ['?'], label: 'Afficher ce panneau' },
        ],
    },
    {
        title: 'Édition (formulaires Markdown)',
        items: [
            { keys: ['⌘', 'B'], label: 'Mettre en gras' },
            { keys: ['⌘', 'I'], label: 'Mettre en italique' },
            { keys: ['⌘', 'K'], label: 'Insérer un lien' },
        ],
    },
    {
        title: 'Navigation dans une liste',
        items: [
            { keys: ['↑'], label: 'Sélection précédente' },
            { keys: ['↓'], label: 'Sélection suivante' },
            { keys: ['↵'], label: 'Ouvrir la sélection' },
        ],
    },
    {
        title: 'Aller à (g + touche)',
        items: [
            { keys: ['g', 'd'], label: 'Tableau de bord' },
            { keys: ['g', 'i'], label: 'Interventions' },
            { keys: ['g', 'b'], label: 'Bénéficiaires' },
            { keys: ['g', 'a'], label: 'Audits' },
            { keys: ['g', 'p'], label: "Plans d'amélioration" },
            { keys: ['g', 'q'], label: 'QVCT' },
            { keys: ['g', 'n'], label: 'Incidents' },
            { keys: ['g', 'f'], label: 'Formations' },
            { keys: ['g', 'c'], label: 'Communication' },
        ],
    },
];

/**
 * Global keyboard shortcuts cheatsheet.
 *
 * Press `?` anywhere on the app (outside an input/contenteditable) and
 * a modal pops listing every documented shortcut. Mounted once in the
 * dashboard layout so it's reachable from any page.
 */
export function ShortcutsCheatsheet() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.key !== '?') return;
            const target = e.target as HTMLElement | null;
            if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
            if (target?.isContentEditable) return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            e.preventDefault();
            setOpen((o) => !o);
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, []);

    return (
        <Modal
            open={open}
            onClose={() => setOpen(false)}
            title="Raccourcis clavier"
            description="Tous les raccourcis disponibles dans l'application."
            iconTone="brand"
            icon={
                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                    <rect x="2" y="6" width="20" height="14" rx="2" />
                    <path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12" strokeLinecap="round" />
                </svg>
            }
            size="md"
        >
            <div className="space-y-5">
                {GROUPS.map((group) => (
                    <section key={group.title}>
                        <h3 className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            {group.title}
                        </h3>
                        <dl className="mt-2 divide-y divide-ink-100 dark:divide-ink-700/60">
                            {group.items.map((s) => (
                                <div key={s.label} className="flex items-center justify-between py-2 text-sm">
                                    <dt className="text-ink-700 dark:text-ink-200">{s.label}</dt>
                                    <dd className="flex items-center gap-1">
                                        {s.keys.map((k, i) => (
                                            <kbd
                                                key={i}
                                                className="rounded-md border border-ink-200 bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-ink-700 shadow-sm dark:border-ink-600 dark:bg-ink-700 dark:text-ink-200"
                                            >
                                                {k}
                                            </kbd>
                                        ))}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                ))}
                <p className="text-[11px] text-ink-500 dark:text-ink-400">
                    Sur Windows / Linux, ⌘ correspond à <kbd className="rounded border border-ink-200 px-1 font-mono dark:border-ink-600">Ctrl</kbd>.
                </p>
            </div>
        </Modal>
    );
}
