import { Badge, Button, FormField, Input, PageHeader, Select, Textarea, Wizard, type WizardStep } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Referentiel {
    value: string;
    label: string;
}

interface Props {
    referentiels: Referentiel[];
}

const REFERENTIEL_META: Record<string, { description: string; items_estimes: number; tone: 'brand' | 'sage' | 'warning' }> = {
    has: {
        description: 'Haute Autorité de Santé — référentiel de certification des établissements médico-sociaux.',
        items_estimes: 86,
        tone: 'sage',
    },
    iso_9001: {
        description: 'ISO 9001 — management de la qualité, processus et amélioration continue.',
        items_estimes: 52,
        tone: 'brand',
    },
    afnor_nf_x50_056: {
        description: 'AFNOR NF X50-056 — qualité des services aux personnes à domicile (SAP).',
        items_estimes: 64,
        tone: 'warning',
    },
};

const STEPS: WizardStep[] = [
    { id: 'referentiel', label: 'Référentiel', description: 'Quel cadre de conformité allez-vous évaluer ?' },
    { id: 'info', label: 'Périmètre', description: 'Donnez un titre clair et précisez le périmètre.' },
    { id: 'planning', label: 'Planning', description: 'Date prévue et auditeur·rice responsable.' },
    { id: 'review', label: 'Récap', description: 'Vérifiez et démarrez l\'audit.' },
];

const today = (): string => new Date().toISOString().slice(0, 10);

export default function AuditCreate({ referentiels = [] }: Partial<Props>) {
    const [step, setStep] = useState(0);
    const { data, setData, post, processing, errors } = useForm({
        referentiel: '',
        titre: '',
        description: '',
        date_audit: today(),
        auditeur: '',
    });

    const canNext =
        (step === 0 && data.referentiel !== '') ||
        (step === 1 && data.titre.trim().length > 2) ||
        (step === 2 && data.date_audit !== '') ||
        step === 3;

    const goNext = () => setStep((s) => Math.min(STEPS.length - 1, s + 1));
    const goPrevious = () => setStep((s) => Math.max(0, s - 1));

    const submit = () => {
        post('/audits');
    };

    const selectedRef = referentiels.find((r) => r.value === data.referentiel);
    const meta = data.referentiel ? REFERENTIEL_META[data.referentiel] : null;

    return (
        <DashboardLayout title="Nouvel audit" subtitle="Configuration en 4 étapes">
            <PageHeader
                title="Planifier un audit"
                subtitle="Configurez le référentiel, le périmètre et la date — l'audit sera créé en brouillon."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Nouvel audit' },
                ]}
            />

            <Wizard
                steps={STEPS}
                currentStep={step}
                onStepChange={(i) => (i < step ? setStep(i) : undefined)}
                onCancel={() => window.history.back()}
                onPrevious={goPrevious}
                onNext={goNext}
                onFinish={submit}
                nextDisabled={!canNext}
                nextLoading={step === 3 && processing}
                finishLabel="Démarrer l'audit"
            >
                {step === 0 && (
                    <ReferentielStep
                        referentiels={referentiels}
                        value={data.referentiel}
                        onChange={(v) => setData('referentiel', v)}
                        error={errors.referentiel}
                    />
                )}
                {step === 1 && (
                    <InfoStep
                        titre={data.titre}
                        description={data.description}
                        onTitre={(v) => setData('titre', v)}
                        onDescription={(v) => setData('description', v)}
                        errors={errors}
                    />
                )}
                {step === 2 && (
                    <PlanningStep
                        date={data.date_audit}
                        auditeur={data.auditeur}
                        onDate={(v) => setData('date_audit', v)}
                        onAuditeur={(v) => setData('auditeur', v)}
                        errors={errors}
                    />
                )}
                {step === 3 && (
                    <ReviewStep
                        referentielLabel={selectedRef?.label ?? '?'}
                        itemsEstimes={meta?.items_estimes ?? 0}
                        titre={data.titre}
                        description={data.description}
                        date={data.date_audit}
                        auditeur={data.auditeur}
                    />
                )}
            </Wizard>
        </DashboardLayout>
    );
}

