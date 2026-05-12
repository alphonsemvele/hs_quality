import { Button, FormField, Input, Modal, Select } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

interface Option {
    id: number | string;
    name: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
    intervenants: Option[];
    beneficiaries: Option[];
    onCreated?: (id: string | number) => void;
}

function tomorrow(): string {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
}

export function QuickAddInterventionModal({ open, onClose, intervenants, beneficiaries }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        intervenant_id: '',
        beneficiary_id: '',
        planned_date: tomorrow(),
        planned_start_time: '09:00',
    });

    useEffect(() => {
        if (!open) reset();
    }, [open, reset]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/interventions', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <Modal
            open={open}
            onClose={processing ? () => {} : onClose}
            title="Planifier une intervention"
            description="Saisie rapide — vous pourrez ajouter horaires détaillés et plan de soins sur la fiche complète."
            size="md"
            iconTone="brand"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            }
            footer={
                <>
                    <Button variant="ghost" onClick={onClose} disabled={processing}>
                        Annuler
                    </Button>
                    <Button onClick={submit} loading={processing}>
                        Planifier
                    </Button>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4">
                <FormField label="Intervenant" htmlFor="qa-intervenant" required error={errors.intervenant_id}>
                    <Select
                        id="qa-intervenant"
                        value={data.intervenant_id}
                        onChange={(e) => setData('intervenant_id', e.target.value)}
                        required
                        invalid={!!errors.intervenant_id}
                    >
                        <option value="" disabled>
                            Sélectionner un intervenant
                        </option>
                        {intervenants.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.name}
                            </option>
                        ))}
                    </Select>
                </FormField>

                <FormField label="Bénéficiaire" htmlFor="qa-beneficiary" required error={errors.beneficiary_id}>
                    <Select
                        id="qa-beneficiary"
                        value={data.beneficiary_id}
                        onChange={(e) => setData('beneficiary_id', e.target.value)}
                        required
                        invalid={!!errors.beneficiary_id}
                    >
                        <option value="" disabled>
                            Sélectionner un bénéficiaire
                        </option>
                        {beneficiaries.map((b) => (
                            <option key={b.id} value={b.id}>
                                {b.name}
                            </option>
                        ))}
                    </Select>
                </FormField>

                <div className="grid grid-cols-2 gap-3">
                    <FormField label="Date" htmlFor="qa-date" required error={errors.planned_date}>
                        <Input
                            id="qa-date"
                            type="date"
                            value={data.planned_date}
                            onChange={(e) => setData('planned_date', e.target.value)}
                            required
                            invalid={!!errors.planned_date}
                        />
                    </FormField>
                    <FormField label="Heure" htmlFor="qa-start" error={errors.planned_start_time}>
                        <Input
                            id="qa-start"
                            type="time"
                            value={data.planned_start_time}
                            onChange={(e) => setData('planned_start_time', e.target.value)}
                            invalid={!!errors.planned_start_time}
                        />
                    </FormField>
                </div>
            </form>
        </Modal>
    );
}
