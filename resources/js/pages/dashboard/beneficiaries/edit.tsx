import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
    first_name: string;
    last_name: string;
    date_of_birth: string | null;
    gender: string | null;
    address: string | null;
    postal_code: string | null;
    city: string | null;
    phone: string | null;
    email: string | null;
    gir: number | null;
    primary_doctor: string | null;
    primary_doctor_phone: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
}

export default function BeneficiaryEdit({ beneficiary }: { beneficiary: Beneficiary }) {
    return (
        <DashboardLayout title={`Modifier ${beneficiary.full_name}`} subtitle="">
            <PageHeader
                title="Modifier le bénéficiaire"
                subtitle="Les données médicales sensibles se modifient depuis le dossier."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: beneficiary.full_name, href: `/beneficiaries/${beneficiary.id}` },
                    { label: 'Modifier' },
                ]}
            />

            <Form action={`/beneficiaries/${beneficiary.id}`} method="put">
                {({ errors, processing }) => (
                    <div className="space-y-5">
                        <Card>
                            <CardHeader title="Identité" />
                            <CardBody className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <FormField label="Prénom" htmlFor="first_name" required error={errors.first_name}>
                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            required
                                            maxLength={100}
                                            defaultValue={beneficiary.first_name}
                                            invalid={!!errors.first_name}
                                        />
                                    </FormField>
                                    <FormField label="Nom" htmlFor="last_name" required error={errors.last_name}>
                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            required
                                            maxLength={100}
                                            defaultValue={beneficiary.last_name}
                                            invalid={!!errors.last_name}
                                        />
                                    </FormField>
                                    <FormField label="Date de naissance" htmlFor="date_of_birth" error={errors.date_of_birth}>
                                        <Input
                                            id="date_of_birth"
                                            name="date_of_birth"
                                            type="date"
                                            defaultValue={beneficiary.date_of_birth ?? ''}
                                            invalid={!!errors.date_of_birth}
                                        />
                                    </FormField>
                                    <FormField label="Sexe" htmlFor="gender" error={errors.gender}>
                                        <Select id="gender" name="gender" defaultValue={beneficiary.gender ?? ''} invalid={!!errors.gender}>
                                            <option value="">—</option>
                                            <option value="m">Homme</option>
                                            <option value="f">Femme</option>
                                            <option value="u">Non spécifié</option>
                                        </Select>
                                    </FormField>
                                </div>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Coordonnées" />
                            <CardBody className="space-y-4">
                                <FormField label="Adresse" htmlFor="address" error={errors.address}>
                                    <Input id="address" name="address" maxLength={500} defaultValue={beneficiary.address ?? ''} invalid={!!errors.address} />
                                </FormField>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <FormField label="Code postal" htmlFor="postal_code" error={errors.postal_code}>
                                        <Input id="postal_code" name="postal_code" maxLength={10} defaultValue={beneficiary.postal_code ?? ''} invalid={!!errors.postal_code} />
                                    </FormField>
                                    <FormField label="Ville" htmlFor="city" error={errors.city}>
                                        <Input id="city" name="city" maxLength={100} defaultValue={beneficiary.city ?? ''} invalid={!!errors.city} />
                                    </FormField>
                                    <FormField label="Téléphone" htmlFor="phone" error={errors.phone}>
                                        <Input id="phone" name="phone" type="tel" maxLength={30} defaultValue={beneficiary.phone ?? ''} invalid={!!errors.phone} />
                                    </FormField>
                                    <FormField label="Email" htmlFor="email" error={errors.email}>
                                        <Input id="email" name="email" type="email" maxLength={150} defaultValue={beneficiary.email ?? ''} invalid={!!errors.email} />
                                    </FormField>
                                </div>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Contexte" />
                            <CardBody>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <FormField label="GIR" htmlFor="gir" error={errors.gir}>
                                        <Input
                                            id="gir"
                                            name="gir"
                                            type="number"
                                            min={1}
                                            max={6}
                                            defaultValue={beneficiary.gir ?? ''}
                                            invalid={!!errors.gir}
                                        />
                                    </FormField>
                                    <FormField label="Médecin traitant" htmlFor="primary_doctor" error={errors.primary_doctor}>
                                        <Input
                                            id="primary_doctor"
                                            name="primary_doctor"
                                            maxLength={150}
                                            defaultValue={beneficiary.primary_doctor ?? ''}
                                            invalid={!!errors.primary_doctor}
                                        />
                                    </FormField>
                                    <FormField label="Téléphone médecin" htmlFor="primary_doctor_phone" error={errors.primary_doctor_phone}>
                                        <Input
                                            id="primary_doctor_phone"
                                            name="primary_doctor_phone"
                                            type="tel"
                                            maxLength={30}
                                            defaultValue={beneficiary.primary_doctor_phone ?? ''}
                                            invalid={!!errors.primary_doctor_phone}
                                        />
                                    </FormField>
                                </div>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Personne de référence" />
                            <CardBody>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <FormField label="Nom" htmlFor="emergency_contact_name" error={errors.emergency_contact_name}>
                                        <Input
                                            id="emergency_contact_name"
                                            name="emergency_contact_name"
                                            maxLength={150}
                                            defaultValue={beneficiary.emergency_contact_name ?? ''}
                                            invalid={!!errors.emergency_contact_name}
                                        />
                                    </FormField>
                                    <FormField label="Lien" htmlFor="emergency_contact_relationship" error={errors.emergency_contact_relationship}>
                                        <Input
                                            id="emergency_contact_relationship"
                                            name="emergency_contact_relationship"
                                            maxLength={50}
                                            defaultValue={beneficiary.emergency_contact_relationship ?? ''}
                                            invalid={!!errors.emergency_contact_relationship}
                                        />
                                    </FormField>
                                    <FormField label="Téléphone" htmlFor="emergency_contact_phone" error={errors.emergency_contact_phone}>
                                        <Input
                                            id="emergency_contact_phone"
                                            name="emergency_contact_phone"
                                            type="tel"
                                            maxLength={30}
                                            defaultValue={beneficiary.emergency_contact_phone ?? ''}
                                            invalid={!!errors.emergency_contact_phone}
                                        />
                                    </FormField>
                                </div>
                            </CardBody>
                            <CardFooter>
                                <Link
                                    href={`/beneficiaries/${beneficiary.id}`}
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
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
