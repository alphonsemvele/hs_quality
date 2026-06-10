import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Textarea, ValidationSummary } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Option {
    id: string;
    name?: string;
    label?: string;
}
interface Category {
    value: string;
    label: string;
}
interface Props {
    options: {
        beneficiaries: Option[];
        interventions: Option[];
        categories: Category[];
    };
}

export default function CreateIncident({ options }: Props) {
    return (
        <DashboardLayout title="Déclarer un incident" subtitle="">
            <PageHeader
                title="Déclarer un incident"
                subtitle="Toute déclaration est auditée. Les incidents graves ou critiques déclenchent automatiquement une notification ARS dans les 24 heures."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Incidents', href: '/incidents' },
                    { label: 'Nouvelle déclaration' },
                ]}
            />

            <Form action="/incidents" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        {Object.keys(errors).length > 0 && (
                            <div className="lg:col-span-3">
                                <ValidationSummary
                                    errors={errors as Record<string, string>}
                                    labels={{
                                        categorie: 'Catégorie',
                                        description: 'Description du fait',
                                        lieu: 'Lieu',
                                        gravite: 'Gravité',
                                        occurred_at: 'Date & heure',
                                        beneficiary_id: 'Bénéficiaire',
                                        intervention_id: 'Intervention liée',
                                    }}
                                />
                            </div>
                        )}
                        <Card className="lg:col-span-2">
                            <CardHeader title="Que s'est-il passé ?" subtitle="Soyez factuel — pas d'opinion ni de jugement." />
                            <CardBody className="space-y-4">
                                <FormField label="Catégorie" htmlFor="categorie" required error={errors.categorie}>
                                    <Select id="categorie" name="categorie" required defaultValue="" invalid={!!errors.categorie}>
                                        <option value="" disabled>
                                            Sélectionner une catégorie
                                        </option>
                                        {options.categories.map((c) => (
                                            <option key={c.value} value={c.value}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField
                                    label="Description"
                                    htmlFor="description"
                                    required
                                    error={errors.description}
                                    help="Décrivez les faits observés, le contexte et les conséquences immédiates. 5000 caractères max."
                                >
                                    <Textarea
                                        id="description"
                                        name="description"
                                        rows={6}
                                        required
                                        maxLength={5000}
                                        invalid={!!errors.description}
                                    />
                                </FormField>

                                <FormField label="Lieu (optionnel)" htmlFor="lieu" error={errors.lieu}>
                                    <Input id="lieu" name="lieu" placeholder="Domicile du bénéficiaire, salle de bain…" maxLength={255} />
                                </FormField>

                                <fieldset className="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/50 dark:bg-warning-900/20">
                                    <legend className="px-2 text-xs font-semibold uppercase tracking-wider text-warning-700 dark:text-warning-300">
                                        Conséquences
                                    </legend>
                                    <p className="mb-3 text-xs text-warning-700 dark:text-warning-300">
                                        Cocher au moins une de ces cases déclenche une classification grave ou critique automatique.
                                    </p>
                                    <div className="space-y-2">
                                        <Checkbox name="avec_blessure_physique" label="Blessure physique" />
                                        <Checkbox name="avec_hospitalisation" label="Hospitalisation" />
                                        <Checkbox name="avec_deces" label="Décès" />
                                    </div>
                                </fieldset>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Quand & contexte" />
                            <CardBody className="space-y-4">
                                <FormField label="Date & heure" htmlFor="occurred_at" required error={errors.occurred_at}>
                                    <Input
                                        id="occurred_at"
                                        name="occurred_at"
                                        type="datetime-local"
                                        required
                                        invalid={!!errors.occurred_at}
                                    />
                                </FormField>

                                <FormField label="Bénéficiaire (optionnel)" htmlFor="beneficiary_id" error={errors.beneficiary_id}>
                                    <Select id="beneficiary_id" name="beneficiary_id" defaultValue="" invalid={!!errors.beneficiary_id}>
                                        <option value="">— Aucun</option>
                                        {options.beneficiaries.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField
                                    label="Intervention liée (optionnel)"
                                    htmlFor="intervention_id"
                                    error={errors.intervention_id}
                                >
                                    <Select id="intervention_id" name="intervention_id" defaultValue="" invalid={!!errors.intervention_id}>
                                        <option value="">— Aucune</option>
                                        {options.interventions.map((i) => (
                                            <option key={i.id} value={i.id}>
                                                {i.label}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>
                            </CardBody>
                            <CardFooter>
                                <Link
                                    href="/incidents"
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
                                >
                                    Annuler
                                </Link>
                                <Button type="submit" variant="danger" loading={processing}>
                                    Déclarer
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}

function Checkbox({ name, label }: { name: string; label: string }) {
    return (
        <label className="flex items-center gap-2.5">
            <input
                type="checkbox"
                name={name}
                value="1"
                className="size-4 rounded border-ink-300 text-brand-600 focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800"
            />
            <span className="text-sm text-ink-700 dark:text-ink-300">{label}</span>
        </label>
    );
}
