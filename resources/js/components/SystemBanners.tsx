import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export interface SystemBanner {
    id: string;
    /** Stable key used to remember dismissal in localStorage. */
    dismissKey?: string;
    severity: 'info' | 'warning' | 'danger' | 'sage';
    title: string;
    description: string;
    cta?: { label: string; href: string };
    /** Optional ISO date — banner auto-hides after this date. */
    expiresAt?: string;
}

interface AuthUser {
    requires_mfa?: boolean;
    has_mfa_enrolled?: boolean;
}

interface StructurePayload {
    trial_ends_at?: string | null;
    status?: string;
}

interface SystemBannersPayload {
    /** Banners injected from the backend via HandleInertiaRequests. */
    items?: SystemBanner[];
}

interface PageProps {
    auth?: { user?: AuthUser | null };
    structure?: StructurePayload | null;
    systemBanners?: SystemBannersPayload | null;
    [key: string]: unknown;
}

const STORAGE_PREFIX = 'hsq.banner.dismissed.';

function isDismissed(key: string): boolean {
    if (typeof window === 'undefined') return false;
    try {
        const stored = window.localStorage.getItem(STORAGE_PREFIX + key);
        if (!stored) return false;
        const ts = parseInt(stored, 10);
        return Number.isFinite(ts) && ts > Date.now();
    } catch {
        return false;
    }
}

function dismiss(key: string, days: number): void {
    if (typeof window === 'undefined') return;
    const until = Date.now() + days * 24 * 60 * 60 * 1000;
    try {
        window.localStorage.setItem(STORAGE_PREFIX + key, String(until));
    } catch {
        // ignore
    }
}

function deriveBanners(props: PageProps): SystemBanner[] {
    const banners: SystemBanner[] = [];
    const now = Date.now();

    const trialEnd = props.structure?.trial_ends_at ? new Date(props.structure.trial_ends_at).getTime() : null;
    if (trialEnd && trialEnd > now) {
        const daysLeft = Math.ceil((trialEnd - now) / (24 * 60 * 60 * 1000));
        if (daysLeft <= 14) {
            banners.push({
                id: 'trial-ending',
                dismissKey: `trial-${trialEnd}-${daysLeft <= 3 ? 'urgent' : 'soft'}`,
                severity: daysLeft <= 3 ? 'warning' : 'info',
                title: daysLeft === 1 ? 'Période d\'essai expire demain' : `Période d'essai : ${daysLeft} jours restants`,
                description: 'Ajoutez un moyen de paiement pour continuer à utiliser HS Quality après la période d\'essai.',
                cta: { label: 'Activer mon abonnement →', href: '/billing' },
            });
        }
    }

    if (props.structure?.status === 'suspended') {
        banners.push({
            id: 'structure-suspended',
            severity: 'danger',
            title: 'Compte suspendu',
            description: 'L\'accès aux données est restreint. Contactez le support pour réactiver votre structure.',
            cta: { label: 'Contacter le support', href: 'mailto:support@hsquality.fr' },
        });
    }

    // Backend can inject additional banners (e.g. maintenance windows, release notes).
    if (Array.isArray(props.systemBanners?.items)) {
        for (const item of props.systemBanners.items) {
            if (item.expiresAt && new Date(item.expiresAt).getTime() < now) continue;
            banners.push(item);
        }
    }

    return banners;
}

