import { Badge, Button, Card, CardBody, CardHeader, FormField, Input, PageHeader } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface StructureData {
    id: string;
    code: string;
    name: string;
    type: string;
    type_label: string;
    siret: string | null;
    address: string | null;
    tier: string;
    tier_label: string;
    status: string;
    billing_email: string | null;
    trial_ends_at: string | null;
    created_at: string | null;
}

interface ComplianceData {
    hosting: string;
    encryption_at_rest: string;
    encryption_in_transit: string;
    data_retention: string;
    backup_frequency: string;
    audit_log: string;
}

interface Props {
    structure: StructureData;
    compliance: ComplianceData;
}

export default function StructureSettings({ structure, compliance }: Props) {
    const [editingContact, setEditingContact] = useState(false);
    const { data, setData, put, processing, errors, reset } = useForm({
        billing_email: structure.billing_email ?? '',
    });

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        put('/settings/contact', {
            preserveScroll: true,
            onSuccess: () => setEditingContact(false),
        });
    };

    const cancelEdit = () => {
        setData('billing_email', structure.billing_email ?? '');
        setEditingContact(false);
        reset();
    };

    return (
        <DashboardLayout title="Paramètres de la structure" subtitle="Profil, contact et conformité">
            <PageHeader
                title="Paramètres de la structure"
                subtitle="Identité, coordonnées et informations de conformité"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Paramètres' },
                    { label: 'Structure' },
                ]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Identity */}
                <Card className="lg:col-span-2">
                    <CardHeader title="Identité" subtitle="Informations légales — modifiables via le support plateforme" />
                    <CardBody className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <ReadOnlyField label="Nom" value={structure.name} />
                            <ReadOnlyField label="Code interne" value={structure.code} mono />
                            <ReadOnlyField
                                label="Type"
                                value={
                                    <Badge tone="brand" size="sm">
                                        {structure.type_label}
                                    </Badge>
                                }
                            />
                            <ReadOnlyField label="SIRET" value={structure.siret ?? '—'} mono />
                            <ReadOnlyField label="Adresse" value={structure.address ?? '—'} fullWidth />
                        </div>

                        <div className="rounded-xl border border-brand-200 bg-brand-50/40 px-3 py-2.5 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                            <p className="text-brand-900 dark:text-brand-200">
                                Pour modifier ces informations, contactez le support — chaque changement est tracé pour
                                des raisons réglementaires.
                            </p>
                        </div>
                    </CardBody>
                </Card>

                {/* Subscription summary */}
                <Card>
                    <CardHeader title="Abonnement" subtitle="Plan actuel" />
                    <CardBody className="space-y-3">
                        <div>
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                Tier
                            </p>
                            <p className="mt-1 font-mono text-xl font-bold text-ink-900 dark:text-white">
                                {structure.tier_label}
                            </p>
                        </div>
                        <div>
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                Statut
                            </p>
                            <Badge tone={structure.status === 'active' ? 'sage' : 'warning'} size="sm" dot>
                                {structure.status === 'active' ? 'Actif' : structure.status}
                            </Badge>
                        </div>
                        {structure.trial_ends_at && (
                            <div>
                                <p className="text-[11px] font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400">
                                    Période d'essai
                                </p>
                                <p className="mt-1 text-sm text-ink-700 dark:text-ink-200">
                                    Jusqu'au {new Date(structure.trial_ends_at).toLocaleDateString('fr-FR')}
                                </p>
                            </div>
                        )}
                        <a
                            href="/billing"
                            className="block rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-center text-xs font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-700/40 dark:bg-brand-900/30 dark:text-brand-300 dark:hover:bg-brand-900/50"
                        >
                            Gérer l'abonnement →
                        </a>
                    </CardBody>
                </Card>

                {/* Contact — editable */}
                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Contact"
                        subtitle="Email recevant les factures et notifications administratives"
                        action={
                            !editingContact && (
                                <Button variant="secondary" size="sm" onClick={() => setEditingContact(true)}>
                                    Modifier
                                </Button>
                            )
                        }
                    />
                    <CardBody>
                        {editingContact ? (
                            <form onSubmit={save} className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-end">
                                <div className="flex-1">
                                    <FormField label="Email de facturation" htmlFor="bill-email" required error={errors.billing_email}>
                                        <Input
                                            id="bill-email"
                                            type="email"
                                            value={data.billing_email}
                                            onChange={(e) => setData('billing_email', e.target.value)}
                                            required
                                            autoComplete="email"
                                            invalid={!!errors.billing_email}
                                        />
                                    </FormField>
                                </div>
                                <div className="flex gap-2 sm:pb-1">
                                    <Button variant="ghost" onClick={cancelEdit} type="button">
                                        Annuler
                                    </Button>
                                    <Button type="submit" loading={processing}>
                                        Enregistrer
                                    </Button>
                                </div>
                            </form>
                        ) : (
                            <p className="font-mono text-sm text-ink-900 dark:text-white">
                                {structure.billing_email ?? <span className="text-ink-400">— non renseigné —</span>}
                            </p>
                        )}
                    </CardBody>
                </Card>

                {/* Compliance */}
                <Card className="lg:col-span-3">
                    <CardHeader title="Conformité & sécurité" subtitle="Paramètres infrastructure (lecture seule)" />
                    <CardBody className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <ComplianceItem label="Hébergement" value={compliance.hosting} icon={<ShieldIcon />} tone="sage" />
                        <ComplianceItem label="Chiffrement au repos" value={compliance.encryption_at_rest} icon={<LockIcon />} tone="brand" />
                        <ComplianceItem label="Chiffrement transport" value={compliance.encryption_in_transit} icon={<LockIcon />} tone="brand" />
                        <ComplianceItem label="Rétention" value={compliance.data_retention} icon={<DatabaseIcon />} tone="neutral" />
                        <ComplianceItem label="Sauvegardes" value={compliance.backup_frequency} icon={<CloudIcon />} tone="neutral" />
                        <ComplianceItem label="Journal d'audit" value={compliance.audit_log} icon={<EyeIcon />} tone="sage" />
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function ReadOnlyField({ label, value, mono, fullWidth }: { label: string; value: React.ReactNode; mono?: boolean; fullWidth?: boolean }) {
    return (
        <div className={fullWidth ? 'sm:col-span-2' : ''}>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
            <p className={`mt-1 text-sm ${mono ? 'font-mono' : ''} text-ink-900 dark:text-white`}>{value}</p>
        </div>
    );
}

function ComplianceItem({ label, value, icon, tone }: { label: string; value: string; icon: React.ReactNode; tone: 'sage' | 'brand' | 'neutral' }) {
    const cls = {
        sage: 'bg-sage-50 text-sage-700 dark:bg-sage-900/30 dark:text-sage-300',
        brand: 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300',
        neutral: 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300',
    }[tone];
    return (
        <div className="flex items-start gap-3 rounded-xl border border-ink-100 bg-ink-50/40 p-3 dark:border-ink-700/60 dark:bg-ink-900/30">
            <span className={`flex size-8 shrink-0 items-center justify-center rounded-lg ${cls}`}>{icon}</span>
            <div className="min-w-0">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
                <p className="mt-0.5 text-xs text-ink-900 dark:text-white">{value}</p>
            </div>
        </div>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
    );
}
function LockIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
        </svg>
    );
}
function DatabaseIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
        </svg>
    );
}
function CloudIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z" />
        </svg>
    );
}
function EyeIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
    );
}
