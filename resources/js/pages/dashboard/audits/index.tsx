import { AuditCompareModal } from '@/components/AuditCompareModal';
import { FilterPresets } from '@/components/FilterPresets';
import { AuditHoverCard } from '@/components/hover-cards';
import { Badge, Button, Card, CardBody, EmptyStateRich, KpiCard, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import DashboardLayout from '../layout';

function readFilterFromUrl(key: string): string | null {
    if (typeof window === 'undefined') return null;
    const params = new URLSearchParams(window.location.search);
    return params.get(key);
}

function writeFilterToUrl(key: string, value: string | null): void {
    if (typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    if (value === null || value === '') {
        url.searchParams.delete(key);
    } else {
        url.searchParams.set(key, value);
    }
    window.history.replaceState(null, '', url.toString());
}

interface AuditSummary {
    id: string;
    titre: string;
    referentiel: string;
    referentiel_label: string;
    date_audit: string | null;
    statut: 'planifie' | 'en_cours' | 'termine' | 'annule';
    statut_label: string;
    score: number | null;
    auditeur: string | null;
    nb_ecarts: number;
}

interface Stats {
    total: number;
    en_cours: number;
    termines: number;
    score_moyen: number | null;
}

interface Props {
    audits: AuditSummary[];
    stats: Stats;
}

const STATUT_TONE: Record<string, 'brand' | 'warning' | 'sage' | 'neutral' | 'danger'> = {
    planifie: 'brand',
    en_cours: 'warning',
    termine: 'sage',
    annule: 'neutral',
};

export default function AuditsIndex({ audits = [], stats = { total: 0, en_cours: 0, termines: 0, score_moyen: null } }: Partial<Props>) {
    const canManage = useCan('audits.manage');
    const [referentielFilter, setReferentielFilter] = useState<string | null>(() => readFilterFromUrl('referentiel'));
    const [statutFilter, setStatutFilter] = useState<string | null>(() => readFilterFromUrl('statut'));
    const [showCompare, setShowCompare] = useState(false);

    useEffect(() => writeFilterToUrl('referentiel', referentielFilter), [referentielFilter]);
    useEffect(() => writeFilterToUrl('statut', statutFilter), [statutFilter]);

    const referentielOptions = useMemo(() => {
        const seen = new Map<string, string>();
        for (const a of audits) {
            if (!seen.has(a.referentiel)) {
                seen.set(a.referentiel, a.referentiel_label || a.referentiel);
            }
        }
        return Array.from(seen.entries()).map(([value, label]) => ({ value, label }));
    }, [audits]);

    const statutOptions = useMemo(() => {
        const seen = new Map<string, string>();
        for (const a of audits) {
            if (!seen.has(a.statut)) {
                seen.set(a.statut, a.statut_label || a.statut);
            }
        }
        return Array.from(seen.entries()).map(([value, label]) => ({ value, label }));
    }, [audits]);

    const filteredAudits = useMemo(
        () =>
            audits.filter((a) => {
                if (referentielFilter && a.referentiel !== referentielFilter) return false;
                if (statutFilter && a.statut !== statutFilter) return false;
                return true;
            }),
        [audits, referentielFilter, statutFilter],
    );

    return (
        <DashboardLayout title="Audits & Conformité" subtitle="Évaluations HAS, AFNOR, ISO 9001">
            <PageHeader
                title="Audits & Conformité"
                subtitle="Grilles d'évaluation, scoring et plans d'actions correctifs"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Audits' }]}
                actions={
                    <div className="flex items-center gap-2">
                        {audits.length >= 2 && (
                            <Button variant="secondary" onClick={() => setShowCompare(true)}>
                                Comparer 2 audits
                            </Button>
                        )}
                        {canManage && (
                            <Link href="/audits/create">
                                <Button leadingIcon={<PlusIcon />}>Nouvel audit</Button>
                            </Link>
                        )}
                    </div>
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total audits" value={stats.total} tone="brand" />
                <KpiCard label="En cours" value={stats.en_cours} tone="warning" />
                <KpiCard label="Terminés" value={stats.termines} tone="sage" />
                <KpiCard
                    label="Score moyen"
                    value={stats.score_moyen !== null ? `${stats.score_moyen} %` : '—'}
                    tone="sage"
                    progress={stats.score_moyen ?? 0}
                />
            </div>

            {audits.length > 0 && (referentielOptions.length > 1 || statutOptions.length > 1) && (
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        Référentiel
                    </span>
                    <FilterPill active={referentielFilter === null} onClick={() => setReferentielFilter(null)} label={`Tous (${audits.length})`} />
                    {referentielOptions.map((opt) => (
                        <FilterPill
                            key={opt.value}
                            active={referentielFilter === opt.value}
                            onClick={() => setReferentielFilter(opt.value)}
                            label={`${opt.label} (${audits.filter((a) => a.referentiel === opt.value).length})`}
                        />
                    ))}
                    <span className="ml-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        Statut
                    </span>
                    <FilterPill active={statutFilter === null} onClick={() => setStatutFilter(null)} label="Tous" />
                    {statutOptions.map((opt) => (
                        <FilterPill
                            key={opt.value}
                            active={statutFilter === opt.value}
                            onClick={() => setStatutFilter(opt.value)}
                            label={`${opt.label} (${audits.filter((a) => a.statut === opt.value).length})`}
                        />
                    ))}
                    {(referentielFilter || statutFilter) && (
                        <button
                            type="button"
                            onClick={() => {
                                setReferentielFilter(null);
                                setStatutFilter(null);
                            }}
                            className="ml-1 text-[11px] font-medium text-brand-600 underline-offset-2 hover:underline dark:text-brand-400"
                        >
                            Réinitialiser
                        </button>
                    )}
                </div>
            )}

            {audits.length > 0 && (
                <FilterPresets
                    pageKey="audits"
                    basePath="/audits"
                    hasActiveFilter={!!referentielFilter || !!statutFilter}
                    className="mb-4"
                />
            )}

            {filteredAudits.length > 0 ? (
                <ul className="space-y-3">
                    {filteredAudits.map((audit) => (
                        <Link key={audit.id} href={`/audits/${audit.id}`}>
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardBody>
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <AuditHoverCard
                                                    title={audit.titre}
                                                    referentiel={audit.referentiel_label}
                                                    statusLabel={audit.statut_label}
                                                    statusTone={STATUT_TONE[audit.statut] === 'danger' ? 'neutral' : (STATUT_TONE[audit.statut] as 'sage' | 'warning' | 'brand' | 'neutral')}
                                                    runDate={audit.date_audit}
                                                    progress={audit.score}
                                                />
                                                <Badge tone={STATUT_TONE[audit.statut] ?? 'neutral'} size="sm" dot>
                                                    {audit.statut_label}
                                                </Badge>
                                                <Badge tone="brand" size="xs">{audit.referentiel_label}</Badge>
                                            </div>
                                            <div className="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                                {audit.date_audit && <span className="font-mono">{audit.date_audit}</span>}
                                                {audit.auditeur && <span>Auditeur : {audit.auditeur}</span>}
                                                {audit.nb_ecarts > 0 && (
                                                    <Badge tone="danger" size="xs">{audit.nb_ecarts} écart(s)</Badge>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-3">
                                            {audit.score !== null && (
                                                <span className="font-mono text-lg font-bold text-ink-900 dark:text-white">
                                                    {audit.score}%
                                                </span>
                                            )}
                                            <span className="text-sm font-medium text-brand-600 dark:text-brand-400">Voir →</span>
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        </Link>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyStateRich
                        icon={<ShieldIcon />}
                        title="Démarrer mon premier audit"
                        description="Préparez votre visite HAS sereinement. Un audit ISO ou AFNOR se configure en moins de 5 minutes via notre wizard guidé."
                        primaryAction={
                            canManage ? (
                                <Link href="/audits/create">
                                    <Button size="lg">Lancer un audit guidé →</Button>
                                </Link>
                            ) : undefined
                        }
                        suggestions={[
                            {
                                icon: '🏥',
                                title: 'Audit HAS',
                                description: '~86 critères. Référentiel principal pour les structures médico-sociales.',
                                tone: 'sage',
                                cta: canManage ? { label: 'Configurer un audit HAS', href: '/audits/create' } : undefined,
                            },
                            {
                                icon: '⚙️',
                                title: 'ISO 9001',
                                description: '~52 critères. Management de la qualité et amélioration continue.',
                                tone: 'brand',
                                cta: canManage ? { label: 'Configurer un audit ISO', href: '/audits/create' } : undefined,
                            },
                            {
                                icon: '🇫🇷',
                                title: 'AFNOR NF X50-056',
                                description: '~64 critères. Qualité des services aux personnes à domicile.',
                                tone: 'warning',
                                cta: canManage ? { label: 'Configurer un audit AFNOR', href: '/audits/create' } : undefined,
                            },
                        ]}
                    />
                </Card>
            )}

            <AuditCompareModal
                open={showCompare}
                onClose={() => setShowCompare(false)}
                audits={audits.map((a) => ({
                    id: a.id,
                    titre: a.titre,
                    referentiel_label: a.referentiel_label,
                    statut_label: a.statut_label,
                    date_audit: a.date_audit,
                    score: a.score,
                    nb_ecarts: a.nb_ecarts,
                }))}
            />
        </DashboardLayout>
    );
}

function FilterPill({ active, onClick, label }: { active: boolean; onClick: () => void; label: string }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={
                active
                    ? 'inline-flex items-center rounded-full bg-ink-900 px-3 py-1 text-[11px] font-semibold text-white dark:bg-white dark:text-ink-900'
                    : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-[11px] font-medium text-ink-700 transition-colors hover:border-ink-400 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200'
            }
        >
            {label}
        </button>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
        </svg>
    );
}
