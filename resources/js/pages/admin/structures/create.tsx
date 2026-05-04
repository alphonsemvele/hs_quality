import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

interface EnumOption {
    value: string;
    label: string;
}
interface Props {
    enums: {
        types: EnumOption[];
        tiers: EnumOption[];
        statuses: EnumOption[];
    };
}

export default function CreateStructure({ enums }: Props) {
    return (
        <DashboardLayout title="Nouvelle structure" subtitle="">
            <PageHeader
                title="Provisionner une structure"
                subtitle="Création d'un nouveau tenant + invitation initiale du dirigeant. Un lien de réinitialisation de mot de passe sera généré et affiché à l'écran."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Structures (admin)', href: '/admin/structures' },
                    { label: 'Nouvelle' },
                ]}
            />

            <Form action="/admin/structures" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader title="Structure" subtitle="Informations principales" />
                            <CardBody className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <FormField label="Code" htmlFor="code" required error={errors.code} help="Identifiant court unique (ex: SAAD-DOUALA-01).">
                                        <Input id="code" name="code" required maxLength={50} invalid={!!errors.code} />
                                    </FormField>
                                    <FormField label="Type" htmlFor="type" required error={errors.type}>
                                        <Select id="type" name="type" required defaultValue="" invalid={!!errors.type}>
                                            <option value="" disabled>
                                                Sélectionner un type
                                            </option>
                                            {enums.types.map((t) => (
                                                <option key={t.value} value={t.value}>
                                                    {t.label}
                                                </option>
                                            ))}
                                        </Select>
                                    </FormField>
                                </div>

                                <FormField label="Raison sociale" htmlFor="name" required error={errors.name}>
                                    <Input id="name" name="name" required maxLength={255} invalid={!!errors.name} />
                                </FormField>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <FormField label="Tier (par défaut : Essentiel)" htmlFor="tier" error={errors.tier}>
                                        <Select id="tier" name="tier" defaultValue="" invalid={!!errors.tier}>
                                            <option value="">— Essentiel</option>
                                            {enums.tiers.map((t) => (
                                                <option key={t.value} value={t.value}>
                                                    {t.label}
                                                </option>
                                            ))}
                                        </Select>
                                    </FormField>
                                    <FormField
                                        label="SIRET (optionnel)"
                                        htmlFor="siret"
                                        error={errors.siret}
                                        help="14 chiffres exactement"
                                    >
                                        <Input id="siret" name="siret" pattern="\d{14}" maxLength={14} invalid={!!errors.siret} />
                                    </FormField>
                                </div>

                                <FormField label="Adresse (optionnel)" htmlFor="address" error={errors.address}>
                                    <Input id="address" name="address" maxLength={1000} invalid={!!errors.address} />
                                </FormField>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Dirigeant initial" subtitle="Premier admin de la structure" />
                            <CardBody className="space-y-4">
                                <FormField label="Prénom" htmlFor="dirigeant_first_name" required error={errors['dirigeant.first_name']}>
                                    <Input
                                        id="dirigeant_first_name"
                                        name="dirigeant[first_name]"
                                        required
                                        maxLength={100}
                                        invalid={!!errors['dirigeant.first_name']}
                                    />
                                </FormField>
                                <FormField label="Nom" htmlFor="dirigeant_last_name" required error={errors['dirigeant.last_name']}>
                                    <Input
                                        id="dirigeant_last_name"
                                        name="dirigeant[last_name]"
                                        required
                                        maxLength={100}
                                        invalid={!!errors['dirigeant.last_name']}
                                    />
                                </FormField>
                                <FormField label="Email" htmlFor="dirigeant_email" required error={errors['dirigeant.email']}>
                                    <Input
                                        id="dirigeant_email"
                                        name="dirigeant[email]"
                                        type="email"
                                        required
                                        maxLength={255}
                                        invalid={!!errors['dirigeant.email']}
                                    />
                                </FormField>
                                <FormField label="Téléphone (optionnel)" htmlFor="dirigeant_phone" error={errors['dirigeant.phone']}>
                                    <Input
                                        id="dirigeant_phone"
                                        name="dirigeant[phone]"
                                        type="tel"
                                        maxLength={50}
                                        invalid={!!errors['dirigeant.phone']}
                                    />
                                </FormField>
                            </CardBody>
                            <CardFooter>
                                <Link
                                    href="/admin/structures"
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100"
                                >
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>
                                    Provisionner
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
