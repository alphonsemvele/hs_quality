import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Rating {
    id: string;
    score: number;
    comment: string | null;
    rated_at: string;
    rated_by: string;
    created_at: string;
}

interface BeneficiaryHeader {
    id: string;
    full_name: string;
    initials: string;
}

interface Stats {
    count: number;
    average: number | null;
    last_rated_at: string | null;
}

interface Props {
    beneficiary: BeneficiaryHeader;
    ratings: Rating[];
    stats: Stats;
    can_record: boolean;
}

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    } catch {
        return iso;
    }
}

function todayIso(): string {
    return new Date().toISOString().slice(0, 10);
}

const SCORE_TONE: Record<number, 'danger' | 'warning' | 'sage'> = {
    1: 'danger',
    2: 'danger',
    3: 'warning',
    4: 'sage',
    5: 'sage',
};

const SCORE_LABEL: Record<number, string> = {
    1: 'Très insatisfait',
    2: 'Insatisfait',
    3: 'Neutre',
    4: 'Satisfait',
    5: 'Très satisfait',
};

export default function BeneficiarySatisfaction({ beneficiary, ratings, stats, can_record }: Props) {
    const [composing, setComposing] = useState(false);

    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        score: 4,
        comment: '',
        rated_at: todayIso(),
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/beneficiaries/${beneficiary.id}/satisfaction`, {
            preserveScroll: true,
            onSuccess: () => {
                reset('comment');
                setComposing(false);
            },
        });
    };

    return (
        <DashboardLayout
            title={`Satisfaction · ${beneficiary.full_name}`}
            subtitle="Historique des évaluations de satisfaction du bénéficiaire"
        >
            <PageHeader
                title={`Satisfaction de ${beneficiary.full_name}`}
                subtitle="Enquêtes ponctuelles capturées par les coordinateurs et les RH. Le portail bénéficiaires Phase 3 alimentera également ce flux."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: beneficiary.full_name, href: `/beneficiaries/${beneficiary.id}` },
                    { label: 'Satisfaction' },
                ]}
                actions={
                    can_record && !composing ? (
                        <Button onClick={() => setComposing(true)}>Enregistrer une évaluation</Button>
                    ) : (
                        <Link
                            href={`/beneficiaries/${beneficiary.id}`}
                            className="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                        >
                            ← Retour à la fiche
                        </Link>
                    )
                }
            />

            {/* Stats row */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Note moyenne"
                    value={stats.average !== null ? `${stats.average} / 5` : '—'}
                    sub={stats.count > 0 ? `${stats.count} évaluation${stats.count > 1 ? 's' : ''}` : 'Aucune donnée'}
                    icon={<StarIcon />}
                    tone={stats.average !== null && stats.average < 3 ? 'warning' : 'sage'}
                    hint="Moyenne arithmétique des scores enregistrés (1 à 5). Une chute en dessous de 3,5 mérite un échange avec le bénéficiaire et sa famille."
                />
                <KpiCard
                    label="Évaluations totales"
                    value={stats.count}
                    sub="Toutes sources confondues"
                    icon={<ListIcon />}
                    tone="brand"
                    hint="Enquêtes ponctuelles capturées en présentiel + futures auto-évaluations via le portail bénéficiaires (Phase 3)."
                />
                <KpiCard
                    label="Dernière évaluation"
                    value={stats.last_rated_at ? formatDate(stats.last_rated_at) : '—'}
                    sub={stats.last_rated_at ? 'Date la plus récente' : 'Aucun historique'}
                    icon={<CalendarIcon />}
                    tone="neutral"
                    hint="Cadence cible : au moins 1 évaluation par trimestre pour les bénéficiaires en accompagnement régulier."
                />
            </div>

            {recentlySuccessful && (
                <div className="mt-4 rounded-2xl border border-sage-200 bg-sage-50 px-4 py-3 text-sm text-sage-700 dark:border-sage-700/50 dark:bg-sage-900/30 dark:text-sage-300">
                    Évaluation enregistrée avec succès.
                </div>
            )}

            {/* Form */}
            {composing && (
                <Card className="mt-6">
                    <CardHeader>
                        <h3 className="text-base font-semibold text-ink-900 dark:text-white">Nouvelle évaluation</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Le commentaire est chiffré au repos. Seuls la note et la date apparaissent dans le journal d'audit.
                        </p>
                    </CardHeader>
                    <CardBody>
                        <form onSubmit={submit} className="space-y-5">
                            {/* Score selector */}
                            <div>
                                <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                                    Note <span className="text-danger-500">*</span>
                                </span>
                                <div className="mt-2 grid grid-cols-5 gap-2">
                                    {[1, 2, 3, 4, 5].map((value) => {
                                        const selected = data.score === value;
                                        return (
                                            <button
                                                type="button"
                                                key={value}
                                                onClick={() => setData('score', value)}
                                                className={
                                                    'flex flex-col items-center gap-1 rounded-xl border px-3 py-3 text-xs font-medium transition-all ' +
                                                    (selected
                                                        ? 'border-brand-400 bg-brand-50 text-brand-800 ring-2 ring-brand-200 dark:border-brand-500 dark:bg-brand-900/30 dark:text-brand-200'
                                                        : 'border-ink-200 bg-white text-ink-600 hover:border-brand-300 hover:bg-brand-50/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300')
                                                }
                                            >
                                                <span className="font-mono text-lg font-bold">{value}</span>
                                                <span>{SCORE_LABEL[value]}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                                {errors.score && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.score}</p>}
                            </div>

                            <label className="block">
                                <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                                    Date d'évaluation <span className="text-danger-500">*</span>
                                </span>
                                <input
                                    type="date"
                                    value={data.rated_at}
                                    onChange={(e) => setData('rated_at', e.target.value)}
                                    max={todayIso()}
                                    required
                                    className="mt-1 block w-full max-w-xs rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                                />
                                {errors.rated_at && (
                                    <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.rated_at}</p>
                                )}
                            </label>

                            <label className="block">
                                <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                                    Commentaire (optionnel)
                                </span>
                                <textarea
                                    value={data.comment}
                                    onChange={(e) => setData('comment', e.target.value)}
                                    rows={4}
                                    maxLength={2000}
                                    placeholder="Verbatim du bénéficiaire ou observation du coordinateur…"
                                    className="mt-1 block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                                />
                                {errors.comment && (
                                    <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.comment}</p>
                                )}
                            </label>

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="secondary" onClick={() => setComposing(false)} disabled={processing}>
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Enregistrement…' : "Enregistrer l'évaluation"}
                                </Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            )}

            {/* History */}
            <div className="mt-6">
                <Card>
                    <CardHeader>
                        <h3 className="text-base font-semibold text-ink-900 dark:text-white">Historique</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            60 évaluations les plus récentes, ordre antichronologique.
                        </p>
                    </CardHeader>
                    <CardBody className="p-0">
                        {ratings.length === 0 ? (
                            <EmptyState
                                title="Aucune évaluation"
                                description={
                                    can_record
                                        ? 'Ce bénéficiaire n\'a encore aucune évaluation. Enregistrez la première pour démarrer le suivi.'
                                        : 'Aucune évaluation enregistrée pour ce bénéficiaire.'
                                }
                                action={can_record ? <Button onClick={() => setComposing(true)}>Enregistrer une évaluation</Button> : undefined}
                            />
                        ) : (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {ratings.map((r) => (
                                    <li key={r.id} className="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-start sm:gap-4">
                                        <div className="flex w-24 shrink-0 flex-col items-center justify-center rounded-xl bg-ink-50/80 px-2 py-3 dark:bg-ink-700/60">
                                            <span className="font-mono text-2xl font-bold text-ink-900 dark:text-white">{r.score}</span>
                                            <span className="text-[10px] uppercase tracking-wider text-ink-500 dark:text-ink-400">sur 5</span>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge tone={SCORE_TONE[r.score]} size="sm" dot>
                                                    {SCORE_LABEL[r.score]}
                                                </Badge>
                                                <span className="text-xs font-medium text-ink-500 dark:text-ink-400">
                                                    {formatDate(r.rated_at)} · {r.rated_by}
                                                </span>
                                            </div>
                                            {r.comment ? (
                                                <p className="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-200">
                                                    {r.comment}
                                                </p>
                                            ) : (
                                                <p className="mt-2 text-sm italic text-ink-400 dark:text-ink-500">
                                                    Aucun commentaire enregistré.
                                                </p>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function StarIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function ListIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <line x1="8" y1="6" x2="21" y2="6" />
            <line x1="8" y1="12" x2="21" y2="12" />
            <line x1="8" y1="18" x2="21" y2="18" />
            <line x1="3" y1="6" x2="3.01" y2="6" />
            <line x1="3" y1="12" x2="3.01" y2="12" />
            <line x1="3" y1="18" x2="3.01" y2="18" />
        </svg>
    );
}
function CalendarIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
    );
}
