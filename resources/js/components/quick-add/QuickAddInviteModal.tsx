import { Button, FormField, Input, Modal, Select } from '@/components/ui';
import { useModalSubmitShortcut } from '@/lib/use-modal-submit-shortcut';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

interface Props {
    open: boolean;
    onClose: () => void;
}

const ROLES = [
    { value: 'intervenant', label: 'Intervenant·e à domicile' },
    { value: 'coordinateur', label: 'Coordinateur·rice / Responsable de secteur' },
    { value: 'referent_qualite', label: 'Référent·e qualité' },
    { value: 'rh', label: 'Responsable RH / formation' },
    { value: 'dirigeant', label: 'Dirigeant·e' },
] as const;

export function QuickAddInviteModal({ open, onClose }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        type: '',
    });

    useEffect(() => {
        if (!open) reset();
    }, [open, reset]);

    const submit = (e?: React.FormEvent | Event) => {
        e?.preventDefault();
        post('/users', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    useModalSubmitShortcut({ open, disabled: processing, onSubmit: () => submit() });

    const selectedRole = ROLES.find((r) => r.value === data.type);
    const requiresMfa = selectedRole && ['coordinateur', 'referent_qualite', 'rh', 'dirigeant'].includes(selectedRole.value);

    return (
        <Modal
            open={open}
            onClose={processing ? () => {} : onClose}
            title="Inviter un utilisateur"
            description="Un email d'invitation sera envoyé. Le destinataire choisira son mot de passe et activera la double authentification si son rôle l'exige."
            size="md"
            iconTone="brand"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <line x1="20" y1="8" x2="20" y2="14" />
                    <line x1="23" y1="11" x2="17" y2="11" />
                </svg>
            }
            footer={
                <>
                    <Button variant="ghost" onClick={onClose} disabled={processing}>
                        Annuler
                    </Button>
                    <Button onClick={submit} loading={processing}>
                        Envoyer l'invitation
                    </Button>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    <FormField label="Prénom" htmlFor="qa-invite-fn" required error={errors.first_name}>
                        <Input
                            id="qa-invite-fn"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            required
                            maxLength={100}
                            invalid={!!errors.first_name}
                        />
                    </FormField>
                    <FormField label="Nom" htmlFor="qa-invite-ln" required error={errors.last_name}>
                        <Input
                            id="qa-invite-ln"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            required
                            maxLength={100}
                            invalid={!!errors.last_name}
                        />
                    </FormField>
                </div>

                <FormField label="Email" htmlFor="qa-invite-email" required error={errors.email} help="L'utilisateur recevra le lien d'invitation à cette adresse.">
                    <Input
                        id="qa-invite-email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="email"
                        invalid={!!errors.email}
                    />
                </FormField>

                <FormField label="Rôle" htmlFor="qa-invite-type" required error={errors.type}>
                    <Select
                        id="qa-invite-type"
                        value={data.type}
                        onChange={(e) => setData('type', e.target.value)}
                        required
                        invalid={!!errors.type}
                    >
                        <option value="" disabled>
                            Sélectionner un rôle
                        </option>
                        {ROLES.map((r) => (
                            <option key={r.value} value={r.value}>
                                {r.label}
                            </option>
                        ))}
                    </Select>
                </FormField>

                {requiresMfa && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-brand-200 bg-brand-50/60 px-3 py-2.5 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                        <svg className="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0110 0v4" />
                        </svg>
                        <p className="text-brand-900 dark:text-brand-200">
                            <strong className="font-semibold">MFA obligatoire</strong> pour ce rôle. La double authentification
                            devra être activée à la première connexion.
                        </p>
                    </div>
                )}
            </form>
        </Modal>
    );
}
