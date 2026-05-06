import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Audit {
    id: string;
    titre: string;
    referentiel: string;
    description: string | null;
    date_audit: string | null;
    auditeur: string | null;
}

interface Props {
    audit: Audit;
    referentiels: { value: string; label: string }[];
}

export default function AuditEdit({ audit, referentiels = [] }: Props) {
    return (
        <DashboardLayout title="Modifier l'audit" subtitle="">
            <PageHeader
                title="Modifier l'audit"
                subtitle={audit.titre}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: audit.titre, href: `/audits/${audit.id}` },
                    { label: 'Modifier' },
                ]}
            />

            <Form action={`/audits/${audit.id}`} method="put">
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Informations de l'audit" />
                            <CardBody className="space-y-4">
                                <FormField label="Titre" htmlFor="titre" required error={errors.titre}>
                                    <Input id="titre" name="titre" required maxLength={255} defaultValue={audit.titre} invalid={!!errors.titre} />
                                </FormField>

                                <FormField label="Référentiel" htmlFor="referentiel" required error={errors.referentiel}>
                                    <Select id="referentiel" name="referentiel" required defaultValue={audit.referentiel} invalid={!!errors.referentiel}>
                                        {referentiels.map((r) => (
                                            <option key={r.value} value={r.value}>{r.label}</option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Description / périmètre" htmlFor="description" error={errors.description}>
                                    <Textarea id="description" name="description" rows={4} maxLength={5000} defaultValue={audit.description ?? ''} />
                                </FormField>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Planning" />
                            <CardBody className="space-y-4">
                                <FormField label="Date prévue" htmlFor="date_audit" error={errors.date_audit}>
                                    <Input id="date_audit" name="date_audit" type="date" defaultValue={audit.date_audit ?? ''} invalid={!!errors.date_audit} />
                                </FormField>

                                <FormField label="Auditeur" htmlFor="auditeur" error={errors.auditeur}>
                                    <Input id="auditeur" name="auditeur" maxLength={150} defaultValue={audit.auditeur ?? ''} />
                                </FormField>
                            </CardBody>
                            <CardFooter>
                                <Link href={`/audits/${audit.id}`} className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700">
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