const SEVERITY_STYLES = {
    info: {
        bg: 'bg-brand-50/80 dark:bg-brand-900/25',
        border: 'border-brand-200 dark:border-brand-700/40',
        title: 'text-brand-900 dark:text-brand-100',
        body: 'text-brand-800/90 dark:text-brand-200/90',
        cta: 'bg-brand-600 hover:bg-brand-700 text-white dark:bg-brand-500 dark:hover:bg-brand-400',
        iconBg: 'bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-300',
    },
    warning: {
        bg: 'bg-warning-50/80 dark:bg-warning-900/25',
        border: 'border-warning-200 dark:border-warning-700/40',
        title: 'text-warning-900 dark:text-warning-100',
        body: 'text-warning-800/90 dark:text-warning-200/90',
        cta: 'bg-warning-700 hover:bg-warning-800 text-white dark:bg-warning-600 dark:hover:bg-warning-500',
        iconBg: 'bg-warning-100 text-warning-700 dark:bg-warning-900/50 dark:text-warning-300',
    },
    danger: {
        bg: 'bg-danger-50/80 dark:bg-danger-900/25',
        border: 'border-danger-200 dark:border-danger-700/40',
        title: 'text-danger-900 dark:text-danger-100',
        body: 'text-danger-800/90 dark:text-danger-200/90',
        cta: 'bg-danger-700 hover:bg-danger-800 text-white dark:bg-danger-600 dark:hover:bg-danger-500',
        iconBg: 'bg-danger-100 text-danger-700 dark:bg-danger-900/50 dark:text-danger-300',
    },
    sage: {
        bg: 'bg-sage-50/80 dark:bg-sage-900/25',
        border: 'border-sage-200 dark:border-sage-700/40',
        title: 'text-sage-900 dark:text-sage-100',
        body: 'text-sage-800/90 dark:text-sage-200/90',
        cta: 'bg-sage-700 hover:bg-sage-800 text-white dark:bg-sage-600 dark:hover:bg-sage-500',
        iconBg: 'bg-sage-100 text-sage-700 dark:bg-sage-900/50 dark:text-sage-300',
    },
} as const;

export default function SystemBanners() {
    const { props } = usePage<PageProps>();
    const [dismissedSet, setDismissedSet] = useState<Set<string>>(new Set());

    const banners = deriveBanners(props as PageProps);

    useEffect(() => {
        const next = new Set<string>();
        for (const b of banners) {
            if (b.dismissKey && isDismissed(b.dismissKey)) next.add(b.id);
        }
        setDismissedSet(next);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [banners.length]);

    const visible = banners.filter((b) => !dismissedSet.has(b.id));
    if (visible.length === 0) return null;

    return (
        <div className="space-y-px">
            {visible.map((b) => {
                const styles = SEVERITY_STYLES[b.severity];
                const handleDismiss = b.dismissKey
                    ? () => {
                          dismiss(b.dismissKey!, b.severity === 'danger' ? 1 : 7);
                          setDismissedSet((prev) => new Set(prev).add(b.id));
                      }
                    : null;
                return (
                    <div
                        key={b.id}
                        role="status"
                        aria-live="polite"
                        className={cn(
                            'border-b px-4 py-2.5 backdrop-blur sm:px-6 lg:px-8',
                            styles.bg,
                            styles.border,
                        )}
                    >
                        <div className="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <div className="flex items-start gap-3 sm:items-center">
                                <span className={cn('flex size-8 shrink-0 items-center justify-center rounded-lg', styles.iconBg)}>
                                    <SeverityIcon severity={b.severity} />
                                </span>
                                <div className="min-w-0">
                                    <p className={cn('text-sm font-semibold', styles.title)}>{b.title}</p>
                                    <p className={cn('text-xs leading-relaxed', styles.body)}>{b.description}</p>
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-2 self-end sm:self-auto">
                                {handleDismiss && (
                                    <button
                                        type="button"
                                        onClick={handleDismiss}
                                        className={cn('rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors hover:bg-black/5 dark:hover:bg-white/10', styles.body)}
                                    >
                                        Plus tard
                                    </button>
                                )}
                                {b.cta && (
                                    <Link
                                        href={b.cta.href}
                                        className={cn('inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold shadow-sm transition-colors', styles.cta)}
                                    >
                                        {b.cta.label}
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function SeverityIcon({ severity }: { severity: SystemBanner['severity'] }) {
    if (severity === 'danger') {
        return (
            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
        );
    }
    if (severity === 'warning') {
        return (
            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
        );
    }
    if (severity === 'sage') {
        return (
            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    }
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
        </svg>
    );
}
