import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Textarea } from '@/components/ui';
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

    return (
        <DashboardLayout title="Nouveau plan" subtitle="">
            <PageHeader
                title="Nouveau plan d'accompagnement"
                subtitle={`Bénéficiaire : ${b.full_name} · le plan sera créé en brouillon, à activer ensuite.`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: 'Plans', href: `/beneficiaries/${b.id}/care-plans` },
                    { label: 'Nouveau' },
                ]}
            />

            <Form action={`/beneficiaries/${b.id}/care-plans`} method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <Card className="mx-auto max-w-3xl">
                        <CardHeader title="Informations" />
                        <CardBody className="space-y-4">
                            <FormField label="Titre" htmlFor="title" required error={errors.title} help="Ex: « Aide quotidienne — semaine type »">
                                <Input id="title" name="title" required maxLength={200} invalid={!!errors.title} />
                            </FormField>

                            <FormField
                                label="Objectifs (optionnel)"
                                htmlFor="objectives"
                                error={errors.objectives}
                                help="Champ chiffré. Décrivez les objectifs d'accompagnement et la trajectoire visée."
                            >
                                <Textarea id="objectives" name="objectives" rows={6} maxLength={10000} invalid={!!errors.objectives} />
                            </FormField>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Date de début" htmlFor="start_date" required error={errors.start_date}>
                                    <Input id="start_date" name="start_date" type="date" required invalid={!!errors.start_date} />
                                </FormField>
                                <FormField label="Date de fin (optionnel)" htmlFor="end_date" error={errors.end_date}>
                                    <Input id="end_date" name="end_date" type="date" invalid={!!errors.end_date} />
                                </FormField>
                            </div>
                        </CardBody>
                        <CardFooter>
                            <Link
                                href={`/beneficiaries/${b.id}/care-plans`}
                                className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100"
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
        </DashboardLayout>
    );
}
