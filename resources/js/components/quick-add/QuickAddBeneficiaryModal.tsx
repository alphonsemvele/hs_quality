import { Button, FormField, Input, Modal, Select } from '@/components/ui';
import { useModalSubmitShortcut } from '@/lib/use-modal-submit-shortcut';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

interface Props {
    open: boolean;
    onClose: () => void;
}

export function QuickAddBeneficiaryModal({ open, onClose }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        date_of_birth: '',
        gender: '',
        gir: '',
    });

    useEffect(() => {
        if (!open) reset();
    }, [open, reset]);

    const submit = (e?: React.FormEvent | Event) => {
        e?.preventDefault();
        post('/beneficiaries', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    useModalSubmitShortcut({ open, disabled: processing, onSubmit: () => submit() });

    return (
        <Modal
            open={open}
            onClose={processing ? () => {} : onClose}
            title="Ajouter un bénéficiaire"
            description="Saisie rapide — complétez ensuite l'adresse, le médecin référent et les contacts d'urgence sur la fiche."
            size="md"
            iconTone="sage"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            }
            footer={
                <>
                    <Button variant="ghost" onClick={onClose} disabled={processing}>
                        Annuler
                    </Button>
                    <Button onClick={submit} loading={processing}>
                        Créer
                    </Button>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    <FormField label="Prénom" htmlFor="qa-fn" required error={errors.first_name}>
                        <Input
                            id="qa-fn"
                            autoFocus
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            required
                            maxLength={100}
                            invalid={!!errors.first_name}
                        />
                    </FormField>
                    <FormField label="Nom" htmlFor="qa-ln" required error={errors.last_name}>
                        <Input
                            id="qa-ln"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            required
                            maxLength={100}
                            invalid={!!errors.last_name}
                        />
                    </FormField>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <FormField label="Date de naissance (optionnel)" htmlFor="qa-dob" error={errors.date_of_birth}>
                        <Input
                            id="qa-dob"
                            type="date"
                            value={data.date_of_birth}
                            onChange={(e) => setData('date_of_birth', e.target.value)}
                            invalid={!!errors.date_of_birth}
                        />
                    </FormField>
                    <FormField label="GIR (optionnel)" htmlFor="qa-gir" error={errors.gir} help="Groupe Iso-Ressources, 1 à 6">
                        <Select
                            id="qa-gir"
                            value={data.gir}
                            onChange={(e) => setData('gir', e.target.value)}
                            invalid={!!errors.gir}
                        >
                            <option value="">—</option>
                            {[1, 2, 3, 4, 5, 6].map((g) => (
                                <option key={g} value={g}>
                                    GIR {g}
                                </option>
                            ))}
                        </Select>
                    </FormField>
                </div>

                <FormField label="Sexe (optionnel)" htmlFor="qa-gender" error={errors.gender}>
                    <Select
                        id="qa-gender"
                        value={data.gender}
                        onChange={(e) => setData('gender', e.target.value)}
                        invalid={!!errors.gender}
                    >
                        <option value="">Non renseigné</option>
                        <option value="female">Femme</option>
                        <option value="male">Homme</option>
                        <option value="other">Autre</option>
                    </Select>
                </FormField>

                <p className="rounded-lg border border-brand-200 bg-brand-50/40 px-3 py-2 text-[11px] text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                    Le dossier médical (allergies, traitements, antécédents) reste en lecture/écriture tracée — accessible
                    depuis l'onglet « Dossier » après création.
                </p>
            </form>
        </Modal>
    );
}
