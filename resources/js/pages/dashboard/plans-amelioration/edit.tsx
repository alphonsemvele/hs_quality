import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Plan {
    id: string;
    titre: string;
    source: string;
    source_label: string;
    constat: string | null;
    responsable: string | null;
    echeance: string | null;
}

interface Props {
    plan: Plan;
}

export default function PlanAmeliorationEdit({ plan }: Props) {
    return (
        <DashboardLayout title="Modifier le plan" subtitle="">
            <PageHeader
                title="Modifier le plan d'amélioration"
                subtitle={plan.titre}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: "Plans d'amélioration", href: '/plans-amelioration' },
                    { label: plan.titre, href: `/plans-amelioration/${plan.id}` },
                    { label: 'Modifier' },
                ]}
            />

            <Form action={`/plans-amelioration/${plan.id}`} method="put">
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Description du plan" subtitle={`Source : ${plan.source_label} (non modifiable)`} />
                            <CardBody className="space-y-4">
                                <FormField label="Titre" htmlFor="titre" required error={errors.titre}>
                                    <Input id="titre" name="titre" required maxLength={255} defaultValue={plan.titre} invalid={!!errors.titre} />
                                </FormField>

                                <FormField label="Constat / écart identifié" htmlFor="constat" error={errors.constat}>
                                    <Textarea id="constat" name="constat" rows={4} maxLength={5000} defaultValue={plan.constat ?? ''} invalid={!!errors.constat} />
                                </FormField>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Pilotage" />
                            <CardBody className="space-y-4">
                                <FormField label="Responsable" htmlFor="responsable" error={errors.responsable}>
                                    <Input id="responsable" name="responsable" maxLength={150} defaultValue={plan.responsable ?? ''} />
                                </FormField>

                                <FormField label="Échéance" htmlFor="echeance" error={errors.echeance}>
                                    <Input id="echeance" name="echeance" type="date" defaultValue={plan.echeance ?? ''} invalid={!!errors.echeance} />
                                </FormField>
                            </CardBody>
                            <CardFooter>
                                <Link href={`/plans-amelioration/${plan.id}`} className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700">
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>Enregistrer</Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
