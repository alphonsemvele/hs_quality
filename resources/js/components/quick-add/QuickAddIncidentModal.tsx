import { Button, FormField, Input, Modal, Select, Textarea } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

interface Props {
    open: boolean;
    onClose: () => void;
}

const CATEGORIES = [
    { value: 'chute', label: 'Chute' },
    { value: 'agression', label: 'Agression / comportement' },
    { value: 'erreur_medicamenteuse', label: 'Erreur médicamenteuse' },
    { value: 'maltraitance_suspecte', label: 'Maltraitance suspectée' },
    { value: 'situation_danger', label: 'Situation de danger' },
    { value: 'autre', label: 'Autre' },
] as const;

interface GraviteOption {
    value: string;
    label: string;
    tone: string;
    note?: string;
}

const GRAVITES: GraviteOption[] = [
    { value: 'mineur', label: 'Mineur', tone: 'sage' },
    { value: 'significatif', label: 'Significatif', tone: 'brand' },
    { value: 'grave', label: 'Grave', tone: 'warning', note: 'Notification ARS' },
    { value: 'critique', label: 'Critique', tone: 'danger', note: 'Notification ARS' },
];

export function QuickAddIncidentModal({ open, onClose }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        categorie: '',
        gravite: '',
        occurred_at: new Date().toISOString().slice(0, 16),
        description: '',
        lieu: '',
    });

    useEffect(() => {
        if (!open) reset();
    }, [open, reset]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/incidents', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    const selectedGravite = GRAVITES.find((g) => g.value === data.gravite);
    const requiresARS = selectedGravite?.note === 'Notification ARS';

    return (
        <Modal
            open={open}
            onClose={processing ? () => {} : onClose}
            title="Déclarer un incident"
            description="Saisie rapide — vous pourrez ajouter les détails (5-pourquoi, actions correctives) sur la fiche complète."
            size="lg"
            iconTone="danger"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            }
            footer={
                <>
                    <Button variant="ghost" onClick={onClose} disabled={processing}>
                        Annuler
                    </Button>
                    <Button variant="danger" onClick={submit} loading={processing}>
                        Déclarer l'incident
                    </Button>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField label="Catégorie" htmlFor="categorie" required error={errors.categorie}>
                        <Select
                            id="categorie"
                            name="categorie"
                            value={data.categorie}
                            onChange={(e) => setData('categorie', e.target.value)}
                            required
                            invalid={!!errors.categorie}
                        >
                            <option value="" disabled>
                                Sélectionner…
                            </option>
                            {CATEGORIES.map((c) => (
                                <option key={c.value} value={c.value}>
                                    {c.label}
                                </option>
                            ))}
                        </Select>
                    </FormField>

                    <FormField label="Gravité" htmlFor="gravite" required error={errors.gravite}>
                        <Select
                            id="gravite"
                            name="gravite"
                            value={data.gravite}
                            onChange={(e) => setData('gravite', e.target.value)}
                            required
                            invalid={!!errors.gravite}
                        >
                            <option value="" disabled>
                                Sélectionner…
                            </option>
                            {GRAVITES.map((g) => (
                                <option key={g.value} value={g.value}>
                                    {g.label}
                                </option>
                            ))}
                        </Select>
                    </FormField>
                </div>

                {requiresARS && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-warning-200 bg-warning-50/60 px-3 py-2.5 text-xs dark:border-warning-700/40 dark:bg-warning-900/20">
                        <svg className="mt-0.5 size-4 shrink-0 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <p className="text-warning-900 dark:text-warning-200">
                            <strong className="font-semibold">Notification ARS requise</strong> — Une déclaration aux autorités sera créée
                            automatiquement à la finalisation de cet incident. Le délai légal est de 24 h.
                        </p>
                    </div>
                )}

                <FormField label="Date et heure de l'incident" htmlFor="occurred_at" required error={errors.occurred_at}>
                    <Input
                        id="occurred_at"
                        name="occurred_at"
                        type="datetime-local"
                        value={data.occurred_at}
                        onChange={(e) => setData('occurred_at', e.target.value)}
                        required
                        invalid={!!errors.occurred_at}
                    />
                </FormField>

                <FormField
                    label="Description"
                    htmlFor="description"
                    required
                    error={errors.description}
                    help="Champ chiffré au repos. Décrivez factuellement les circonstances — qui, quoi, où, conséquences immédiates."
                >
                    <Textarea
                        id="description"
                        name="description"
                        rows={4}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        required
                        maxLength={5000}
                        invalid={!!errors.description}
                        placeholder="Ex: Mme L. a glissé en sortant de la salle de bain. Présence d'une flaque non signalée. Aucune blessure apparente, douleur au poignet droit. Famille prévenue à 10h15."
                    />
                </FormField>

                <FormField label="Lieu (optionnel)" htmlFor="lieu" error={errors.lieu}>
                    <Input
                        id="lieu"
                        name="lieu"
                        value={data.lieu}
                        onChange={(e) => setData('lieu', e.target.value)}
                        maxLength={255}
                        placeholder="Salle de bain, domicile du bénéficiaire…"
                    />
                </FormField>
            </form>
        </Modal>
    );
}
