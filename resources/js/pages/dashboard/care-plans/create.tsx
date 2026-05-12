import { Badge, FormField, Input, PageHeader, Textarea, Wizard, type WizardStep } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
}

function unwrap<T>(value: { data: T } | T): T {
    if (value && typeof value === 'object' && 'data' in (value as object)) {
        return (value as { data: T }).data;
    }
    return value as T;
}

const STEPS: WizardStep[] = [
    { id: 'info', label: 'Informations', description: 'Titre et contexte du plan.' },
    { id: 'objectives', label: 'Objectifs', description: 'Trajectoire d\'accompagnement.' },
    { id: 'period', label: 'Période', description: 'Dates de début et fin du plan.' },
    { id: 'review', label: 'Récap', description: 'Vérifiez et créez le plan en brouillon.' },
];

const today = (): string => new Date().toISOString().slice(0, 10);

export default function CarePlanCreate({ beneficiary }: { beneficiary: { data: Beneficiary } | Beneficiary }) {
    const b = unwrap<Beneficiary>(beneficiary);
    const [step, setStep] = useState(0);
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        objectives: '',
        start_date: today(),
        end_date: '',
    });

    const canNext =
        (step === 0 && data.title.trim().length > 2) ||
        step === 1 ||
        (step === 2 && data.start_date !== '') ||
        step === 3;

    const submit = () => {
        post(`/beneficiaries/${b.id}/care-plans`);
    };

    return (
        <DashboardLayout title="Nouveau plan" subtitle={`Bénéficiaire : ${b.full_name}`}>
            <PageHeader
                title="Nouveau plan d'accompagnement"
                subtitle={`Bénéficiaire : ${b.full_name}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: 'Plans', href: `/beneficiaries/${b.id}/care-plans` },
                    { label: 'Nouveau' },
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
                finishLabel="Créer en brouillon"
            >
                {step === 0 && (
                    <InfoStep
                        title={data.title}
                        onTitle={(v) => setData('title', v)}
                        beneficiaryName={b.full_name}
                        error={errors.title}
                    />
                )}
                {step === 1 && (
                    <ObjectivesStep
                        value={data.objectives}
                        onChange={(v) => setData('objectives', v)}
                        error={errors.objectives}
                    />
                )}
                {step === 2 && (
                    <PeriodStep
                        startDate={data.start_date}
                        endDate={data.end_date}
                        onStartDate={(v) => setData('start_date', v)}
                        onEndDate={(v) => setData('end_date', v)}
                        errors={errors}
                    />
                )}
                {step === 3 && (
                    <ReviewStep
                        title={data.title}
                        objectives={data.objectives}
                        startDate={data.start_date}
                        endDate={data.end_date}
                        beneficiaryName={b.full_name}
                    />
                )}
            </Wizard>
        </DashboardLayout>
    );
}

function InfoStep({
    title,
    onTitle,
    beneficiaryName,
    error,
}: {
    title: string;
    onTitle: (v: string) => void;
    beneficiaryName: string;
    error?: string;
}) {
    return (
        <div className="space-y-4">
            <div className="flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50/40 p-3 dark:border-brand-700/40 dark:bg-brand-900/20">
                <svg className="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <div className="text-xs text-brand-900 dark:text-brand-200">
                    Ce plan est créé pour <strong className="font-semibold">{beneficiaryName}</strong>. Vous pourrez
                    ajouter les tâches récurrentes après la création.
                </div>
            </div>

            <FormField
                label="Titre du plan"
                htmlFor="cp-title"
                required
                error={error}
                help="Soyez explicite — ex: « Aide quotidienne — semaine type », « Surveillance fin de vie »"
            >
                <Input
                    id="cp-title"
                    value={title}
                    onChange={(e) => onTitle(e.target.value)}
                    maxLength={200}
                    required
                    invalid={!!error}
                    placeholder="Aide quotidienne — semaine type"
                />
            </FormField>
        </div>
    );
}

function ObjectivesStep({
    value,
    onChange,
    error,
}: {
    value: string;
    onChange: (v: string) => void;
    error?: string;
}) {
    return (
        <div className="space-y-4">
            <FormField
                label="Objectifs d'accompagnement (optionnel)"
                htmlFor="cp-obj"
                error={error}
                help="Décrivez les objectifs concrets et mesurables. Ce champ est chiffré au repos (RGPD Art. 9)."
            >
                <Textarea
                    id="cp-obj"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    rows={7}
                    maxLength={10000}
                    invalid={!!error}
                    placeholder={'Ex:\n- Maintenir l\'autonomie pour la toilette d\'ici 3 mois\n- Prévenir les chutes nocturnes (tapis antidérapant + veilleuse)\n- Surveiller la prise médicamenteuse — Coumadine quotidienne\n- Préserver les liens sociaux (visites famille les week-ends)'}
                />
            </FormField>

            <div className="rounded-xl border border-brand-200 bg-brand-50/40 p-3 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                <p className="font-semibold text-brand-900 dark:text-brand-200">🔒 Donnée chiffrée</p>
                <p className="mt-1 text-brand-900/80 dark:text-brand-200/80">
                    Le contenu des objectifs est chiffré en base et déchiffré uniquement à l'affichage. Tout accès est
                    tracé dans le registre d'audit.
                </p>
            </div>
        </div>
    );
}

function PeriodStep({
    startDate,
    endDate,
    onStartDate,
    onEndDate,
    errors,
}: {
    startDate: string;
    endDate: string;
    onStartDate: (v: string) => void;
    onEndDate: (v: string) => void;
    errors: Record<string, string>;
}) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Date de début" htmlFor="cp-start" required error={errors.start_date}>
                    <Input
                        id="cp-start"
                        type="date"
                        value={startDate}
                        onChange={(e) => onStartDate(e.target.value)}
                        required
                        invalid={!!errors.start_date}
                    />
                </FormField>
                <FormField
                    label="Date de fin (optionnel)"
                    htmlFor="cp-end"
                    error={errors.end_date}
                    help="Laissez vide pour un plan sans terme défini."
                >
                    <Input
                        id="cp-end"
                        type="date"
                        value={endDate}
                        onChange={(e) => onEndDate(e.target.value)}
                        min={startDate || undefined}
                        invalid={!!errors.end_date}
                    />
                </FormField>
            </div>

            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-3 text-xs dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="font-semibold text-sage-900 dark:text-sage-100">💡 Conseil</p>
                <p className="mt-1 text-sage-900/80 dark:text-sage-200/80">
                    Une révision périodique du plan est recommandée — fixez une date de fin pour déclencher
                    automatiquement une alerte de revue avant échéance.
                </p>
            </div>
        </div>
    );
}

function ReviewStep({
    title,
    objectives,
    startDate,
    endDate,
    beneficiaryName,
}: {
    title: string;
    objectives: string;
    startDate: string;
    endDate: string;
    beneficiaryName: string;
}) {
    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <ReviewRow label="Bénéficiaire" value={beneficiaryName} />
                <ReviewRow label="Statut initial" value={<Badge tone="warning" size="sm" dot>Brouillon</Badge>} />
                <ReviewRow label="Titre" value={title || '—'} fullWidth />
                <ReviewRow label="Date de début" value={startDate ? new Date(startDate).toLocaleDateString('fr-FR', { dateStyle: 'long' }) : '—'} />
                <ReviewRow label="Date de fin" value={endDate ? new Date(endDate).toLocaleDateString('fr-FR', { dateStyle: 'long' }) : 'Sans terme'} />
                {objectives && (
                    <ReviewRow
                        label="Objectifs (chiffrés)"
                        value={<span className="whitespace-pre-line text-xs">{objectives}</span>}
                        fullWidth
                    />
                )}
            </div>

            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 text-xs dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="font-semibold text-sage-900 dark:text-sage-100">Ce qui se passe ensuite</p>
                <ul className="mt-2 space-y-1 text-sage-900/80 dark:text-sage-200/80">
                    <li>• Le plan est créé en <strong className="font-semibold">brouillon</strong></li>
                    <li>• Vous serez redirigé·e sur la fiche pour ajouter les tâches récurrentes</li>
                    <li>• L'activation se fait depuis la fiche — elle archive automatiquement tout plan actif</li>
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