function ReferentielStep({
    referentiels,
    value,
    onChange,
    error,
}: {
    referentiels: Referentiel[];
    value: string;
    onChange: (v: string) => void;
    error?: string;
}) {
    return (
        <div className="space-y-3">
            {referentiels.map((r) => {
                const meta = REFERENTIEL_META[r.value];
                const active = value === r.value;
                return (
                    <button
                        key={r.value}
                        type="button"
                        onClick={() => onChange(r.value)}
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
                                <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{r.label}</h3>
                                {meta && (
                                    <Badge tone={meta.tone} size="xs">
                                        ~{meta.items_estimes} critères
                                    </Badge>
                                )}
                            </div>
                            {meta && <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">{meta.description}</p>}
                        </div>
                    </button>
                );
            })}
            {error && (
                <p className="text-xs text-danger-600 dark:text-danger-400" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

function InfoStep({
    titre,
    description,
    onTitre,
    onDescription,
    errors,
}: {
    titre: string;
    description: string;
    onTitre: (v: string) => void;
    onDescription: (v: string) => void;
    errors: Record<string, string>;
}) {
    return (
        <div className="space-y-4">
            <FormField label="Titre de l'audit" htmlFor="audit-titre" required error={errors.titre} help="Soyez explicite — ex: « Audit HAS annuel 2026 », « Conformité ISO secteur Nord »">
                <Input
                    id="audit-titre"
                    value={titre}
                    onChange={(e) => onTitre(e.target.value)}
                    maxLength={255}
                    required
                    invalid={!!errors.titre}
                    placeholder="Audit HAS annuel 2026"
                />
            </FormField>

            <FormField label="Périmètre / description (optionnel)" htmlFor="audit-desc" error={errors.description}>
                <Textarea
                    id="audit-desc"
                    value={description}
                    onChange={(e) => onDescription(e.target.value)}
                    rows={4}
                    maxLength={2000}
                    invalid={!!errors.description}
                    placeholder="Secteurs concernés, équipes auditées, exclusions éventuelles…"
                />
            </FormField>
        </div>
    );
}

function PlanningStep({
    date,
    auditeur,
    onDate,
    onAuditeur,
    errors,
}: {
    date: string;
    auditeur: string;
    onDate: (v: string) => void;
    onAuditeur: (v: string) => void;
    errors: Record<string, string>;
}) {
    return (
        <div className="space-y-4">
            <FormField label="Date prévue" htmlFor="audit-date" required error={errors.date_audit}>
                <Input
                    id="audit-date"
                    type="date"
                    value={date}
                    onChange={(e) => onDate(e.target.value)}
                    required
                    invalid={!!errors.date_audit}
                />
            </FormField>

            <FormField label="Auditeur·rice (optionnel)" htmlFor="audit-auditeur" error={errors.auditeur} help="Personne responsable de la conduite de l'audit.">
                <Input
                    id="audit-auditeur"
                    value={auditeur}
                    onChange={(e) => onAuditeur(e.target.value)}
                    maxLength={150}
                    invalid={!!errors.auditeur}
                    placeholder="Claire Bernard — Référente qualité"
                />
            </FormField>

            <p className="rounded-lg border border-brand-200 bg-brand-50/40 px-3 py-2 text-[11px] text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                💡 La date prévue est indicative. L'audit reste consultable et modifiable en brouillon jusqu'à sa
                finalisation, qui horodate les résultats.
            </p>
        </div>
    );
}

function ReviewStep({
    referentielLabel,
    itemsEstimes,
    titre,
    description,
    date,
    auditeur,
}: {
    referentielLabel: string;
    itemsEstimes: number;
    titre: string;
    description: string;
    date: string;
    auditeur: string;
}) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <ReviewRow label="Référentiel" value={referentielLabel} hint={`~${itemsEstimes} critères seront chargés`} />
                <ReviewRow label="Date prévue" value={date ? new Date(date).toLocaleDateString('fr-FR', { dateStyle: 'long' }) : '—'} />
                <ReviewRow label="Titre" value={titre || '—'} fullWidth />
                <ReviewRow label="Auditeur·rice" value={auditeur || 'Non renseigné·e'} />
                <ReviewRow label="Statut initial" value={<Badge tone="warning" size="sm" dot>Brouillon</Badge>} />
                {description && <ReviewRow label="Périmètre" value={description} fullWidth />}
            </div>

            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 text-xs dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="font-semibold text-sage-900 dark:text-sage-100">Ce qui se passe ensuite</p>
                <ul className="mt-2 space-y-1 text-sage-900/80 dark:text-sage-200/80">
                    <li>• L'audit est créé en brouillon avec la grille du référentiel pré-chargée</li>
                    <li>• Vous pourrez saisir les écarts au fil de votre visite terrain</li>
                    <li>• La finalisation calcule le score et génère le PDF horodaté en arrière-plan</li>
                </ul>
            </div>
        </div>
    );
}

function ReviewRow({ label, value, hint, fullWidth }: { label: string; value: React.ReactNode; hint?: string; fullWidth?: boolean }) {
    return (
        <div className={cn('rounded-xl border border-ink-100 bg-ink-50/40 p-3 dark:border-ink-700/60 dark:bg-ink-900/30', fullWidth && 'sm:col-span-2')}>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
            <p className="mt-1 text-sm text-ink-900 dark:text-white">{value}</p>
            {hint && <p className="mt-0.5 text-[11px] text-ink-400 dark:text-ink-500">{hint}</p>}
        </div>
    );
}
