import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

export default function BeneficiaryCreate() {
    return (
        <DashboardLayout title="Nouveau bénéficiaire" subtitle="">
            <PageHeader
                title="Nouveau bénéficiaire"
                subtitle="Création d'un dossier. Les informations médicales sensibles seront ajoutées depuis le dossier après création."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: 'Nouveau' },
                ]}
            />

            <Form action="/beneficiaries" method="post" resetOnSuccess>
                {({ errors, processing }) => (
                    <div className="space-y-5">
                        <Card>
                            <CardHeader title="Identité" />
                            <CardBody className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <FormField label="Prénom" htmlFor="first_name" required error={errors.first_name}>
                                        <Input id="first_name" name="first_name" required maxLength={100} invalid={!!errors.first_name} />
                                    </FormField>
                                    <FormField label="Nom" htmlFor="last_name" required error={errors.last_name}>
                                        <Input id="last_name" name="last_name" required maxLength={100} invalid={!!errors.last_name} />
                                    </FormField>
                                    <FormField label="Date de naissance" htmlFor="date_of_birth" error={errors.date_of_birth}>
                                        <Input id="date_of_birth" name="date_of_birth" type="date" invalid={!!errors.date_of_birth} />
                                    </FormField>
                                    <FormField label="Sexe" htmlFor="gender" error={errors.gender}>
                                        <Select id="gender" name="gender" defaultValue="" invalid={!!errors.gender}>
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
                                    <Input id="address" name="address" maxLength={500} invalid={!!errors.address} />
                                </FormField>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <FormField label="Code postal" htmlFor="postal_code" error={errors.postal_code}>
                                        <Input id="postal_code" name="postal_code" maxLength={10} invalid={!!errors.postal_code} />
                                    </FormField>
                                    <FormField label="Ville" htmlFor="city" error={errors.city}>
                                        <Input id="city" name="city" maxLength={100} invalid={!!errors.city} />
                                    </FormField>
                                    <FormField label="Téléphone" htmlFor="phone" error={errors.phone}>
                                        <Input id="phone" name="phone" type="tel" maxLength={30} invalid={!!errors.phone} />
                                    </FormField>
                                    <FormField label="Email" htmlFor="email" error={errors.email}>
                                        <Input id="email" name="email" type="email" maxLength={150} invalid={!!errors.email} />
                                    </FormField>
                                </div>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader title="Contexte médical" subtitle="Informations administratives — pas de données médicales" />
                            <CardBody>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <FormField label="GIR (1 à 6)" htmlFor="gir" error={errors.gir}>
                                        <Input id="gir" name="gir" type="number" min={1} max={6} invalid={!!errors.gir} />
                                    </FormField>
                                    <FormField label="Médecin traitant" htmlFor="primary_doctor" error={errors.primary_doctor}>
                                        <Input id="primary_doctor" name="primary_doctor" maxLength={150} invalid={!!errors.primary_doctor} />
                                    </FormField>
                                    <FormField label="Téléphone médecin" htmlFor="primary_doctor_phone" error={errors.primary_doctor_phone}>
                                        <Input
                                            id="primary_doctor_phone"
                                            name="primary_doctor_phone"
                                            type="tel"
                                            maxLength={30}
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
                                        <Input id="emergency_contact_name" name="emergency_contact_name" maxLength={150} invalid={!!errors.emergency_contact_name} />
                                    </FormField>
                                    <FormField label="Lien" htmlFor="emergency_contact_relationship" error={errors.emergency_contact_relationship}>
                                        <Input id="emergency_contact_relationship" name="emergency_contact_relationship" maxLength={50} invalid={!!errors.emergency_contact_relationship} />
                                    </FormField>
                                    <FormField label="Téléphone" htmlFor="emergency_contact_phone" error={errors.emergency_contact_phone}>
                                        <Input id="emergency_contact_phone" name="emergency_contact_phone" type="tel" maxLength={30} invalid={!!errors.emergency_contact_phone} />
                                    </FormField>
                                </div>
                            </CardBody>
                            <CardFooter>
                                <Link
                                    href="/beneficiaries"
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
                                >
                                    Annuler
                                </Link>
                                <Button type="submit" loading={processing}>
                                    Créer le bénéficiaire
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                )}
            </Form>
        </DashboardLayout>
    );
}
