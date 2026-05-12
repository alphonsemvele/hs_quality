import { AnonymityBanner } from '@/components/qvct';
import { Badge, Button, EmptyState, FormField, Input, PageHeader, Wizard, type WizardStep } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface QuestionnaireOption {
    id: string;
    title: string;
    frequency: string | null;
    question_count: number;
    campaigns_count: number;
}

interface TeamOption {
    value: string;
    label: string;
    members: number;
}

interface Props {
    questionnaires: QuestionnaireOption[];
    teams: TeamOption[];
}

const STEPS: WizardStep[] = [
    { id: 'questionnaire', label: 'Questionnaire', description: 'Choisissez le questionnaire à diffuser.' },
    { id: 'audience', label: 'Audience', description: "Qui doit répondre à cette campagne ?" },
    { id: 'schedule', label: 'Période', description: "Fenêtre d'ouverture et rappels." },
    { id: 'review', label: 'Récap', description: "Vérifiez et lancez la campagne." },
];

const today = (): string => new Date().toISOString().slice(0, 10);
const inDays = (days: number): string => {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
};

export default function QvctCampaignCreate({ questionnaires = [], teams = [] }: Partial<Props>) {
    const [step, setStep] = useState(0);
    const [questionnaireId, setQuestionnaireId] = useState<string | null>(null);
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        target_team: '',
        opens_at: today(),
        closes_at: inDays(14),
    });

    const canNext =
        (step === 0 && questionnaireId !== null) ||
        step === 1 ||
        (step === 2 && data.opens_at !== '' && data.closes_at !== '' && data.closes_at >= data.opens_at) ||
        step === 3;

    const submit = () => {
        if (!questionnaireId) return;
        post(`/qvct/questionnaires/${questionnaireId}/campaigns`);
    };

    const selected = questionnaires.find((q) => q.id === questionnaireId) ?? null;
    const selectedTeam = teams.find((t) => t.value === data.target_team) ?? teams[0];

    return (
        <DashboardLayout title="Nouvelle campagne QVCT" subtitle="Configuration en 4 étapes">
            <PageHeader
                title="Lancer une campagne QVCT"
                subtitle="Choisissez un questionnaire et configurez la période de collecte des réponses anonymes."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Campagnes', href: '/qvct/campaigns' },
                    { label: 'Nouvelle' },
                ]}
            />

            <Wizard
                steps={STEPS}
                currentStep={step}
                onStepChange={(i) => (i < step ? setStep(i) : undefined)}
                onCancel={() => window.history.back()}
                onPrevious={() => setStep((s) => Math.max(0, s - 1))}
                onNext={() => setStep((s) => Math.min(STEPS.length - 1, s + 1))}
                onFinish={submit}
                nextDisabled={!canNext}
                nextLoading={step === 3 && processing}
                finishLabel="Lancer la campagne"
            >
                {step === 0 && (
                    <QuestionnaireStep
                        questionnaires={questionnaires}
                        value={questionnaireId}
                        onChange={setQuestionnaireId}
                    />
                )}
                {step === 1 && (
                    <AudienceStep
                        teams={teams}
                        title={data.title}
                        targetTeam={data.target_team}
                        onTitle={(v) => setData('title', v)}
                        onTargetTeam={(v) => setData('target_team', v)}
                        errors={errors}
                    />
                )}
                {step === 2 && (
                    <ScheduleStep
                        opensAt={data.opens_at}
                        closesAt={data.closes_at}
                        onOpensAt={(v) => setData('opens_at', v)}
                        onClosesAt={(v) => setData('closes_at', v)}
                        errors={errors}
                    />
                )}
                {step === 3 && selected && (
                    <ReviewStep
                        questionnaire={selected}
                        title={data.title || `Baromètre QVCT — ${new Date(data.opens_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}`}
                        team={selectedTeam ?? null}
                        opensAt={data.opens_at}
                        closesAt={data.closes_at}
                    />
                )}
            </Wizard>
        </DashboardLayout>
    );
}

