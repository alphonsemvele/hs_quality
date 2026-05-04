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
interface Props {
    options: {
        intervenants: Option[];
        beneficiaries: Option[];
        care_plans: CarePlanOption[];
    };
}

export default function CreateIntervention({ options }: Props) {
    return (
        <DashboardLayout title="Nouvelle intervention" subtitle="Planifier une visite à domicile">
            <PageHeader
                title="Nouvelle intervention"
                subtitle="Renseignez les informations de base. Les détails terrain (check-in / check-out) seront enregistrés depuis le mobile."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Interventions', href: '/interventions' },
                    { label: 'Nouvelle' },
                ]}
            />

            <Form action="/interventions" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Acteurs" subtitle="Qui intervient et pour qui" />
                            <CardBody className="space-y-4">
                                <FormField label="Intervenant" htmlFor="intervenant_id" required error={errors.intervenant_id}>
                                    <Select id="intervenant_id" name="intervenant_id" required defaultValue="" invalid={!!errors.intervenant_id}>
                                        <option value="" disabled>
                                            Sélectionner un intervenant
                                        </option>
                                        {options.intervenants.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField label="Bénéficiaire" htmlFor="beneficiary_id" required error={errors.beneficiary_id}>
                                    <Select id="beneficiary_id" name="beneficiary_id" required defaultValue="" invalid={!!errors.beneficiary_id}>
                                        <option value="" disabled>
                                            Sélectionner un bénéficiaire
                                        </option>
                                        {options.beneficiaries.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </Select>
                                </FormField>

                                <FormField
                                    label="Plan d'accompagnement (optionnel)"
                                    htmlFor="care_plan_id"
                                    error={errors.care_plan_id}
                                    help="Limite les tâches affichées au plan actif du bénéficiaire."
                                >
                                    <Select id="care_plan_id" name="care_plan_id" defaultValue="" invalid={!!errors.care_plan_id}>
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
                            <CardHeader title="Planification" subtitle="Date et créneau prévus" />
                            <CardBody className="space-y-4">
                                <FormField label="Date" htmlFor="planned_date" required error={errors.planned_date}>
                                    <Input id="planned_date" name="planned_date" type="date" required invalid={!!errors.planned_date} />
                                </FormField>

                                <div className="grid grid-cols-2 gap-3">
                                    <FormField label="Heure de début" htmlFor="planned_start_time" error={errors.planned_start_time}>
                                        <Input
                                            id="planned_start_time"
                                            name="planned_start_time"
                                            type="time"
                                            invalid={!!errors.planned_start_time}
                                        />
                                    </FormField>
                                    <FormField label="Heure de fin" htmlFor="planned_end_time" error={errors.planned_end_time}>
                                        <Input
                                            id="planned_end_time"
                                            name="planned_end_time"
                                            type="time"
                                            invalid={!!errors.planned_end_time}
                                        />
                                    </FormField>
                                </div>
                            </CardBody>
                            <CardFooter>
                                <Link
                                    href="/interventions"
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100"
                                >
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>
                                    Planifier
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
