import { Skeleton } from '@/components/ui';
import { useAbilities, type Ability } from '@/lib/can';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

interface SearchItem {
    id: string;
    title: string;
    subtitle: string;
    href: string;
}

interface SearchGroup {
    key: string;
    label: string;
    items: SearchItem[];
}

interface QuickAction {
    id: string;
    label: string;
    href: string;
    shortcut?: string;
    keywords?: string[];
    requires?: Ability;
    danger?: boolean;
}

const QUICK_ACTIONS: QuickAction[] = [
    { id: 'home', label: 'Tableau de bord', href: '/dashboard', keywords: ['dashboard', 'accueil', 'home'] },
    { id: 'interventions', label: 'Liste des interventions', href: '/interventions', keywords: ['visites', 'tournée'], requires: 'interventions.view' },
    { id: 'new-intervention', label: 'Nouvelle intervention', href: '/interventions/create', keywords: ['planifier', 'créer'], requires: 'interventions.create' },
    { id: 'beneficiaries', label: 'Liste des bénéficiaires', href: '/beneficiaries', keywords: ['patients', 'usagers'], requires: 'beneficiaries.view' },
    { id: 'incidents', label: 'Liste des incidents', href: '/incidents', keywords: ['ei', 'événements'], requires: 'incidents.view' },
    { id: 'declare-incident', label: 'Déclarer un incident', href: '/incidents/create', keywords: ['signaler', 'chute', 'urgence'], requires: 'incidents.create', danger: true },
    { id: 'audits', label: 'Audits & conformité', href: '/audits', keywords: ['has', 'iso', 'afnor'], requires: 'audits.view' },
    { id: 'pac', label: "Plans d'amélioration", href: '/plans-amelioration', keywords: ['pac', 'qualité'], requires: 'plans_amelioration.view' },
    { id: 'indicators', label: 'Indicateurs', href: '/indicateurs', keywords: ['kpi', 'stats', 'graphiques'], requires: 'indicateurs.view' },
    { id: 'qvct', label: 'Baromètre QVCT', href: '/qvct', keywords: ['rps', 'rh', 'bien-être'], requires: 'qvct.view' },
    { id: 'profile', label: 'Mon profil', href: '/dashboard/profile', keywords: ['compte', 'mfa', '2fa'] },
];

const HISTORY_KEY = 'hsq.cmdk.history';
const HISTORY_MAX = 5;

interface HistoryEntry {
    id: string;
    title: string;
    subtitle: string;
    href: string;
}

function readHistory(): HistoryEntry[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = window.localStorage.getItem(HISTORY_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw) as HistoryEntry[];
        return Array.isArray(parsed) ? parsed.slice(0, HISTORY_MAX) : [];
    } catch {
        return [];
    }
}

function pushHistory(entry: HistoryEntry): void {
    if (typeof window === 'undefined') return;
    try {
        const current = readHistory().filter((e) => e.href !== entry.href);
        const next = [entry, ...current].slice(0, HISTORY_MAX);
        window.localStorage.setItem(HISTORY_KEY, JSON.stringify(next));
    } catch {
        // localStorage disabled — silently skip.
    }
}

