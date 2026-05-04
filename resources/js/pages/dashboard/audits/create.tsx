import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Props {
    referentiels: { value: string; label: string }[];
}

export default function AuditCreate({ referentiels = [] }: Partial<Props>) {
    return (
        <DashboardLayout title="Nouvel audit" subtitle="">
            <PageHeader
                title="Planifier un audit"
                subtitle="Configurez les paramètres de l'audit et la grille d'évaluation."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Nouvel audit' },
                ]}
            />

            <Form action="/audits" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Informations de l'audit" />
                            <CardBody className="space-y-4">
                                <FormField label="Titre" htmlFor="titre" required error={errors.titre}>
                                    <Input id="titre" name="titre" required maxLength={255} placeholder="Ex: Audit HAS annuel 2026" invalid={!!errors.titre} />
                                </FormField>

                                <FormField label="Référentiel" htmlFor="referentiel" required error={errors.referentiel}>
                                    <Select id="referentiel" name="referentiel" required defaultValue="" invalid={!!errors.referentiel}>
                                        <option value="" disabled>Sélectionner un référentiel</option>
                                        {referentiels.map((r) => (
                                            <option key={r.value} value={r.value}>{r.label}</option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Description / périmètre" htmlFor="description" error={errors.description}>
                                    <Textarea id="description" name="description" rows={4} maxLength={2000} placeholder="Périmètre de l'audit, services concernés…" />
                                </FormField>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Planning" />
                            <CardBody className="space-y-4">
                                <FormField label="Date prévue" htmlFor="date_audit" required error={errors.date_audit}>
                                    <Input id="date_audit" name="date_audit" type="date" required invalid={!!errors.date_audit} />
                                </FormField>

                                <FormField label="Auditeur" htmlFor="auditeur" error={errors.auditeur}>
                                    <Input id="auditeur" name="auditeur" maxLength={150} placeholder="Nom de l'auditeur" />
                                </FormField>
                            </CardBody>
                            <CardFooter>
                                <Link href="/audits" className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700">
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>Créer l'audit</Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
