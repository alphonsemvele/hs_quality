import { Badge, Button, Card, CardBody, CardHeader, ConfirmDialog, EmptyState, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Session {
    id: string;
    user_agent: string;
    device_name: string;
    browser: string;
    os: string;
    ip_address: string;
    location: string;
    last_active: string;
    is_current: boolean;
}

const DEMO_SESSIONS: Session[] = [
    {
        id: 'sess-1',
        user_agent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) Chrome/126',
        device_name: 'MacBook Pro',
        browser: 'Chrome 126',
        os: 'macOS 14.5',
        ip_address: '82.65.123.45',
        location: 'Paris, France',
        last_active: new Date().toISOString(),
        is_current: true,
    },
    {
        id: 'sess-2',
        user_agent: 'HS Quality Mobile / iOS 17',
        device_name: 'iPhone 15',
        browser: 'App native',
        os: 'iOS 17.5',
        ip_address: '109.130.45.12',
        location: 'Marseille, France',
        last_active: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString(),
        is_current: false,
    },
    {
        id: 'sess-3',
        user_agent: 'Mozilla/5.0 (Windows NT 10.0) Firefox/127',
        device_name: 'PC bureau',
        browser: 'Firefox 127',
        os: 'Windows 10',
        ip_address: '92.184.55.201',
        location: 'Lyon, France',
        last_active: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
        is_current: false,
    },
];

export default function Sessions() {
    const [sessions, setSessions] = useState<Session[]>(DEMO_SESSIONS);
    const [revokeTarget, setRevokeTarget] = useState<Session | null>(null);
    const [revokeAll, setRevokeAll] = useState(false);

    const revoke = () => {
        if (!revokeTarget) return;
        setSessions((prev) => prev.filter((s) => s.id !== revokeTarget.id));
        setRevokeTarget(null);
    };

    const revokeAllOthers = () => {
        setSessions((prev) => prev.filter((s) => s.is_current));
        setRevokeAll(false);
    };

    const otherCount = sessions.filter((s) => !s.is_current).length;

    return (
        <DashboardLayout title="Sessions actives" subtitle="Appareils connectés à votre compte">
            <PageHeader
                title="Sessions actives"
                subtitle="Les appareils sur lesquels vous êtes actuellement connecté·e — révoquez tout accès suspect"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Mon profil', href: '/dashboard/profile' },
                    { label: 'Sessions' },
                ]}
                actions={
                    otherCount > 0 && (
                        <Button variant="danger" onClick={() => setRevokeAll(true)}>
                            Déconnecter tous les autres ({otherCount})
                        </Button>
                    )
                }
            />

            <Card>
                <CardHeader title="Appareils connectés" subtitle={`${sessions.length} session${sessions.length > 1 ? 's' : ''} active${sessions.length > 1 ? 's' : ''}`} />
                <CardBody className="px-0">
                    {sessions.length > 0 ? (
                        <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                            {sessions.map((s) => (
                                <li key={s.id} className="flex items-start gap-3 px-5 py-4">
                                    <span className={cn(
                                        'flex size-10 shrink-0 items-center justify-center rounded-xl',
                                        s.is_current ? 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300' : 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300',
                                    )}>
                                        <DeviceIcon kind={s.os.toLowerCase().includes('ios') || s.os.toLowerCase().includes('android') ? 'mobile' : 'desktop'} />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">
                                                {s.device_name} · {s.browser}
                                            </p>
                                            {s.is_current && <Badge tone="sage" size="xs" dot>Cet appareil</Badge>}
                                        </div>
                                        <p className="font-mono text-[11px] text-ink-500 dark:text-ink-400">
                                            {s.os} · {s.location} ({s.ip_address})
                                        </p>
                                        <p className="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">
                                            {s.is_current ? 'Active maintenant' : `Dernière activité ${formatRelative(s.last_active)}`}
                                        </p>
                                    </div>
                                    {!s.is_current && (
                                        <Button variant="ghost" size="sm" onClick={() => setRevokeTarget(s)}>
                                            Déconnecter
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState title="Aucune session" description="Reconnectez-vous pour voir cette page." />
                    )}
                </CardBody>
            </Card>

            <div className="mt-5 rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                <p className="font-semibold text-brand-900 dark:text-brand-200">🔒 Bonnes pratiques sécurité</p>
                <ul className="mt-1.5 space-y-1 text-brand-900/80 dark:text-brand-200/80">
                    <li>• Si vous reconnaissez un appareil inhabituel, <strong>déconnectez-le immédiatement</strong> et changez votre mot de passe</li>
                    <li>• Les sessions inactives ≥ 30 jours sont déconnectées automatiquement</li>
                    <li>• Toujours vous déconnecter sur les appareils partagés (clinique, salle de réunion)</li>
                </ul>
            </div>

            <ConfirmDialog
                open={revokeTarget !== null}
                onClose={() => setRevokeTarget(null)}
                onConfirm={revoke}
                title={`Déconnecter ${revokeTarget?.device_name} ?`}
                description="L'utilisateur de cet appareil sera invité à se reconnecter. Si c'est vous, vous pourrez vous reconnecter normalement."
                confirmLabel="Déconnecter"
                tone="warning"
            />

            <ConfirmDialog
                open={revokeAll}
                onClose={() => setRevokeAll(false)}
                onConfirm={revokeAllOthers}
                title={`Déconnecter les ${otherCount} autres sessions ?`}
                description="Tous les autres appareils seront déconnectés. À utiliser si vous suspectez un accès non autorisé."
                confirmLabel="Tout déconnecter"
                tone="danger"
            />
        </DashboardLayout>
    );
}

function formatRelative(iso: string): string {
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return 'il y a < 1 min';
    if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`;
    return `il y a ${Math.floor(diff / 86400)} j`;
}

function DeviceIcon({ kind }: { kind: 'mobile' | 'desktop' }) {
    if (kind === 'mobile') {
        return (
            <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                <rect x="5" y="2" width="14" height="20" rx="2" />
                <line x1="12" y1="18" x2="12.01" y2="18" />
            </svg>
        );
    }
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="2" y="3" width="20" height="14" rx="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
        </svg>
    );
}
