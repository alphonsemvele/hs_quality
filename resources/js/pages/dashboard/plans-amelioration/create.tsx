import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Props {
    sources: { value: string; label: string }[];
}

export default function PlanAmeliorationCreate({ sources = [] }: Partial<Props>) {
    return (
        <DashboardLayout title="Nouveau plan d'amélioration" subtitle="">
            <PageHeader
                title="Nouveau plan d'amélioration"
                subtitle="Définissez l'origine de l'écart et les actions correctives à mener."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: "Plans d'amélioration", href: '/plans-amelioration' },
                    { label: 'Nouveau' },
                ]}
            />

            <Form action="/plans-amelioration" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Description du plan" />
                            <CardBody className="space-y-4">
                                <FormField label="Titre" htmlFor="titre" required error={errors.titre}>
                                    <Input id="titre" name="titre" required maxLength={255} placeholder="Ex: PAC suite audit HAS 2026" invalid={!!errors.titre} />
                                </FormField>

                                <FormField label="Source" htmlFor="source" required error={errors.source}>
                                    <Select id="source" name="source" required defaultValue="" invalid={!!errors.source}>
                                        <option value="" disabled>Sélectionner la source</option>
                                        {sources.map((s) => (
                                            <option key={s.value} value={s.value}>{s.label}</option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Constat / écart identifié" htmlFor="constat" required error={errors.constat}>
                                    <Textarea id="constat" name="constat" rows={4} required maxLength={2000} placeholder="Décrivez l'écart ou le problème identifié…" invalid={!!errors.constat} />
                                </FormField>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Planning" />
                            <CardBody className="space-y-4">
                                <FormField label="Responsable" htmlFor="responsable" error={errors.responsable}>
                                    <Input id="responsable" name="responsable" maxLength={150} placeholder="Nom du responsable" />
                                </FormField>

                                <FormField label="Échéance" htmlFor="echeance" error={errors.echeance}>
                                    <Input id="echeance" name="echeance" type="date" invalid={!!errors.echeance} />
                                </FormField>
                            </CardBody>
                            <CardFooter>
                                <Link href="/plans-amelioration" className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700">
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>Créer le plan</Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
