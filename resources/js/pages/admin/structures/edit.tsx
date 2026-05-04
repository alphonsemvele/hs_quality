import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

interface EnumOption {
    value: string;
    label: string;
}

interface StructureDetail {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    tier: string;
    tier_label: string;
    status: string;
    address: string | null;
    siret: string | null;
}

interface Props {
    structure: StructureDetail;
    enums: {
        types: EnumOption[];
        tiers: EnumOption[];
        statuses: EnumOption[];
    };
}

export default function EditStructure({ structure, enums }: Props) {
    return (
        <DashboardLayout title={`Modifier ${structure.name}`} subtitle="">
            <PageHeader
                title="Modifier la structure"
                subtitle={`${structure.code} — ${structure.type_label}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Structures (admin)', href: '/admin/structures' },
                    { label: structure.name, href: `/admin/structures/${structure.id}` },
                    { label: 'Modifier' },
                ]}
            />

            <Form action={`/admin/structures/${structure.id}`} method="put">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader title="Informations de la structure" />
                        <CardBody className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Code" htmlFor="code" required error={errors.code}>
                                    <Input id="code" name="code" required maxLength={50} defaultValue={structure.code} invalid={!!errors.code} />
                                </FormField>
                                <FormField label="Type" htmlFor="type" required error={errors.type}>
                                    <Select id="type" name="type" required defaultValue={structure.type} invalid={!!errors.type}>
                                        <option value="" disabled>Selectionner</option>
                                        {enums.types.map((t) => (
                                            <option key={t.value} value={t.value}>{t.label}</option>
                                        ))}
                                    </Select>
                                </FormField>
                            </div>

                            <FormField label="Raison sociale" htmlFor="name" required error={errors.name}>
                                <Input id="name" name="name" required maxLength={255} defaultValue={structure.name} invalid={!!errors.name} />
                            </FormField>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Tier" htmlFor="tier" error={errors.tier}>
                                    <Select id="tier" name="tier" defaultValue={structure.tier} invalid={!!errors.tier}>
                                        {enums.tiers.map((t) => (
                                            <option key={t.value} value={t.value}>{t.label}</option>
                                        ))}
                                    </Select>
                                </FormField>
                                <FormField label="SIRET" htmlFor="siret" error={errors.siret} help="14 chiffres">
                                    <Input id="siret" name="siret" pattern="\d{14}" maxLength={14} defaultValue={structure.siret ?? ''} invalid={!!errors.siret} />
                                </FormField>
                            </div>

                            <FormField label="Adresse" htmlFor="address" error={errors.address}>
                                <Input id="address" name="address" maxLength={1000} defaultValue={structure.address ?? ''} invalid={!!errors.address} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Link href={`/admin/structures/${structure.id}`} className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700">
                                Annuler
                            </Link>
                            <Button type="submit" loading={processing}>Enregistrer</Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
