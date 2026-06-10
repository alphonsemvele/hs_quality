import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

export default function QvctActionPlanCreate() {
    return (
        <DashboardLayout title="Nouveau plan d'action QVCT" subtitle="Plans d'action">
            <PageHeader
                title="Nouveau plan d'action"
                subtitle="Le plan est créé en brouillon — vous pourrez ajouter les actions et le publier ensuite."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: "Plans d'action", href: '/qvct/action-plans' },
                    { label: 'Nouveau' },
                ]}
            />

            <Form action="/qvct/action-plans" method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader title="Informations générales" />
                        <CardBody className="space-y-4">
                            <FormField label="Titre" htmlFor="title" required error={errors.title}>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    maxLength={255}
                                    placeholder="Ex: Plan d'action — Surcharge secteur Nord"
                                    invalid={!!errors.title}
                                />
                            </FormField>

                            <FormField
                                label="Description (optionnel)"
                                htmlFor="description"
                                error={errors.description}
                                help="Contexte du plan : signal détecté, objectifs, périmètre."
                            >
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    maxLength={5000}
                                    placeholder="Sur quoi porte ce plan d'action ? Quels signaux il vise à traiter ?"
                                    invalid={!!errors.description}
                                />
                            </FormField>

                            <FormField
                                label="Trimestre cible (optionnel)"
                                htmlFor="target_quarter"
                                error={errors.target_quarter}
                                help="Ex: « 2026-Q2 » ou « S2 2026 »."
                            >
                                <Input
                                    id="target_quarter"
                                    name="target_quarter"
                                    maxLength={32}
                                    placeholder="2026-Q2"
                                    invalid={!!errors.target_quarter}
                                />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Link
                                href="/qvct/action-plans"
                                className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
                            >
                                Annuler
                            </Link>
                            <Button type="submit" loading={processing}>
                                Créer le brouillon
                            </Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
