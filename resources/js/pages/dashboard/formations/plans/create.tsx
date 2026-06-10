import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

export default function TrainingPlanCreate() {
    const currentYear = new Date().getFullYear();

    return (
        <DashboardLayout title="Nouveau plan de formation" subtitle="Formations & compétences">
            <PageHeader
                title="Nouveau plan de formation"
                subtitle="Définissez l'année et le thème — les sessions seront ajoutées ensuite depuis la fiche du plan."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Formations', href: '/formations' },
                    { label: 'Nouveau plan' },
                ]}
            />

            <Form action="/formations" method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader title="Informations générales" subtitle="Le plan est créé en brouillon et publié dans une étape suivante." />
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
                                        defaultValue={currentYear}
                                        invalid={!!errors.year}
                                    />
                                </FormField>
                                <FormField label="Thème" htmlFor="theme" required error={errors.theme}>
                                    <Input
                                        id="theme"
                                        name="theme"
                                        required
                                        maxLength={200}
                                        placeholder="Ex: Bientraitance et prévention RPS"
                                        invalid={!!errors.theme}
                                    />
                                </FormField>
                            </div>
                            <FormField
                                label="Public cible (optionnel)"
                                htmlFor="target_audience"
                                error={errors.target_audience}
                                help="Décrivez les profils concernés — par exemple « tous intervenants », « nouveaux arrivants », « managers de secteur »."
                            >
                                <Input
                                    id="target_audience"
                                    name="target_audience"
                                    maxLength={2000}
                                    placeholder="Tous intervenants"
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
                                Créer le plan
                            </Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
