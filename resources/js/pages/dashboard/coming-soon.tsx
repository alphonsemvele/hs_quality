import { Badge, Button, Card, PageHeader } from '@/components/ui';
import DashboardLayout from './layout';

interface Props {
    feature: string;
    feature_label: string;
    description: string;
    eta: string;
    tier_required?: string;
}

export default function ComingSoon({ feature, feature_label, description, eta, tier_required }: Partial<Props>) {
    const label = feature_label ?? feature ?? 'Module à venir';

    return (
        <DashboardLayout title={label} subtitle="Module en préparation">
            <PageHeader
                title={label}
                subtitle="Cette fonctionnalité fait partie de la feuille de route et n'est pas encore disponible."
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label }]}
            />

            <Card className="overflow-hidden">
                <div className="flex flex-col items-start gap-6 p-8 sm:p-10">
                    <div className="flex items-center gap-3">
                        <Badge tone="brand" size="sm">
                            Bientôt disponible
                        </Badge>
                        {tier_required && (
                            <Badge tone="sage" size="sm">
                                Tier {tier_required}
                            </Badge>
                        )}
                    </div>

                    <div className="max-w-2xl">
                        <h2 className="font-serif text-2xl font-medium text-ink-900 sm:text-3xl">{feature ?? label}</h2>
                        {description && <p className="mt-3 text-sm leading-relaxed text-ink-600">{description}</p>}
                    </div>

                    <div className="grid w-full max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2">
                        <InfoTile label="Disponibilité prévue" value={eta ?? 'Non planifiée'} />
                        <InfoTile label="Tier requis" value={tier_required ? `Pro / Premium` : 'Tous'} />
                    </div>

                    <div className="flex flex-wrap items-center gap-2 pt-2">
                        <Button variant="secondary" onClick={() => window.history.back()}>
                            Retour
                        </Button>
                        <Button variant="ghost" onClick={() => (window.location.href = '/dashboard')}>
                            Tableau de bord
                        </Button>
                    </div>
                </div>
            </Card>
        </DashboardLayout>
    );
}

function InfoTile({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-ink-100 bg-ink-50/50 p-4">
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{label}</p>
            <p className="mt-1.5 text-sm font-medium text-ink-900">{value}</p>
        </div>
    );
}
