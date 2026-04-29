import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Option {
    id: number | string;
    name: string;
}
interface CarePlanOption {
    id: string;
    title: string;
    beneficiary_id: string;
}
interface Intervention {
    id: string;
    intervenant_id: number;
    beneficiary_id: string;
    care_plan_id: string | null;
    planned_date: string | null;
    planned_start_time: string | null;
    planned_end_time: string | null;
    status: string;
}

interface Props {
    intervention: Intervention;
    options: {
        intervenants: Option[];
        beneficiaries: Option[];
        care_plans: CarePlanOption[];
    };
}

export default function EditIntervention({ intervention, options }: Props) {
    return (
        <DashboardLayout title="Modifier l'intervention" subtitle="">
            <PageHeader
                title="Modifier l'intervention"
                subtitle="Mettez à jour la planification. Les statuts et heures réelles ne sont pas modifiables ici (ils proviennent du terrain)."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Interventions', href: '/interventions' },
                    { label: `#${String(intervention.id).slice(0, 8)}`, href: `/interventions/${intervention.id}` },
                    { label: 'Modifier' },
                ]}
            />

            <Form action={`/interventions/${intervention.id}`} method="put">
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Acteurs" />
                            <CardBody className="space-y-4">
                                <FormField label="Intervenant" htmlFor="intervenant_id" required error={errors.intervenant_id}>
                                    <Select
                                        id="intervenant_id"
                                        name="intervenant_id"
                                        required
                                        defaultValue={intervention.intervenant_id}
                                        invalid={!!errors.intervenant_id}
                                    >
                                        {options.intervenants.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Bénéficiaire" htmlFor="beneficiary_id" required error={errors.beneficiary_id}>
                                    <Select
                                        id="beneficiary_id"
                                        name="beneficiary_id"
                                        required
                                        defaultValue={intervention.beneficiary_id}
                                        invalid={!!errors.beneficiary_id}
                                    >
                                        {options.beneficiaries.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Plan d'accompagnement" htmlFor="care_plan_id" error={errors.care_plan_id}>
                                    <Select
                                        id="care_plan_id"
                                        name="care_plan_id"
                                        defaultValue={intervention.care_plan_id ?? ''}
                                        invalid={!!errors.care_plan_id}
                                    >
                                        <option value="">— Aucun plan</option>
                                        {options.care_plans.map((cp) => (
                                            <option key={cp.id} value={cp.id}>
                                                {cp.title}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Planification" />
                            <CardBody className="space-y-4">
                                <FormField label="Date" htmlFor="planned_date" required error={errors.planned_date}>
                                    <Input
                                        id="planned_date"
                                        name="planned_date"
                                        type="date"
                                        required
                                        defaultValue={intervention.planned_date ?? ''}
                                        invalid={!!errors.planned_date}
                                    />
                                </FormField>

                                <div className="grid grid-cols-2 gap-3">
                                    <FormField label="Début" htmlFor="planned_start_time" error={errors.planned_start_time}>
                                        <Input
                                            id="planned_start_time"
                                            name="planned_start_time"
                                            type="time"
                                            defaultValue={intervention.planned_start_time ?? ''}
                                            invalid={!!errors.planned_start_time}
                                        />
                                    </FormField>
                                    <FormField label="Fin" htmlFor="planned_end_time" error={errors.planned_end_time}>
                                        <Input
                                            id="planned_end_time"
                                            name="planned_end_time"
                                            type="time"
                                            defaultValue={intervention.planned_end_time ?? ''}
                                            invalid={!!errors.planned_end_time}
                                        />
                                    </FormField>
                                </div>
                            </CardBody>
                            <CardFooter>
                                <Link
                                    href={`/interventions/${intervention.id}`}
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100"
                                >
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>
                                    Enregistrer
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