function QuestionnaireStep({
    questionnaires,
    value,
    onChange,
}: {
    questionnaires: QuestionnaireOption[];
    value: string | null;
    onChange: (id: string) => void;
}) {
    if (questionnaires.length === 0) {
        return (
            <EmptyState
                icon={
                    <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                }
                title="Aucun questionnaire disponible"
                description="Créez d'abord un questionnaire actif pour lancer une campagne."
                action={
                    <a
                        href="/qvct/questionnaires/create"
                        className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
                    >
                        Créer un questionnaire
                    </a>
                }
            />
        );
    }
    return (
        <div className="space-y-3">
            {questionnaires.map((q) => {
                const active = value === q.id;
                return (
                    <button
                        key={q.id}
                        type="button"
                        onClick={() => onChange(q.id)}
                        className={cn(
                            'group flex w-full items-start gap-4 rounded-2xl border-2 p-4 text-left transition-all duration-150',
                            active
                                ? 'border-brand-500 bg-brand-50/40 ring-2 ring-brand-500/15 dark:border-brand-400 dark:bg-brand-900/15'
                                : 'border-ink-200 bg-white hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:hover:border-ink-600 dark:hover:bg-ink-700/40',
                        )}
                    >
                        <span
                            className={cn(
                                'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2',
                                active ? 'border-brand-500 bg-brand-500 text-white' : 'border-ink-300 dark:border-ink-600',
                            )}
                        >
                            {active && (
                                <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                                    <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            )}
                        </span>
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{q.title}</h3>
                                <Badge tone="brand" size="xs">
                                    {q.question_count} questions
                                </Badge>
                                {q.campaigns_count > 0 && (
                                    <Badge tone="neutral" size="xs">
                                        Déjà utilisé {q.campaigns_count}×
                                    </Badge>
                                )}
                            </div>
                            {q.frequency && (
                                <p className="mt-1 text-[11px] text-ink-500 dark:text-ink-400">
                                    Fréquence prévue : {q.frequency}
                                </p>
                            )}
                        </div>
                    </button>
                );
            })}
        </div>
    );
}

function AudienceStep({
    teams,
    title,
    targetTeam,
    onTitle,
    onTargetTeam,
    errors,
}: {
    teams: TeamOption[];
    title: string;
    targetTeam: string;
    onTitle: (v: string) => void;
    onTargetTeam: (v: string) => void;
    errors: Record<string, string>;
}) {
    return (
        <div className="space-y-5">
            <FormField label="Titre de la campagne (optionnel)" htmlFor="qc-title" error={errors.title} help="Sera généré automatiquement si laissé vide. Ex: « Baromètre QVCT — Juin 2026 »">
                <Input
                    id="qc-title"
                    value={title}
                    onChange={(e) => onTitle(e.target.value)}
                    maxLength={255}
                    placeholder="Baromètre QVCT — Juin 2026"
                />
            </FormField>

            <div>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    Équipe cible
                </p>
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    {teams.map((t) => {
                        const active = targetTeam === t.value;
                        return (
                            <button
                                key={t.value || 'all'}
                                type="button"
                                onClick={() => onTargetTeam(t.value)}
                                className={cn(
                                    'flex items-center justify-between gap-2 rounded-xl border-2 px-3 py-2.5 text-left transition-all',
                                    active
                                        ? 'border-brand-500 bg-brand-50/40 dark:border-brand-400 dark:bg-brand-900/15'
                                        : 'border-ink-200 bg-white hover:border-ink-300 dark:border-ink-700 dark:bg-ink-800 dark:hover:border-ink-600',
                                )}
                            >
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-ink-900 dark:text-white">{t.label}</p>
                                    {t.members > 0 && (
                                        <p className="text-[11px] text-ink-500 dark:text-ink-400">{t.members} membres</p>
                                    )}
                                </div>
                                {active && (
                                    <svg className="size-4 shrink-0 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                        <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                )}
                            </button>
                        );
                    })}
                </div>
                {errors.target_team && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.target_team}</p>}
            </div>

            <AnonymityBanner variant="callout" threshold={5} />
        </div>
    );
}

function ScheduleStep({
    opensAt,
    closesAt,
    onOpensAt,
    onClosesAt,
    errors,
}: {
    opensAt: string;
    closesAt: string;
    onOpensAt: (v: string) => void;
    onClosesAt: (v: string) => void;
    errors: Record<string, string>;
}) {
    const duration = opensAt && closesAt ? Math.ceil((new Date(closesAt).getTime() - new Date(opensAt).getTime()) / 86400000) + 1 : 0;
    const tooShort = duration > 0 && duration < 7;
    const tooLong = duration > 30;

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Ouverture" htmlFor="qc-open" required error={errors.opens_at}>
                    <Input
                        id="qc-open"
                        type="date"
                        value={opensAt}
                        onChange={(e) => onOpensAt(e.target.value)}
                        required
                        invalid={!!errors.opens_at}
                    />
                </FormField>
                <FormField label="Clôture" htmlFor="qc-close" required error={errors.closes_at}>
                    <Input
                        id="qc-close"
                        type="date"
                        value={closesAt}
                        onChange={(e) => onClosesAt(e.target.value)}
                        min={opensAt || undefined}
                        required
                        invalid={!!errors.closes_at}
                    />
                </FormField>
            </div>

            {duration > 0 && (
                <div
                    className={cn(
                        'rounded-xl border px-3 py-2.5 text-xs',
                        tooShort
                            ? 'border-warning-200 bg-warning-50/40 dark:border-warning-700/40 dark:bg-warning-900/15'
                            : tooLong
                                ? 'border-warning-200 bg-warning-50/40 dark:border-warning-700/40 dark:bg-warning-900/15'
                                : 'border-sage-200 bg-sage-50/40 dark:border-sage-700/40 dark:bg-sage-900/15',
                    )}
                >
                    <p
                        className={cn(
                            'font-semibold',
                            tooShort || tooLong ? 'text-warning-900 dark:text-warning-100' : 'text-sage-900 dark:text-sage-100',
                        )}
                    >
                        Durée : {duration} jour{duration > 1 ? 's' : ''}
                    </p>
                    <p className={cn('mt-1', tooShort || tooLong ? 'text-warning-900/80 dark:text-warning-200/80' : 'text-sage-900/80 dark:text-sage-200/80')}>
                        {tooShort && '⚠ Une durée < 7 jours risque de pénaliser le taux de participation.'}
                        {tooLong && '⚠ Une durée > 30 jours dilue l\'attention. Préférez 14-21 jours.'}
                        {!tooShort && !tooLong && '✓ Durée optimale pour un bon taux de participation.'}
                    </p>
                </div>
            )}

            <p className="rounded-lg border border-brand-200 bg-brand-50/40 px-3 py-2 text-[11px] text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                💡 Un rappel automatique sera envoyé à mi-parcours aux personnes n'ayant pas encore répondu, sans
                jamais révéler leur identité.
            </p>
        </div>
    );
}

