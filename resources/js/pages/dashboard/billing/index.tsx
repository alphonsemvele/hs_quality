import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Tier {
    key: 'essential' | 'pro' | 'premium';
    label: string;
    price_per_user: number;
    is_current: boolean;
    is_recommended?: boolean;
    tagline: string;
    features: string[];
}

interface PaymentMethod {
    brand: string;
    last4: string;
    expires: string;
}

interface Invoice {
    id: string;
    number: string;
    date: string;
    amount: number;
    status: 'paid' | 'open' | 'failed' | 'refunded';
    status_label: string;
    pdf_url: string;
}

interface Props {
    currentTier: 'essential' | 'pro' | 'premium';
    currentTierLabel: string;
    userCount: number;
    nextInvoiceAmount: number;
    nextInvoiceDate: string | null;
    paymentMethod: PaymentMethod | null;
    tiers: Tier[];
    invoices: Invoice[];
    trialEndsAt: string | null;
}

const INVOICE_TONE: Record<Invoice['status'], 'sage' | 'warning' | 'danger' | 'neutral'> = {
    paid: 'sage',
    open: 'warning',
    failed: 'danger',
    refunded: 'neutral',
};

export default function BillingShow({
    currentTier = 'essential',
    currentTierLabel = 'Essentiel',
    userCount = 0,
    nextInvoiceAmount = 0,
    nextInvoiceDate = null,
    paymentMethod = null,
    tiers = [],
    invoices = [],
    trialEndsAt = null,
}: Partial<Props>) {
    const [confirmCancel, setConfirmCancel] = useState(false);

    const cancel = () => {
        router.post('/billing/cancel', undefined, { preserveScroll: true, onSuccess: () => setConfirmCancel(false) });
    };

    return (
        <DashboardLayout title="Abonnement" subtitle="Plan, facturation et paiement">
            <PageHeader
                title="Abonnement & facturation"
                subtitle="Gérez votre plan, votre moyen de paiement et téléchargez vos factures"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Abonnement' }]}
            />

            {trialEndsAt && (
                <div className="mb-5 flex items-center gap-3 rounded-2xl border border-warning-200 bg-warning-50/60 p-4 dark:border-warning-700/40 dark:bg-warning-900/15">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300">
                        <ClockIcon />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium text-warning-900 dark:text-warning-100">Période d'essai en cours</p>
                        <p className="text-xs text-warning-800/80 dark:text-warning-200/80">
                            Votre essai se termine le {trialEndsAt}. Ajoutez un moyen de paiement pour continuer.
                        </p>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Current plan summary */}
                <Card className="lg:col-span-2">
                    <CardHeader title="Votre plan actuel" subtitle="Synthèse facturation" />
                    <CardBody>
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <Stat
                                label="Plan"
                                value={currentTierLabel}
                                hint={`${tiers.find((t) => t.is_current)?.price_per_user ?? 0} € / utilisateur / mois`}
                                accent="brand"
                            />
                            <Stat
                                label="Utilisateurs actifs"
                                value={userCount.toString()}
                                hint="Comptés à date du jour"
                                accent="neutral"
                            />
                            <Stat
                                label="Prochaine facture"
                                value={`${nextInvoiceAmount} €`}
                                hint={nextInvoiceDate ? `Échéance ${nextInvoiceDate}` : '—'}
                                accent="sage"
                            />
                        </div>
                    </CardBody>
                </Card>

                {/* Payment method */}
                <Card>
                    <CardHeader title="Moyen de paiement" subtitle="Carte bancaire enregistrée" />
                    <CardBody>
                        {paymentMethod ? (
                            <div className="flex items-center gap-3 rounded-xl border border-ink-100 bg-ink-50/40 p-3 dark:border-ink-700/60 dark:bg-ink-900/30">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-ink-700 to-ink-900 font-mono text-[10px] font-bold text-white">
                                    {paymentMethod.brand.toUpperCase()}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="font-mono text-sm font-semibold tabular-nums text-ink-900 dark:text-white">
                                        •••• •••• •••• {paymentMethod.last4}
                                    </p>
                                    <p className="text-[11px] text-ink-500 dark:text-ink-400">
                                        Expire {paymentMethod.expires}
                                    </p>
                                </div>
                                <Button size="sm" variant="secondary">
                                    Modifier
                                </Button>
                            </div>
                        ) : (
                            <EmptyState
                                icon={<CardIcon />}
                                title="Aucun moyen de paiement"
                                description="Ajoutez une carte bancaire pour activer l'abonnement."
                                action={<Button size="sm">Ajouter une carte</Button>}
                            />
                        )}
                    </CardBody>
                </Card>

                {/* Plan tiers */}
                <div className="lg:col-span-3">
                    <h3 className="mb-3 text-sm font-semibold text-ink-700 dark:text-ink-200">Changer de plan</h3>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {tiers.map((t) => (
                            <TierCard key={t.key} tier={t} userCount={userCount} />
                        ))}
                    </div>
                </div>

                {/* Invoices */}
                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Historique des factures"
                        subtitle={`${invoices.length} facture(s)`}
                        action={
                            invoices.length > 0 && (
                                <Button size="sm" variant="secondary" leadingIcon={<DownloadIcon />}>
                                    Tout télécharger
                                </Button>
                            )
                        }
                    />
                    <CardBody className="px-2 py-2">
                        {invoices.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {invoices.map((inv) => (
                                    <li key={inv.id} className="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <p className="font-mono text-sm font-medium text-ink-900 dark:text-white">{inv.number}</p>
                                            <p className="text-[11px] text-ink-500 dark:text-ink-400">{inv.date}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="font-mono text-sm font-semibold tabular-nums text-ink-900 dark:text-white">
                                                {inv.amount} €
                                            </span>
                                            <Badge tone={INVOICE_TONE[inv.status]} size="sm" dot>
                                                {inv.status_label}
                                            </Badge>
                                            <a
                                                href={inv.pdf_url}
                                                className="rounded-md p-1.5 text-ink-500 hover:bg-ink-100 hover:text-ink-700 dark:text-ink-400 dark:hover:bg-ink-700/60 dark:hover:text-ink-100"
                                                aria-label="Télécharger la facture"
                                            >
                                                <DownloadIcon />
                                            </a>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                icon={<ReceiptIcon />}
                                title="Aucune facture"
                                description="Vos factures apparaîtront ici dès le premier mois d'abonnement."
                            />
                        )}
                    </CardBody>
                </Card>

                {/* Danger zone */}
                <Card className="lg:col-span-3 border-danger-200 bg-danger-50/30 dark:border-danger-700/40 dark:bg-danger-900/15">
                    <CardHeader
                        title="Zone sensible"
                        subtitle="Résiliation de l'abonnement"
                    />
                    <CardBody>
                        {confirmCancel ? (
                            <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-danger-900 dark:text-danger-200">
                                    Confirmer la résiliation ? L'accès reste actif jusqu'au {nextInvoiceDate ?? 'terme de la période'}.
                                </p>
                                <div className="flex gap-2">
                                    <Button variant="secondary" onClick={() => setConfirmCancel(false)}>
                                        Annuler
                                    </Button>
                                    <Button variant="danger" onClick={cancel}>
                                        Confirmer la résiliation
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-danger-900/80 dark:text-danger-200/80">
                                    Résiliation effective à la fin de la période en cours. Vos données restent
                                    consultables 90 jours puis sont anonymisées conformément à la politique RGPD.
                                </p>
                                <Button variant="danger" onClick={() => setConfirmCancel(true)}>
                                    Résilier l'abonnement
                                </Button>
                            </div>
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function Stat({ label, value, hint, accent }: { label: string; value: string; hint?: string; accent: 'brand' | 'sage' | 'neutral' }) {
    const accentColor: Record<typeof accent, string> = {
        brand: 'text-brand-600 dark:text-brand-300',
        sage: 'text-sage-600 dark:text-sage-300',
        neutral: 'text-ink-700 dark:text-ink-200',
    };
    return (
        <div>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
            <p className={cn('mt-1 font-mono text-2xl font-bold tabular-nums', accentColor[accent])}>{value}</p>
            {hint && <p className="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">{hint}</p>}
        </div>
    );
}

function TierCard({ tier, userCount }: { tier: Tier; userCount: number }) {
    const total = tier.price_per_user * userCount;
    const change = () => {
        router.post('/billing/change-plan', { tier: tier.key }, { preserveScroll: true });
    };
    return (
        <article
            className={cn(
                'relative rounded-2xl border p-5 transition-all',
                tier.is_current
                    ? 'border-brand-500 bg-brand-50/40 ring-2 ring-brand-500/20 dark:border-brand-400 dark:bg-brand-900/15'
                    : tier.is_recommended
                        ? 'border-brand-300 bg-white dark:border-brand-600/60 dark:bg-ink-800'
                        : 'border-ink-100 bg-white dark:border-ink-700/60 dark:bg-ink-800',
            )}
        >
            {tier.is_recommended && !tier.is_current && (
                <span className="absolute -top-2.5 left-4 inline-flex items-center rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white">
                    Recommandé
                </span>
            )}
            {tier.is_current && (
                <span className="absolute -top-2.5 left-4 inline-flex items-center rounded-full bg-sage-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white">
                    Plan actuel
                </span>
            )}
            <h3 className="text-base font-semibold text-ink-900 dark:text-white">{tier.label}</h3>
            <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">{tier.tagline}</p>
            <div className="mt-4">
                <p className="font-mono">
                    <span className="text-3xl font-bold tabular-nums text-ink-900 dark:text-white">{tier.price_per_user}</span>
                    <span className="text-sm text-ink-500 dark:text-ink-400"> € / user / mois</span>
                </p>
                {userCount > 0 && (
                    <p className="mt-1 text-[11px] text-ink-500 dark:text-ink-400">
                        Soit <span className="font-mono font-semibold text-ink-700 dark:text-ink-200">{total} €/mois</span> pour {userCount} utilisateurs
                    </p>
                )}
            </div>
            <ul className="mt-4 space-y-1.5">
                {tier.features.map((f, i) => (
                    <li key={i} className="flex items-start gap-2 text-xs text-ink-700 dark:text-ink-200">
                        <span className="mt-0.5 shrink-0 text-sage-600 dark:text-sage-400">
                            <CheckIcon />
                        </span>
                        <span>{f}</span>
                    </li>
                ))}
            </ul>
            <div className="mt-5">
                {tier.is_current ? (
                    <Button variant="secondary" disabled className="w-full">
                        Plan actuel
                    </Button>
                ) : (
                    <Button onClick={change} className="w-full" variant={tier.is_recommended ? 'primary' : 'secondary'}>
                        Choisir {tier.label}
                    </Button>
                )}
            </div>
        </article>
    );
}

function CheckIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function DownloadIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
        </svg>
    );
}
function CardIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <rect x="2" y="6" width="20" height="13" rx="2" />
            <line x1="2" y1="11" x2="22" y2="11" />
        </svg>
    );
}
function ReceiptIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path d="M4 2v20l4-2 4 2 4-2 4 2V2z" />
            <line x1="8" y1="8" x2="16" y2="8" />
            <line x1="8" y1="12" x2="16" y2="12" />
            <line x1="8" y1="16" x2="13" y2="16" />
        </svg>
    );
}
function ClockIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
