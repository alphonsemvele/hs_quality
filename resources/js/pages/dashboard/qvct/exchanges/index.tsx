import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Form } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

type ExchangeStatus = 'requested' | 'accepted' | 'scheduled' | 'closed' | 'cancelled';

interface InboxItem {
    id: string;
    from: string;
    subject: string;
    reason: string;
    status: ExchangeStatus;
    status_label: string;
    requested_at: string;
    scheduled_at: string | null;
}

interface OutboxItem {
    id: string;
    to: string;
    subject: string;
    status: ExchangeStatus;
    status_label: string;
    requested_at: string;
    scheduled_at: string | null;
}

interface Props {
    inbox: InboxItem[];
    outbox: OutboxItem[];
}

type Tab = 'inbox' | 'outbox' | 'new';

const STATUS_TONE: Record<ExchangeStatus, 'warning' | 'brand' | 'sage' | 'neutral' | 'danger'> = {
    requested: 'warning',
    accepted: 'brand',
    scheduled: 'sage',
    closed: 'neutral',
    cancelled: 'danger',
};

const REASONS = [
    'Charge de travail',
    'Organisation',
    'Conflit collègue',
    'Conflit hiérarchique',
    'Évolution / carrière',
    'Santé / fatigue',
    'Autre',
];

export default function ExchangesIndex({ inbox = [], outbox = [] }: Partial<Props>) {
    const [tab, setTab] = useState<Tab>('inbox');
    const newCount = inbox.filter((i) => i.status === 'requested').length;

    return (
        <DashboardLayout title="Demandes d'échange" subtitle="Demandes et entretiens">
            <PageHeader
                title="Demandes d'échange"
                subtitle="Sollicitations directes entre collaborateurs et RH / hiérarchie"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Demandes' },
                ]}
            />

            {/* Tabs */}
            <div className="mb-5 flex gap-1 rounded-lg border border-ink-200 bg-white p-1 dark:border-ink-700 dark:bg-ink-800">
                <TabButton active={tab === 'inbox'} onClick={() => setTab('inbox')} count={newCount}>
                    Reçues
                </TabButton>
                <TabButton active={tab === 'outbox'} onClick={() => setTab('outbox')}>
                    Envoyées
                </TabButton>
                <TabButton active={tab === 'new'} onClick={() => setTab('new')}>
                    Nouvelle demande
                </TabButton>
            </div>

            {tab === 'inbox' && <InboxTab items={inbox} />}
            {tab === 'outbox' && <OutboxTab items={outbox} />}
            {tab === 'new' && <NewTab />}
        </DashboardLayout>
    );
}

function TabButton({
    active,
    onClick,
    count,
    children,
}: {
    active: boolean;
    onClick: () => void;
    count?: number;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors sm:flex-initial',
                active
                    ? 'bg-brand-600 text-white shadow-sm'
                    : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700/60',
            )}
        >
            {children}
            {count !== undefined && count > 0 && (
                <span
                    className={cn(
                        'inline-flex min-w-[18px] items-center justify-center rounded-full px-1.5 py-0.5 font-mono text-[10px] font-bold',
                        active ? 'bg-white/20 text-white' : 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300',
                    )}
                >
                    {count}
                </span>
            )}
        </button>
    );
}