function ReviewStep({
    questionnaire,
    title,
    team,
    opensAt,
    closesAt,
}: {
    questionnaire: QuestionnaireOption;
    title: string;
    team: TeamOption | null;
    opensAt: string;
    closesAt: string;
}) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <ReviewRow label="Titre" value={title} fullWidth />
                <ReviewRow label="Questionnaire" value={`${questionnaire.title} (${questionnaire.question_count} questions)`} />
                <ReviewRow label="Équipe cible" value={team?.label ?? 'Toute la structure'} />
                <ReviewRow label="Ouverture" value={new Date(opensAt).toLocaleDateString('fr-FR', { dateStyle: 'long' })} />
                <ReviewRow label="Clôture" value={new Date(closesAt).toLocaleDateString('fr-FR', { dateStyle: 'long' })} />
            </div>

            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 text-xs dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="font-semibold text-sage-900 dark:text-sage-100">Ce qui se passe au lancement</p>
                <ul className="mt-2 space-y-1 text-sage-900/80 dark:text-sage-200/80">
                    <li>• Notification envoyée à tous les membres de l'équipe cible (email + in-app)</li>
                    <li>• Les réponses sont collectées de façon anonyme — aucune trace d'identité</li>
                    <li>• Un rappel est envoyé à J-3 de la clôture</li>
                    <li>• La détection des signaux faibles s'exécute automatiquement à la clôture</li>
                </ul>
            </div>
        </div>
    );
}

function ReviewRow({ label, value, fullWidth }: { label: string; value: React.ReactNode; fullWidth?: boolean }) {
    return (
        <div className={cn('rounded-xl border border-ink-100 bg-ink-50/40 p-3 dark:border-ink-700/60 dark:bg-ink-900/30', fullWidth && 'sm:col-span-2')}>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
            <p className="mt-1 text-sm text-ink-900 dark:text-white">{value}</p>
        </div>
    );
}
