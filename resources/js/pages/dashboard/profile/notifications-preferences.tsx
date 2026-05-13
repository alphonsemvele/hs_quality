import { Badge, Button, Card, CardBody, CardHeader, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface ChannelPref {
    inApp: boolean;
    email: boolean;
}

interface NotifConfig {
    key: string;
    title: string;
    description: string;
    severity: 'info' | 'warning' | 'critical';
    /** Whether the user can really disable this (some critical ones are mandatory). */
    mandatory?: boolean;
    defaults: ChannelPref;
}

const NOTIFS: NotifConfig[] = [
    {
        key: 'incident.declared',
        title: 'Nouvel incident déclaré',
        description: 'Quand un·e intervenant·e déclare un EI, vous êtes notifié·e.',
        severity: 'warning',
        mandatory: true,
        defaults: { inApp: true, email: true },
    },
    {
        key: 'incident.ars',
        title: 'Notification ARS (grave/critique)',
        description: 'Déclaration ARS sous 24h obligatoire — alerte critique immédiate.',
        severity: 'critical',
        mandatory: true,
        defaults: { inApp: true, email: true },
    },
    {
        key: 'qvct.weak_signal',
        title: 'Signal faible RPS détecté',
        description: 'Le détecteur identifie un cluster RPS sur une équipe ou un horaire.',
        severity: 'warning',
        defaults: { inApp: true, email: true },
    },
    {
        key: 'qvct.exchange_request',
        title: 'Demande d\'échange (QVCT)',
        description: 'Quand un·e collaborateur·rice sollicite un entretien.',
        severity: 'info',
        defaults: { inApp: true, email: false },
    },
    {
        key: 'certification.expiring',
        title: 'Certification expire bientôt',
        description: 'Alerte 60j et 30j avant l\'échéance d\'une certification.',
        severity: 'warning',
        defaults: { inApp: true, email: true },
    },
    {
        key: 'audit.finalised',
        title: 'Audit finalisé',
        description: 'PDF horodaté disponible, plan d\'amélioration auto-généré.',
        severity: 'info',
        defaults: { inApp: true, email: false },
    },
    {
        key: 'subscription.renewal',
        title: 'Renouvellement abonnement',
        description: 'Rappel 14j et 3j avant le renouvellement.',
        severity: 'info',
        defaults: { inApp: true, email: true },
    },
    {
        key: 'system.maintenance',
        title: 'Maintenance programmée',
        description: 'Annonce de fenêtres de maintenance ≥ 24h à l\'avance.',
        severity: 'info',
        defaults: { inApp: true, email: false },
    },
];

export default function NotificationsPreferences() {
    const [prefs, setPrefs] = useState<Record<string, ChannelPref>>(() =>
        Object.fromEntries(NOTIFS.map((n) => [n.key, { ...n.defaults }])),
    );
    const [saved, setSaved] = useState(false);

    const toggle = (key: string, channel: keyof ChannelPref) => {
        const notif = NOTIFS.find((n) => n.key === key);
        if (notif?.mandatory) return;
        setPrefs((prev) => ({
            ...prev,
            [key]: { ...prev[key], [channel]: !prev[key][channel] },
        }));
        setSaved(false);
    };

    const save = () => {
        // Backend endpoint not yet wired — for now we persist client-side and
        // show a confirmation. Replace with `router.put('/dashboard/profile/notifications', prefs)`
        // when the backend endpoint exists.
        try {
            window.localStorage.setItem('hsq.notif-prefs', JSON.stringify(prefs));
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
        } catch {
            // ignore
        }
    };

    return (
        <DashboardLayout title="Préférences de notifications" subtitle="Choisissez ce que vous recevez">
            <PageHeader
                title="Préférences de notifications"
                subtitle="Choisissez les événements et les canaux par lesquels vous souhaitez être notifié·e"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Mon profil', href: '/dashboard/profile' },
                    { label: 'Notifications' },
                ]}
                actions={
                    <Button onClick={save}>
                        {saved ? '✓ Enregistré' : 'Enregistrer'}
                    </Button>
                }
            />

            <div className="mx-auto max-w-4xl">
                <Card>
                    <CardHeader title="Catégories" subtitle="Les notifications critiques (ARS, MFA) ne sont pas désactivables — exigence sécurité" />
                    <CardBody className="px-0">
                        <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                            <li className="grid grid-cols-12 gap-3 bg-ink-50/40 px-5 py-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:bg-ink-900/30 dark:text-ink-400">
                                <span className="col-span-8">Événement</span>
                                <span className="col-span-2 text-center">In-app</span>
                                <span className="col-span-2 text-center">Email</span>
                            </li>
                            {NOTIFS.map((n) => {
                                const p = prefs[n.key];
                                return (
                                    <li key={n.key} className="grid grid-cols-12 items-center gap-3 px-5 py-3">
                                        <div className="col-span-8 flex items-start gap-3">
                                            <SeverityDot severity={n.severity} />
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-sm font-medium text-ink-900 dark:text-white">{n.title}</p>
                                                    {n.mandatory && <Badge tone="danger" size="xs">Obligatoire</Badge>}
                                                </div>
                                                <p className="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">{n.description}</p>
                                            </div>
                                        </div>
                                        <div className="col-span-2 text-center">
                                            <Toggle checked={p.inApp} disabled={n.mandatory} onChange={() => toggle(n.key, 'inApp')} ariaLabel={`In-app ${n.title}`} />
                                        </div>
                                        <div className="col-span-2 text-center">
                                            <Toggle checked={p.email} disabled={n.mandatory} onChange={() => toggle(n.key, 'email')} ariaLabel={`Email ${n.title}`} />
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </CardBody>
                </Card>

                <div className="mt-5 rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                    <p className="font-semibold text-brand-900 dark:text-brand-200">💡 À savoir</p>
                    <ul className="mt-1.5 space-y-1 text-brand-900/80 dark:text-brand-200/80">
                        <li>• <strong>SMS volontairement exclu</strong> (risque SIM-swap pour données de santé, deprecated NIST/ANSSI/CNIL)</li>
                        <li>• Les notifications in-app sont consultables depuis la cloche en haut à droite</li>
                        <li>• Les notifications critiques (ARS, MFA, suspension de compte) restent toujours actives</li>
                    </ul>
                </div>
            </div>
        </DashboardLayout>
    );
}

function Toggle({ checked, disabled, onChange, ariaLabel }: { checked: boolean; disabled?: boolean; onChange: () => void; ariaLabel: string }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={ariaLabel}
            onClick={disabled ? undefined : onChange}
            disabled={disabled}
            className={cn(
                'relative inline-flex h-5 w-9 cursor-pointer items-center rounded-full transition-colors',
                checked ? 'bg-brand-600 dark:bg-brand-500' : 'bg-ink-300 dark:bg-ink-600',
                disabled && 'cursor-not-allowed opacity-50',
            )}
        >
            <span
                className={cn(
                    'inline-block size-4 transform rounded-full bg-white transition-transform',
                    checked ? 'translate-x-4' : 'translate-x-0.5',
                )}
            />
        </button>
    );
}

function SeverityDot({ severity }: { severity: 'info' | 'warning' | 'critical' }) {
    const cls = {
        info: 'bg-brand-500',
        warning: 'bg-warning-500',
        critical: 'bg-danger-500',
    }[severity];
    return <span aria-hidden className={cn('mt-1.5 size-2 shrink-0 rounded-full', cls)} />;
}