function InboxTab({ items }: { items: InboxItem[] }) {
    if (items.length === 0) {
        return (
            <Card>
                <EmptyState icon={<InboxIcon />} title="Pas de demande reçue" description="Aucun collaborateur ne vous a sollicité pour le moment." />
            </Card>
        );
    }
    return (
        <ul className="space-y-3">
            {items.map((i) => (
                <li key={i.id}>
                    <Card>
                        <CardBody>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{i.subject}</h3>
                                        <Badge tone={STATUS_TONE[i.status]} size="sm" dot>
                                            {i.status_label}
                                        </Badge>
                                    </div>
                                    <p className="mt-1 text-xs text-ink-600 dark:text-ink-300">
                                        <span className="font-medium">{i.from}</span> · Motif : {i.reason}
                                    </p>
                                    <p className="mt-0.5 font-mono text-[11px] text-ink-400 dark:text-ink-500">
                                        {i.requested_at}
                                        {i.scheduled_at && ` · Planifié ${i.scheduled_at}`}
                                    </p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {i.status === 'requested' && (
                                        <>
                                            <Button size="sm" variant="secondary">Refuser</Button>
                                            <Button size="sm">Accepter</Button>
                                        </>
                                    )}
                                    {i.status === 'accepted' && <Button size="sm">Planifier</Button>}
                                    {i.status === 'scheduled' && (
                                        <Button size="sm" variant="secondary">
                                            Marquer fait
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </CardBody>
                    </Card>
                </li>
            ))}
        </ul>
    );
}

function OutboxTab({ items }: { items: OutboxItem[] }) {
    if (items.length === 0) {
        return (
            <Card>
                <EmptyState icon={<SendIcon />} title="Aucune demande envoyée" description="Vous n'avez pas encore sollicité d'échange." />
            </Card>
        );
    }
    return (
        <ul className="space-y-3">
            {items.map((i) => (
                <li key={i.id}>
                    <Card>
                        <CardBody>
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{i.subject}</h3>
                                        <Badge tone={STATUS_TONE[i.status]} size="sm" dot>
                                            {i.status_label}
                                        </Badge>
                                    </div>
                                    <p className="mt-1 text-xs text-ink-600 dark:text-ink-300">
                                        Destinataire : <span className="font-medium">{i.to}</span>
                                    </p>
                                    <p className="mt-0.5 font-mono text-[11px] text-ink-400 dark:text-ink-500">
                                        {i.requested_at}
                                        {i.scheduled_at && ` · Planifié ${i.scheduled_at}`}
                                    </p>
                                </div>
                            </div>
                        </CardBody>
                    </Card>
                </li>
            ))}
        </ul>
    );
}

function NewTab() {
    return (
        <Card className="mx-auto max-w-2xl">
            <CardHeader
                title="Solliciter un échange"
                subtitle="Demandez un temps d'échange avec votre RH ou votre manager"
            />
            <CardBody>
                <Form action="/qvct/exchanges" method="post" resetOnSuccess>
                    {({ processing, errors }) => (
                        <div className="space-y-4">
                            <div>
                                <label htmlFor="addressee" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                    Destinataire *
                                </label>
                                <select
                                    id="addressee"
                                    name="addressee_role"
                                    required
                                    className="h-10 w-full rounded-lg border border-ink-200 bg-white px-3 text-sm text-ink-900 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                                >
                                    <option value="rh">Mon référent·e RH</option>
                                    <option value="coordinateur">Mon coordinateur·rice</option>
                                    <option value="dirigeant">La direction</option>
                                </select>
                            </div>

                            <div>
                                <label htmlFor="reason" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                    Motif *
                                </label>
                                <select
                                    id="reason"
                                    name="reason"
                                    required
                                    className="h-10 w-full rounded-lg border border-ink-200 bg-white px-3 text-sm text-ink-900 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                                >
                                    {REASONS.map((r) => (
                                        <option key={r} value={r}>
                                            {r}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="message" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                    Message (optionnel)
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows={5}
                                    maxLength={2000}
                                    placeholder="Ce que vous souhaitez aborder, sans contrainte de précision."
                                    className="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-white dark:placeholder:text-ink-500"
                                />
                                {errors.message && (
                                    <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.message}</p>
                                )}
                            </div>

                            <p className="rounded-lg border border-brand-200 bg-brand-50/50 px-3 py-2 text-[11px] text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                                Votre demande est traitée avec confidentialité. Aucun motif détaillé n'est requis pour
                                solliciter un échange.
                            </p>

                            <div className="flex justify-end">
                                <Button type="submit" loading={processing}>
                                    Envoyer la demande
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </CardBody>
        </Card>
    );
}

function InboxIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
            <path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z" />
        </svg>
    );
}
function SendIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <line x1="22" y1="2" x2="11" y2="13" />
            <polygon points="22 2 15 22 11 13 2 9 22 2" />
        </svg>
    );
}
