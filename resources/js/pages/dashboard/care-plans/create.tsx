import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Form, Link } from '@inertiajs/react';
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

export default function CarePlanCreate({ beneficiary }: { beneficiary: { data: Beneficiary } | Beneficiary }) {
    const b = unwrap<Beneficiary>(beneficiary);
    const today = new Date().toISOString().slice(0, 10);

    return (
        <DashboardLayout title="Nouveau plan" subtitle="">
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

            <div className="mx-auto grid max-w-5xl grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Form column */}
                <div className="lg:col-span-2">
                    <Form action={`/beneficiaries/${b.id}/care-plans`} method="post" resetOnSuccess>
                        {({ errors, processing }) => (
                            <Card>
                                <CardHeader title="Informations du plan" subtitle="Étape 1 sur 3" />
                                <CardBody className="space-y-4">
                                    <FormField
                                        label="Titre"
                                        htmlFor="title"
                                        required
                                        error={errors.title}
                                        help="Donnez un titre clair, lisible par toute l'équipe. Ex: « Aide quotidienne — semaine type »"
                                    >
                                        <Input
                                            id="title"
                                            name="title"
                                            required
                                            maxLength={200}
                                            invalid={!!errors.title}
                                            placeholder="Aide quotidienne — semaine type"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Objectifs (optionnel)"
                                        htmlFor="objectives"
                                        error={errors.objectives}
                                        help="Champ chiffré. Décrivez les objectifs d'accompagnement et la trajectoire visée."
                                    >
                                        <Textarea
                                            id="objectives"
                                            name="objectives"
                                            rows={5}
                                            maxLength={10000}
                                            invalid={!!errors.objectives}
                                            placeholder="Ex: Maintenir l'autonomie pour la toilette, prévenir les chutes nocturnes, surveiller la prise médicamenteuse…"
                                        />
                                    </FormField>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <FormField label="Date de début" htmlFor="start_date" required error={errors.start_date}>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                                required
                                                defaultValue={today}
                                                invalid={!!errors.start_date}
                                            />
                                        </FormField>
                                        <FormField
                                            label="Date de fin (optionnel)"
                                            htmlFor="end_date"
                                            error={errors.end_date}
                                            help="Laissez vide pour un plan sans terme."
                                        >
                                            <Input id="end_date" name="end_date" type="date" invalid={!!errors.end_date} />
                                        </FormField>
                                    </div>
                                </CardBody>
                                <CardFooter>
                                    <Link
                                        href={`/beneficiaries/${b.id}/care-plans`}
                                        className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700/60"
                                    >
                                        Annuler
                                    </Link>
                                    <Button type="submit" loading={processing}>
                                        Créer en brouillon
                                    </Button>
                                </CardFooter>
                            </Card>
                        )}
                    </Form>
                </div>

                {/* Workflow timeline */}
                <aside className="lg:col-span-1">
                    <Card>
                        <CardHeader title="Prochaines étapes" subtitle="Création du plan" />
                        <CardBody className="space-y-1">
                            <Step n={1} active title="Définir les informations" desc="Titre, objectifs et période. Le plan est créé en brouillon." />
                            <Step n={2} title="Ajouter les tâches récurrentes" desc="Aide à la toilette, prise médicamenteuse, surveillance… Les intervenants les cochent à chaque visite." />
                            <Step n={3} title="Activer le plan" desc="Une fois prêt, activez-le. Tout plan déjà actif pour ce bénéficiaire sera automatiquement archivé." />
                        </CardBody>
                    </Card>

                    <Card className="mt-4 border-brand-200 bg-brand-50/40 dark:border-brand-700/40 dark:bg-brand-900/15">
                        <CardBody className="flex gap-3">
                            <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300">
                                <LockIcon />
                            </span>
                            <div className="min-w-0">
                                <p className="text-xs font-semibold text-brand-900 dark:text-brand-200">Donnée sensible</p>
                                <p className="mt-1 text-[11px] leading-relaxed text-brand-800/80 dark:text-brand-300/80">
                                    Les objectifs et tâches d'un plan sont chiffrés au repos et l'accès est tracé (RGPD Art. 9 — données de santé).
                                </p>
                            </div>
                        </CardBody>
                    </Card>
                </aside>
            </div>
        </DashboardLayout>
    );
}

function Step({ n, title, desc, active }: { n: number; title: string; desc: string; active?: boolean }) {
    return (
        <div className="flex gap-3 rounded-lg p-2">
            <div
                className={cn(
                    'flex size-7 shrink-0 items-center justify-center rounded-full font-mono text-xs font-semibold',
                    active
                        ? 'bg-brand-600 text-white shadow-[0_0_0_4px_rgba(99,102,241,0.18)]'
                        : 'bg-ink-100 text-ink-500 dark:bg-ink-700 dark:text-ink-400',
                )}
            >
                {n}
            </div>
            <div className="min-w-0 pt-0.5">
                <p
                    className={cn(
                        'text-sm font-medium',
                        active ? 'text-ink-900 dark:text-white' : 'text-ink-700 dark:text-ink-300',
                    )}
                >
                    {title}
                </p>
                <p className="mt-0.5 text-[11px] leading-relaxed text-ink-500 dark:text-ink-400">{desc}</p>
            </div>
        </div>
    );
}

function LockIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
        </svg>
    );
}
