import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
    medical_notes: string | null;
    allergies: string | null;
    medical_history: string | null;
    current_treatments: string | null;
}

interface Props {
    beneficiary: { data: Beneficiary };
}

const Block = ({ title, value }: { title: string; value: string | null }) => (
    <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
        <h3 style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)', marginBottom: 10, textTransform: 'uppercase', letterSpacing: '0.05em' }}>{title}</h3>
        <p style={{ fontSize: 13, color: 'var(--navy)', whiteSpace: 'pre-wrap', lineHeight: 1.6 }}>
            {value ?? <span style={{ color: '#94A3B8', fontStyle: 'italic' }}>Aucune information enregistrée.</span>}
        </p>
    </div>
);

export default function BeneficiaryDossier({ beneficiary: { data } }: Props) {
    return (
        <DashboardLayout title={`Dossier médical — ${data.full_name}`} subtitle="Données de santé · Accès tracé dans le journal d'audit">

            <div style={{
                background: '#FEF3C7', border: '1px solid #FCD34D', color: '#92400E',
                padding: '12px 16px', borderRadius: 10, marginBottom: 16, fontSize: 12,
            }}>
                ⚠️ Cette page contient des données de santé (RGPD article 9). Votre consultation est enregistrée dans le journal d'audit.
            </div>

            <Link href={`/beneficiaries/${data.id}`} style={{ fontSize: 12, color: '#64748B', textDecoration: 'none', display: 'inline-block', marginBottom: 14 }}>← Retour à la fiche</Link>

            <Block title="Notes médicales" value={data.medical_notes} />
            <Block title="Allergies" value={data.allergies} />
            <Block title="Antécédents médicaux" value={data.medical_history} />
            <Block title="Traitements en cours" value={data.current_treatments} />
        </DashboardLayout>
    );
}
