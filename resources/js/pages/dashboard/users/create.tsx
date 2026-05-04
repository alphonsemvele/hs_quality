import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Props {
    roles: { value: string; label: string }[];
}

export default function CreateUser({ roles }: Props) {
    return (
        <DashboardLayout title="Inviter un utilisateur" subtitle="">
            <PageHeader
                title="Inviter un utilisateur"
                subtitle="Un email d'activation sera envoyé. Le membre choisira son mot de passe puis configurera sa 2FA si son rôle l'exige."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Utilisateurs', href: '/users' },
                    { label: 'Nouvelle invitation' },
                ]}
            />

            <Form action="/users" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <Card className="mx-auto max-w-2xl">
                        <CardHeader title="Informations" />
                        <CardBody className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Prénom" htmlFor="first_name" required error={errors.first_name}>
                                    <Input id="first_name" name="first_name" required maxLength={100} invalid={!!errors.first_name} />
                                </FormField>
                                <FormField label="Nom" htmlFor="last_name" required error={errors.last_name}>
                                    <Input id="last_name" name="last_name" required maxLength={100} invalid={!!errors.last_name} />
                                </FormField>
                            </div>

                            <FormField label="Email" htmlFor="email" required error={errors.email}>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    maxLength={255}
                                    invalid={!!errors.email}
                                />
                            </FormField>

                            <FormField
                                label="Rôle"
                                htmlFor="type"
                                required
                                error={errors.type}
                                help="Le rôle détermine les permissions et les obligations 2FA."
                            >
                                <Select id="type" name="type" required defaultValue="" invalid={!!errors.type}>
                                    <option value="" disabled>
                                        Sélectionner un rôle
                                    </option>
                                    {roles.map((r) => (
                                        <option key={r.value} value={r.value}>
                                            {r.label}
                                        </option>
                                    ))}
                                </Select>
                            </FormField>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField label="Téléphone (optionnel)" htmlFor="phone" error={errors.phone}>
                                    <Input id="phone" name="phone" type="tel" maxLength={50} invalid={!!errors.phone} />
                                </FormField>
                                <FormField
                                    label="N° employé (optionnel)"
                                    htmlFor="employee_number"
                                    error={errors.employee_number}
                                >
                                    <Input
                                        id="employee_number"
                                        name="employee_number"
                                        maxLength={50}
                                        invalid={!!errors.employee_number}
                                    />
                                </FormField>
                            </div>
                        </CardBody>
                        <CardFooter>
                            <Link
                                href="/users"
                                className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100"
                            >
                                Annuler
                            </Link>
                            <Button type="submit" loading={processing}>
                                Envoyer l'invitation
                            </Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
