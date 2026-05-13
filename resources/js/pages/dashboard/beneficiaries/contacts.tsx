import { Badge, Button, Card, CardBody, CardHeader, PageHeader } from '@/components/ui';
import { Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface BeneficiaryContacts {
    id: string;
    full_name: string;
    initials: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    postal_code: string | null;
    city: string | null;
    primary_doctor: string | null;
    primary_doctor_phone: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
}

interface Props {
    beneficiary: BeneficiaryContacts;
    can_update: boolean;
}

export default function BeneficiaryContactsPage({ beneficiary, can_update }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        phone: beneficiary.phone ?? '',
        email: beneficiary.email ?? '',
        address: beneficiary.address ?? '',
        postal_code: beneficiary.postal_code ?? '',
        city: beneficiary.city ?? '',
        primary_doctor: beneficiary.primary_doctor ?? '',
        primary_doctor_phone: beneficiary.primary_doctor_phone ?? '',
        emergency_contact_name: beneficiary.emergency_contact_name ?? '',
        emergency_contact_phone: beneficiary.emergency_contact_phone ?? '',
        emergency_contact_relationship: beneficiary.emergency_contact_relationship ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/beneficiaries/${beneficiary.id}/contacts`, { preserveScroll: true });
    };

    return (
        <DashboardLayout
            title={`Contacts · ${beneficiary.full_name}`}
            subtitle="Coordonnées non médicales — séparées du dossier de santé"
        >
            <PageHeader
                title={`Contacts de ${beneficiary.full_name}`}
                subtitle="Téléphone, e-mail, adresse, médecin référent et personne à prévenir en cas d'urgence. Le dossier médical (allergies, traitements, antécédents) reste séparé."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: beneficiary.full_name, href: `/beneficiaries/${beneficiary.id}` },
                    { label: 'Contacts' },
                ]}
                actions={
                    <Link
                        href={`/beneficiaries/${beneficiary.id}`}
                        className="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        ← Retour à la fiche
                    </Link>
                }
            />

            <Card>
                <CardBody className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-sage-50 text-sm font-semibold text-sage-700 dark:bg-sage-900/30 dark:text-sage-300">
                            {beneficiary.initials}
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-ink-900 dark:text-white">{beneficiary.full_name}</p>
                            <p className="text-xs text-ink-500 dark:text-ink-400">
                                Page sans accès aux données de santé — aucune ligne de cette page n'est tracée par{' '}
                                <span className="font-mono">log_sensitive_read</span>.
                            </p>
                        </div>
                    </div>
                    <Badge tone="sage" size="sm">
                        Non médical
                    </Badge>
                </CardBody>
            </Card>

            {recentlySuccessful && (
                <div className="mt-4 rounded-2xl border border-sage-200 bg-sage-50 px-4 py-3 text-sm text-sage-700 dark:border-sage-700/50 dark:bg-sage-900/30 dark:text-sage-300">
                    Contacts enregistrés avec succès.
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-6">
                {/* Direct contact */}
                <Card>
                    <CardHeader>
                        <h3 className="text-base font-semibold text-ink-900 dark:text-white">Coordonnées du bénéficiaire</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Comment joindre directement la personne accompagnée — usage opérationnel uniquement.
                        </p>
                    </CardHeader>
                    <CardBody>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                label="Téléphone"
                                value={data.phone}
                                onChange={(v) => setData('phone', v)}
                                error={errors.phone}
                                placeholder="06 12 34 56 78"
                                disabled={!can_update}
                            />
                            <TextField
                                label="E-mail"
                                value={data.email}
                                onChange={(v) => setData('email', v)}
                                error={errors.email}
                                placeholder="prenom.nom@exemple.fr"
                                type="email"
                                disabled={!can_update}
                            />
                            <TextField
                                label="Adresse"
                                value={data.address}
                                onChange={(v) => setData('address', v)}
                                error={errors.address}
                                placeholder="12 rue des Lilas"
                                className="sm:col-span-2"
                                disabled={!can_update}
                            />
                            <TextField
                                label="Code postal"
                                value={data.postal_code}
                                onChange={(v) => setData('postal_code', v)}
                                error={errors.postal_code}
                                placeholder="75001"
                                disabled={!can_update}
                            />
                            <TextField
                                label="Ville"
                                value={data.city}
                                onChange={(v) => setData('city', v)}
                                error={errors.city}
                                placeholder="Paris"
                                disabled={!can_update}
                            />
                        </div>
                    </CardBody>
                </Card>

                {/* Primary doctor */}
                <Card>
                    <CardHeader>
                        <h3 className="text-base font-semibold text-ink-900 dark:text-white">Médecin référent</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Médecin traitant déclaré. À contacter en cas de question médicale dépassant le cadre des soins prévus.
                        </p>
                    </CardHeader>
                    <CardBody>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                label="Nom du médecin"
                                value={data.primary_doctor}
                                onChange={(v) => setData('primary_doctor', v)}
                                error={errors.primary_doctor}
                                placeholder="Dr Martin Dupont"
                                disabled={!can_update}
                            />
                            <TextField
                                label="Téléphone cabinet"
                                value={data.primary_doctor_phone}
                                onChange={(v) => setData('primary_doctor_phone', v)}
                                error={errors.primary_doctor_phone}
                                placeholder="01 23 45 67 89"
                                disabled={!can_update}
                            />
                        </div>
                    </CardBody>
                </Card>

                {/* Emergency contact */}
                <Card>
                    <CardHeader>
                        <h3 className="text-base font-semibold text-ink-900 dark:text-white">Personne à prévenir en urgence</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Membre de la famille ou proche désigné pour les urgences. Ce contact reçoit les notifications graves.
                        </p>
                    </CardHeader>
                    <CardBody>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TextField
                                label="Nom et prénom"
                                value={data.emergency_contact_name}
                                onChange={(v) => setData('emergency_contact_name', v)}
                                error={errors.emergency_contact_name}
                                placeholder="Marie Dupont"
                                disabled={!can_update}
                            />
                            <TextField
                                label="Téléphone"
                                value={data.emergency_contact_phone}
                                onChange={(v) => setData('emergency_contact_phone', v)}
                                error={errors.emergency_contact_phone}
                                placeholder="06 98 76 54 32"
                                disabled={!can_update}
                            />
                            <TextField
                                label="Lien de parenté"
                                value={data.emergency_contact_relationship}
                                onChange={(v) => setData('emergency_contact_relationship', v)}
                                error={errors.emergency_contact_relationship}
                                placeholder="Fille, fils, conjoint·e, voisin·e…"
                                className="sm:col-span-2"
                                disabled={!can_update}
                            />
                        </div>
                    </CardBody>
                </Card>

                {can_update && (
                    <div className="flex justify-end gap-2">
                        <Link href={`/beneficiaries/${beneficiary.id}`}>
                            <Button type="button" variant="secondary">
                                Annuler
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Enregistrement…' : 'Enregistrer les contacts'}
                        </Button>
                    </div>
                )}
            </form>
        </DashboardLayout>
    );
}

function TextField({
    label,
    value,
    onChange,
    error,
    placeholder,
    type = 'text',
    disabled,
    className,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    error?: string;
    placeholder?: string;
    type?: string;
    disabled?: boolean;
    className?: string;
}) {
    return (
        <label className={`block ${className ?? ''}`}>
            <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">{label}</span>
            <input
                type={type}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                disabled={disabled}
                className="mt-1 block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 disabled:cursor-not-allowed disabled:bg-ink-50 disabled:text-ink-500 dark:border-ink-700 dark:bg-ink-800 dark:text-white dark:disabled:bg-ink-900"
            />
            {error && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{error}</p>}
        </label>
    );
}
