import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Plan {
    id: string;
    year: number;
    theme: string;
}

export default function TrainingSessionCreate({ plan }: { plan: Plan }) {
    return (
        <DashboardLayout title="Nouvelle session" subtitle="Formations & compétences">
            <PageHeader
                title="Nouvelle session de formation"
                subtitle={`Plan ${plan.year} · ${plan.theme}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Formations', href: '/formations' },
                    { label: plan.theme, href: '/formations' },
                    { label: 'Nouvelle session' },
                ]}
            />

            <Form action={`/formations/plans/${plan.id}/sessions`} method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader title="Informations" subtitle="Titre, créneau, capacité et formateur." />
                        <CardBody className="space-y-4">
                            <FormField label="Titre" htmlFor="title" required error={errors.title}>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    maxLength={200}
                                    placeholder="Ex: PSC1 — Session de rappel"
                                    invalid={!!errors.title}
                                />
                            </FormField>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Début" htmlFor="starts_at" required error={errors.starts_at}>
                                    <Input id="starts_at" name="starts_at" type="datetime-local" required invalid={!!errors.starts_at} />
                                </FormField>
                                <FormField label="Fin" htmlFor="ends_at" required error={errors.ends_at}>
                                    <Input id="ends_at" name="ends_at" type="datetime-local" required invalid={!!errors.ends_at} />
                                </FormField>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Capacité" htmlFor="capacity" required error={errors.capacity}>
                                    <Input
                                        id="capacity"
                                        name="capacity"
                                        type="number"
                                        min={1}
                                        max={200}
                                        required
                                        defaultValue={10}
                                        invalid={!!errors.capacity}
                                    />
                                </FormField>
                                <FormField label="Lieu (optionnel)" htmlFor="location" error={errors.location}>
                                    <Input
                                        id="location"
                                        name="location"
                                        maxLength={200}
                                        placeholder="Ex: Croix-Rouge Paris 11e"
                                        invalid={!!errors.location}
                                    />
                                </FormField>
                            </div>

                            <FormField
                                label="Formateur — nom externe (optionnel)"
                                htmlFor="trainer_name"
                                error={errors.trainer_name}
                                help="Si l'animateur n'est pas dans votre structure, saisissez son nom ici."
                            >
                                <Input
                                    id="trainer_name"
                                    name="trainer_name"
                                    maxLength={200}
                                    placeholder="Ex: Croix-Rouge française"
                                    invalid={!!errors.trainer_name}
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
                                Créer la session
                            </Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
