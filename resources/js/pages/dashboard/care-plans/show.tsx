import { Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
}

interface Task {
    id: string;
    title: string;
    description: string | null;
    frequency_label: string;
    duration_minutes: number | null;
    task_order: number;
    mandatory: boolean;
}

interface CarePlan {
    id: string;
    title: string;
    objectives: string | null;
    start_date: string | null;
    end_date: string | null;
    status: string;
    status_label: string;
    is_active: boolean;
    is_archived: boolean;
    archived_at: string | null;
    archived_reason: string | null;
    tasks: { data: Task[] } | Task[];
}

interface Props {
    plan: { data: CarePlan };
    beneficiary: { data: Beneficiary };
}

const getTasksArray = (tasks: CarePlan['tasks']): Task[] => {
    if (Array.isArray(tasks)) return tasks;
    if (tasks && 'data' in tasks && Array.isArray(tasks.data)) return tasks.data;
    return [];
};

export default function CarePlanShow({ plan: { data: plan }, beneficiary: { data: b } }: Props) {
    const [archiveOpen, setArchiveOpen] = useState(false);
    const [archiveReason, setArchiveReason] = useState('');
    const tasks = getTasksArray(plan.tasks);

    const activate = () => {
        if (!confirm('Activer ce plan ? Tout plan actuellement actif pour ce bénéficiaire sera automatiquement archivé.')) return;
        router.post(`/care-plans/${plan.id}/activate`);
    };

    const submitArchive = (e: FormEvent) => {
        e.preventDefault();
        router.post(`/care-plans/${plan.id}/archive`, { reason: archiveReason });
    };

    return (
        <DashboardLayout title={plan.title} subtitle={`Plan d'accompagnement — ${b.full_name}`}>

            <div style={{ display: 'flex', gap: 10, marginBottom: 20, alignItems: 'center' }}>
                <Link href={`/beneficiaries/${b.id}/care-plans`} style={{ fontSize: 12, color: '#64748B', textDecoration: 'none' }}>
                    ← Tous les plans
                </Link>
                <span style={{ flex: 1 }} />
                {!plan.is_archived && (
                    <>
                        <Link href={`/care-plans/${plan.id}/edit`} style={{
                            background: '#F1F5F9', color: 'var(--navy)',
                            fontSize: 12, fontWeight: 600, padding: '8px 14px', borderRadius: 8, textDecoration: 'none',
                        }}>Modifier</Link>
                        {!plan.is_active && (
                            <button onClick={activate} style={{
                                background: '#15803D', color: 'white',
                                fontSize: 12, fontWeight: 700, padding: '8px 14px', borderRadius: 8, border: 'none', cursor: 'pointer',
                            }}>Activer</button>
                        )}
                        <button onClick={() => setArchiveOpen(o => !o)} style={{
                            background: '#FEF3C7', color: '#92400E',
                            fontSize: 12, fontWeight: 600, padding: '8px 14px', borderRadius: 8, border: 'none', cursor: 'pointer',
                        }}>Archiver</button>
                    </>
                )}
            </div>

            {archiveOpen && (
                <form onSubmit={submitArchive} style={{ background: '#FEF3C7', borderRadius: 10, padding: 16, marginBottom: 14 }}>
                    <label style={{ fontSize: 12, color: '#92400E', fontWeight: 600, display: 'block', marginBottom: 6 }}>
                        Raison de l'archivage
                    </label>
                    <input
                        autoFocus required
                        value={archiveReason} onChange={e => setArchiveReason(e.target.value)}
                        style={{ width: '100%', padding: '8px 12px', borderRadius: 8, border: '1px solid #FCD34D', fontSize: 13 }}
                    />
                    <button type="submit" style={{
                        marginTop: 10, background: '#92400E', color: 'white',
                        fontSize: 12, fontWeight: 700, padding: '8px 14px', borderRadius: 8, border: 'none', cursor: 'pointer',
                    }}>Confirmer</button>
                </form>
            )}

            <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Informations</h3>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14 }}>
                    <div>
                        <div style={{ fontSize: 11, color: '#64748B' }}>Statut</div>
                        <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>{plan.status_label}</div>
                    </div>
                    <div>
                        <div style={{ fontSize: 11, color: '#64748B' }}>Début</div>
                        <div style={{ fontSize: 13, color: 'var(--navy)' }}>{plan.start_date ?? '—'}</div>
                    </div>
                    <div>
                        <div style={{ fontSize: 11, color: '#64748B' }}>Fin</div>
                        <div style={{ fontSize: 13, color: 'var(--navy)' }}>{plan.end_date ?? 'En cours'}</div>
                    </div>
                </div>
                {plan.archived_at && (
                    <div style={{ marginTop: 14, padding: 10, background: '#FEF3C7', borderRadius: 8, fontSize: 12, color: '#92400E' }}>
                        Archivé le {plan.archived_at.split('T')[0]} — {plan.archived_reason}
                    </div>
                )}
            </div>

            <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Objectifs</h3>
                <p style={{ fontSize: 13, color: 'var(--navy)', whiteSpace: 'pre-wrap', lineHeight: 1.6 }}>
                    {plan.objectives || <span style={{ color: '#94A3B8', fontStyle: 'italic' }}>Aucun objectif renseigné.</span>}
                </p>
            </div>

            <div style={{ background: 'white', borderRadius: 14, padding: 20 }}>
                <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Tâches planifiées ({tasks.length})
                </h3>
                {tasks.length === 0 ? (
                    <p style={{ fontSize: 12, color: '#64748B', fontStyle: 'italic' }}>Aucune tâche planifiée pour le moment.</p>
                ) : (
                    <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
                        {tasks.map(t => (
                            <li key={t.id} style={{ padding: '10px 0', borderBottom: '1px solid #F1F5F9' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>
                                        {t.task_order + 1}. {t.title}
                                        {!t.mandatory && <span style={{ marginLeft: 8, fontSize: 10, color: '#64748B', fontWeight: 400 }}>(optionnel)</span>}
                                    </span>
                                    <span style={{ fontSize: 11, color: '#64748B' }}>
                                        {t.frequency_label}
                                        {t.duration_minutes ? ` · ${t.duration_minutes} min` : ''}
                                    </span>
                                </div>
                                {t.description && <p style={{ fontSize: 12, color: '#64748B', marginTop: 4, marginBottom: 0 }}>{t.description}</p>}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </DashboardLayout>
    );
}