export default function CommandPalette() {
    const abilities = useAbilities();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [groups, setGroups] = useState<SearchGroup[]>([]);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState(0);
    const [history, setHistory] = useState<HistoryEntry[]>([]);
    const inputRef = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    // ── Toggle hotkeys ───────────────────────────────────────────
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            const isMod = e.metaKey || e.ctrlKey;
            if (isMod && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setOpen((o) => !o);
                return;
            }
            if (e.key === '/' && !open) {
                const target = e.target as HTMLElement;
                if (target && !['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) && !target.isContentEditable) {
                    e.preventDefault();
                    setOpen(true);
                }
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open]);

    // ── Focus input on open + refresh history ────────────────────
    useEffect(() => {
        if (open) {
            setTimeout(() => inputRef.current?.focus(), 30);
            setSelected(0);
            setHistory(readHistory());
        } else {
            setQuery('');
            setGroups([]);
        }
    }, [open]);

    // ── Debounced fetch ──────────────────────────────────────────
    useEffect(() => {
        if (!open) return;
        const q = query.trim();
        if (q.length < 2) {
            setGroups([]);
            setLoading(false);
            abortRef.current?.abort();
            return;
        }
        const ac = new AbortController();
        abortRef.current?.abort();
        abortRef.current = ac;
        setLoading(true);
        const timer = setTimeout(async () => {
            try {
                const res = await fetch(`/search/quick?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: ac.signal,
                });
                if (!res.ok) {
                    setGroups([]);
                    return;
                }
                const data = (await res.json()) as { groups: SearchGroup[] };
                setGroups(data.groups ?? []);
            } catch (err) {
                if ((err as Error).name !== 'AbortError') {
                    setGroups([]);
                }
            } finally {
                if (!ac.signal.aborted) setLoading(false);
            }
        }, 180);
        return () => {
            clearTimeout(timer);
            ac.abort();
        };
    }, [query, open]);

    const visibleActions = useMemo(() => {
        const q = query.trim().toLowerCase();
        const filtered = QUICK_ACTIONS.filter((a) => !a.requires || abilities[a.requires] === true);
        if (!q) return filtered;
        return filtered.filter(
            (a) =>
                a.label.toLowerCase().includes(q) ||
                (a.keywords ?? []).some((k) => k.toLowerCase().includes(q)),
        );
    }, [query, abilities]);

    const visibleHistory = useMemo(() => (query.trim() === '' ? history : []), [query, history]);

    const flatItems = useMemo(() => {
        const items: Array<{ kind: 'history' | 'action' | 'result'; href: string; entry?: HistoryEntry; danger?: boolean }> = [];
        visibleHistory.forEach((h) => items.push({ kind: 'history', href: h.href, entry: h }));
        visibleActions.forEach((a) => items.push({ kind: 'action', href: a.href, danger: a.danger }));
        groups.forEach((g) => g.items.forEach((i) => items.push({ kind: 'result', href: i.href })));
        return items;
    }, [visibleActions, visibleHistory, groups]);

    // Clamp selected when list shrinks
    useEffect(() => {
        if (selected >= flatItems.length) setSelected(0);
    }, [flatItems.length, selected]);

    const go = (href: string, entry?: HistoryEntry) => {
        if (entry) {
            pushHistory(entry);
        }
        setOpen(false);
        router.visit(href);
    };

    const handleKey = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setSelected((s) => Math.min(s + 1, Math.max(flatItems.length - 1, 0)));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSelected((s) => Math.max(s - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const target = flatItems[selected];
            if (target) go(target.href, target.entry);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(false);
        }
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="Rechercher (Cmd+K)"
                className="hidden cursor-pointer items-center gap-2 rounded-lg border border-ink-200 bg-white px-2.5 py-1.5 text-xs text-ink-500 transition-colors hover:border-ink-300 hover:text-ink-700 lg:flex dark:border-ink-700 dark:bg-ink-800 dark:text-ink-400 dark:hover:border-ink-600 dark:hover:text-ink-200"
            >
                <SearchIcon />
                <span>Rechercher</span>
                <kbd className="ml-3 rounded border border-ink-200 bg-ink-50 px-1.5 py-0.5 font-mono text-[10px] font-medium text-ink-500 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-400">
                    ⌘K
                </kbd>
            </button>
        );
    }

    return (
        <div className="fixed inset-0 z-[100] flex items-start justify-center p-4 pt-[10vh] sm:pt-[14vh]" role="dialog" aria-modal>
            <button
                type="button"
                aria-label="Fermer la recherche"
                onClick={() => setOpen(false)}
                className="absolute inset-0 cursor-default bg-ink-900/40 backdrop-blur-sm dark:bg-ink-900/70"
            />
            <div className="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-2xl dark:border-ink-700 dark:bg-ink-800">
                <div className="flex items-center gap-3 border-b border-ink-100 px-4 dark:border-ink-700/60">
                    <SearchIcon />
                    <input
                        ref={inputRef}
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onKeyDown={handleKey}
                        placeholder="Rechercher bénéficiaires, interventions, incidents, audits…"
                        className="h-12 flex-1 bg-transparent text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none dark:text-white"
                        aria-label="Recherche transversale"
                    />
                    <kbd className="hidden rounded border border-ink-200 bg-ink-50 px-1.5 py-0.5 font-mono text-[10px] font-medium text-ink-500 sm:inline-block dark:border-ink-600 dark:bg-ink-900 dark:text-ink-400">
                        Esc
                    </kbd>
                </div>

                <div ref={listRef} className="max-h-[60vh] overflow-y-auto px-2 py-2">
                    {visibleHistory.length > 0 && (
                        <Section label="Récents">
                            {visibleHistory.map((h, i) => (
                                <Row
                                    key={`recent-${h.href}`}
                                    label={h.title}
                                    subtitle={h.subtitle}
                                    icon={<ClockIcon />}
                                    active={selected === i}
                                    onMouseEnter={() => setSelected(i)}
                                    onClick={() => go(h.href, h)}
                                />
                            ))}
                        </Section>
                    )}

                    {visibleActions.length > 0 && (
                        <Section label="Actions rapides">
                            {visibleActions.map((a, i) => {
                                const idx = visibleHistory.length + i;
                                return (
                                    <Row
                                        key={a.id}
                                        label={a.label}
                                        icon={<BoltIcon />}
                                        danger={a.danger}
                                        active={selected === idx}
                                        onMouseEnter={() => setSelected(idx)}
                                        onClick={() =>
                                            go(a.href, { id: a.id, title: a.label, subtitle: 'Action rapide', href: a.href })
                                        }
                                    />
                                );
                            })}
                        </Section>
                    )}

                    {groups.map((group, gi) => {
                        const offset =
                            visibleHistory.length +
                            visibleActions.length +
                            groups.slice(0, gi).reduce((s, g) => s + g.items.length, 0);
                        return (
                            <Section key={group.key} label={group.label}>
                                {group.items.map((it, i) => {
                                    const idx = offset + i;
                                    return (
                                        <Row
                                            key={it.id}
                                            label={it.title}
                                            subtitle={it.subtitle}
                                            icon={iconForGroup(group.key)}
                                            active={selected === idx}
                                            onMouseEnter={() => setSelected(idx)}
                                            onClick={() => go(it.href, { id: it.id, title: it.title, subtitle: it.subtitle, href: it.href })}
                                        />
                                    );
                                })}
                            </Section>
                        );
                    })}

                    {!loading && visibleActions.length === 0 && groups.length === 0 && (
                        <div className="px-6 py-10 text-center">
                            {query.trim().length < 2 ? (
                                <p className="text-sm text-ink-500 dark:text-ink-400">
                                    Tapez au moins 2 caractères pour rechercher.
                                </p>
                            ) : (
                                <p className="text-sm text-ink-500 dark:text-ink-400">
                                    Aucun résultat pour « <span className="font-medium text-ink-700 dark:text-ink-200">{query}</span> ».
                                </p>
                            )}
                        </div>
                    )}

                    {loading && (
                        <div className="space-y-2 px-4 py-3" aria-label="Recherche en cours">
                            {[0, 1, 2].map((i) => (
                                <div key={i} className="flex items-center gap-3 px-2 py-1.5">
                                    <Skeleton shape="circle" w="size-7" />
                                    <div className="flex-1 space-y-1.5">
                                        <Skeleton shape="text" w={i === 0 ? 'w-3/4' : i === 1 ? 'w-1/2' : 'w-2/3'} />
                                        <Skeleton shape="text" w={i === 0 ? 'w-1/2' : 'w-1/3'} className="h-3" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="flex items-center justify-between gap-3 border-t border-ink-100 bg-ink-50/60 px-4 py-2 text-[11px] text-ink-500 dark:border-ink-700/60 dark:bg-ink-900/40 dark:text-ink-400">
                    <div className="flex items-center gap-3">
                        <Hint k="↑↓" l="Naviguer" />
                        <Hint k="↵" l="Ouvrir" />
                        <Hint k="Esc" l="Fermer" />
                    </div>
                    <span className="hidden sm:inline">Données tenant uniquement</span>
                </div>
            </div>
        </div>
    );
}

function Section({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="mb-1">
            <p className="px-3 pb-1 pt-2 text-[10px] font-semibold uppercase tracking-wider text-ink-400 dark:text-ink-500">
                {label}
            </p>
            <div className="flex flex-col gap-0.5">{children}</div>
        </div>
    );
}

function Row({
    label,
    subtitle,
    icon,
    active,
    danger,
    onClick,
    onMouseEnter,
}: {
    label: string;
    subtitle?: string;
    icon?: React.ReactNode;
    active?: boolean;
    danger?: boolean;
    onClick?: () => void;
    onMouseEnter?: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            onMouseEnter={onMouseEnter}
            className={cn(
                'flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors',
                active
                    ? 'bg-brand-50 text-brand-900 dark:bg-brand-900/30 dark:text-brand-100'
                    : 'text-ink-700 hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/40',
                danger && !active && 'text-danger-700 dark:text-danger-300',
            )}
        >
            <span
                className={cn(
                    'flex size-7 shrink-0 items-center justify-center rounded-md',
                    active
                        ? 'bg-brand-100 text-brand-700 dark:bg-brand-800/60 dark:text-brand-200'
                        : 'bg-ink-100 text-ink-500 dark:bg-ink-700 dark:text-ink-400',
                    danger && !active && 'bg-danger-50 text-danger-600 dark:bg-danger-900/40 dark:text-danger-300',
                )}
            >
                {icon ?? <BoltIcon />}
            </span>
            <span className="min-w-0 flex-1">
                <span className="block truncate font-medium">{label}</span>
                {subtitle && (
                    <span className="block truncate text-[11px] text-ink-500 dark:text-ink-400">{subtitle}</span>
                )}
            </span>
            {active && <span className="font-mono text-[10px] text-brand-600 dark:text-brand-300">↵</span>}
        </button>
    );
}

function Hint({ k, l }: { k: string; l: string }) {
    return (
        <span className="flex items-center gap-1">
            <kbd className="rounded border border-ink-200 bg-white px-1.5 py-0.5 font-mono text-[10px] font-medium text-ink-600 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-300">
                {k}
            </kbd>
            <span>{l}</span>
        </span>
    );
}

function iconForGroup(key: string): React.ReactNode {
    switch (key) {
        case 'beneficiaries': return <UserIcon />;
        case 'interventions': return <ClipboardIcon />;
        case 'incidents': return <AlertIcon />;
        case 'audits': return <ShieldIcon />;
        case 'plans_amelioration': return <CheckIcon />;
        default: return <BoltIcon />;
    }
}

function SearchIcon() {
    return (
        <svg className="size-4 shrink-0" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4.35-4.35" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function BoltIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
}
function ClockIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" strokeLinecap="round" strokeLinejoin="round" /></svg>;
}
function UserIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>;
}
function ClipboardIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>;
}
function AlertIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></svg>;
}
function ShieldIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><path d="M9 12l2 2 4-4" /></svg>;
}
function CheckIcon() {
    return <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" /></svg>;
}
