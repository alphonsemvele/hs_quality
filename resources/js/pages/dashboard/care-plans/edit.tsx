import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Plan {
    id: string;
    title: string;
    objectives: string | null;
    start_date: string | null;
    end_date: string | null;
}

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

interface Props {
    plan: { data: Plan } | Plan;
    beneficiary: { data: Beneficiary } | Beneficiary;
}

export default function CarePlanEdit({ plan, beneficiary }: Props) {
    const p = unwrap<Plan>(plan);
    const b = unwrap<Beneficiary>(beneficiary);

    return (
        <DashboardLayout title={`Modifier ${p.title}`} subtitle="">
            <PageHeader
                title="Modifier le plan"
                subtitle={`Bénéficiaire : ${b.full_name}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: p.title, href: `/care-plans/${p.id}` },
                    { label: 'Modifier' },
                ]}
            />

            <Form action={`/care-plans/${p.id}`} method="put">
                {({ errors, processing }) => (
                    <Card className="mx-auto max-w-3xl">
                        <CardHeader title="Informations" />
                        <CardBody className="space-y-4">
                            <FormField label="Titre" htmlFor="title" required error={errors.title}>
                                <Input id="title" name="title" required maxLength={200} defaultValue={p.title} invalid={!!errors.title} />
                            </FormField>
                            <FormField label="Objectifs" htmlFor="objectives" error={errors.objectives} help="Champ chiffré">
                                <Textarea
                                    id="objectives"
                                    name="objectives"
                                    rows={6}
                                    maxLength={10000}
                                    defaultValue={p.objectives ?? ''}
                                    invalid={!!errors.objectives}
                                />
                            </FormField>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Date de début" htmlFor="start_date" required error={errors.start_date}>
                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        required
                                        defaultValue={p.start_date ?? ''}
                                        invalid={!!errors.start_date}
                                    />
                                </FormField>
                                <FormField label="Date de fin" htmlFor="end_date" error={errors.end_date}>
                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                        defaultValue={p.end_date ?? ''}
                                        invalid={!!errors.end_date}
                                    />
                                </FormField>
                            </div>
                        </CardBody>
                        <CardFooter>
                            <Link
                                href={`/care-plans/${p.id}`}
                                className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100"
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
