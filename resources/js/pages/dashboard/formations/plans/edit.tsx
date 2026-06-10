import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Plan {
    id: string;
    year: number;
    theme: string;
    target_audience: string;
    status: 'draft' | 'published' | 'archived';
    status_label: string;
}

const STATUS_TONE: Record<Plan['status'], 'sage' | 'warning' | 'brand'> = {
    draft: 'warning',
    published: 'sage',
    archived: 'brand',
};

export default function TrainingPlanEdit({ plan }: { plan: Plan }) {
    return (
        <DashboardLayout title={`Modifier — ${plan.theme}`} subtitle="Formations & compétences">
            <PageHeader
                title="Modifier le plan de formation"
                subtitle={`Année ${plan.year} · ${plan.theme}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Formations', href: '/formations' },
                    { label: plan.theme, href: '/formations' },
                    { label: 'Modifier' },
                ]}
                actions={
                    <Badge tone={STATUS_TONE[plan.status]} size="sm" dot>
                        {plan.status_label}
                    </Badge>
                }
            />

            <Form action={`/formations/${plan.id}`} method="put">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader title="Informations générales" />
                        <CardBody className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Année" htmlFor="year" required error={errors.year}>
                                    <Input
                                        id="year"
                                        name="year"
                                        type="number"
                                        min={2020}
                                        max={2099}
                                        required
                                        defaultValue={plan.year}
                                        invalid={!!errors.year}
                                    />
                                </FormField>
                                <FormField label="Thème" htmlFor="theme" required error={errors.theme}>
                                    <Input id="theme" name="theme" required maxLength={200} defaultValue={plan.theme} invalid={!!errors.theme} />
                                </FormField>
                            </div>
                            <FormField label="Public cible (optionnel)" htmlFor="target_audience" error={errors.target_audience}>
                                <Input
                                    id="target_audience"
                                    name="target_audience"
                                    maxLength={2000}
                                    defaultValue={plan.target_audience}
                                    invalid={!!errors.target_audience}
                                />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Link
                                href="/formations"
                                className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
                            >
                                Annuler
                            </Link>
                            <Button type="submit" loading={processing}>
                                Enregistrer
                            </Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
