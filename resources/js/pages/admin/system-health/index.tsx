import { Badge, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { router } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

type Status = 'healthy' | 'warning' | 'danger' | 'unknown';

interface QueueMetrics {
    driver: string;
    default_depth: number | null;
    high_depth: number | null;
    status: Status;
    error: string | null;
}

interface DatabaseMetrics {
    driver: string;
    latency_ms: number | null;
    status: Status;
    error: string | null;
}

interface RedisMetrics {
    reachable: boolean;
    latency_ms: number | null;
    ping: string | null;
    status: Status;
    error: string | null;
}

interface FailedJob {
    id: number;
    connection: string;
    queue: string;
    exception_class: string;
    failed_at: string;
}

interface FailedJobsMetrics {
    total: number | null;
    last_24h: number | null;
    recent: FailedJob[];
    status: Status;
    error: string | null;
}

interface Snapshot {
    collected_at: string;
    queue: QueueMetrics;
    database: DatabaseMetrics;
    redis: RedisMetrics;
    failed_jobs: FailedJobsMetrics;
}

interface Props {
    snapshot: Snapshot;
}

const STATUS_TONE: Record<Status, 'sage' | 'warning' | 'danger' | 'neutral'> = {
    healthy: 'sage',
    warning: 'warning',
    danger: 'danger',
    unknown: 'neutral',
};

const STATUS_LABEL: Record<Status, string> = {
    healthy: 'Sain',
    warning: 'À surveiller',
    danger: 'Critique',
    unknown: 'Indisponible',
};

function formatRelative(iso: string): string {
    try {
        return new Date(iso).toLocaleString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return iso;
    }
}

function tone(status: Status): 'sage' | 'warning' | 'danger' | 'neutral' {
    return STATUS_TONE[status];
}

function kpiTone(status: Status): 'sage' | 'warning' | 'danger' | 'neutral' | 'brand' {
    if (status === 'healthy') return 'sage';
    if (status === 'warning') return 'warning';
    if (status === 'danger') return 'danger';
    return 'neutral';
}

export default function SystemHealthIndex({ snapshot }: Props) {
    const refresh = () => router.reload({ only: ['snapshot'] });

    return (
        <DashboardLayout title="Santé système" subtitle="Métriques infrastructure temps réel — administration plateforme">
            <PageHeader
                title="Santé système"
                subtitle={`Snapshot pris le ${formatRelative(snapshot.collected_at)}`}
                breadcrumb={[
                    { label: 'Plateforme', href: '/admin' },
                    { label: 'Santé système' },
                ]}
                actions={
                    <button
                        type="button"
                        onClick={refresh}
                        className="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        <RefreshIcon />
                        Rafraîchir
                    </button>
                }
            />

            {/* ───── KPI row ───── */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="File d'attente par défaut"
                    value={snapshot.queue.default_depth ?? '—'}
                    sub={snapshot.queue.high_depth !== null ? `${snapshot.queue.high_depth} jobs prioritaires` : '—'}
                    icon={<LayersIcon />}
                    tone={kpiTone(snapshot.queue.status)}
                    hint="Nombre de jobs en attente sur la queue Redis « default ». Les seuils d'alerte sont 500 (warning) et 2000 (danger). Surveillez cette métrique pendant les pics d'activité (matinée, fin de tournée)."
                />
                <KpiCard
                    label="Latence base de données"
                    value={snapshot.database.latency_ms !== null ? `${snapshot.database.latency_ms} ms` : '—'}
                    sub={`Driver ${snapshot.database.driver}`}
                    icon={<DatabaseIcon />}
                    tone={kpiTone(snapshot.database.status)}
                    hint="Temps de réponse d'une requête SELECT triviale. Au-delà de 200 ms, suspecter un verrou, une migration en cours ou une instance saturée."
                />
                <KpiCard
                    label="Latence Redis"
                    value={snapshot.redis.latency_ms !== null ? `${snapshot.redis.latency_ms} ms` : '—'}
                    sub={snapshot.redis.ping ? `Réponse : ${snapshot.redis.ping}` : 'Injoignable'}
                    icon={<BoltIcon />}
                    tone={kpiTone(snapshot.redis.status)}
                    hint="Temps de réponse d'un PING Redis. Redis sert à la fois pour le cache, les sessions, la queue et Reverb — toute latence > 100 ms impacte l'ensemble de la plateforme."
                />
                <KpiCard
                    label="Jobs échoués (24 h)"
                    value={snapshot.failed_jobs.last_24h ?? '—'}
                    sub={snapshot.failed_jobs.total !== null ? `Total historique : ${snapshot.failed_jobs.total}` : '—'}
                    icon={<AlertIcon />}
                    tone={kpiTone(snapshot.failed_jobs.status)}
                    hint="Jobs ayant terminé en erreur sur les 24 dernières heures. Au-delà de 10 par jour, ouvrir la liste pour identifier la classe d'exception dominante."
                />
            </div>

            {/* ───── Status summary ───── */}
            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatusCard
                    title="File d'attente"
                    status={snapshot.queue.status}
                    error={snapshot.queue.error}
                    bullets={[
                        `Driver : ${snapshot.queue.driver}`,
                        snapshot.queue.default_depth !== null ? `Queue default : ${snapshot.queue.default_depth} jobs` : 'default indisponible',
                        snapshot.queue.high_depth !== null ? `Queue high : ${snapshot.queue.high_depth} jobs` : 'high indisponible',
                    ]}
                />
                <StatusCard
                    title="Base de données"
                    status={snapshot.database.status}
                    error={snapshot.database.error}
                    bullets={[
                        `Driver : ${snapshot.database.driver}`,
                        snapshot.database.latency_ms !== null ? `Latence : ${snapshot.database.latency_ms} ms` : 'Latence indisponible',
                    ]}
                />
                <StatusCard
                    title="Redis"
                    status={snapshot.redis.status}
                    error={snapshot.redis.error}
                    bullets={[
                        snapshot.redis.reachable ? 'Connexion établie' : 'Pas de connexion',
                        snapshot.redis.latency_ms !== null ? `Latence : ${snapshot.redis.latency_ms} ms` : 'Latence indisponible',
                    ]}
                />
                <StatusCard
                    title="Jobs échoués"
                    status={snapshot.failed_jobs.status}
                    error={snapshot.failed_jobs.error}
                    bullets={[
                        snapshot.failed_jobs.last_24h !== null ? `Dernières 24 h : ${snapshot.failed_jobs.last_24h}` : '24 h indisponible',
                        snapshot.failed_jobs.total !== null ? `Total : ${snapshot.failed_jobs.total}` : 'Total indisponible',
                    ]}
                />
            </div>

            {/* ───── Recent failed jobs ───── */}
            <div className="mt-6">
                <Card>
                    <CardHeader>
                        <h3 className="text-sm font-semibold text-ink-900 dark:text-white">5 derniers jobs échoués</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Tronqué à la première ligne de l'exception. Utilisez {' '}
                            <span className="font-mono">php artisan queue:failed --queue=…</span> pour le détail complet.
                        </p>
                    </CardHeader>
                    <CardBody className="p-0">
                        {snapshot.failed_jobs.recent.length === 0 ? (
                            <EmptyState
                                title="Aucun job échoué"
                                description="L'historique récent est vide — bon signe sur la stabilité des workers."
                            />
                        ) : (
                            <Table>
                                <THead>
                                    <Tr>
                                        <Th hint="Identifiant interne du job dans la table failed_jobs.">ID</Th>
                                        <Th hint="Connexion Laravel utilisée (généralement « redis »).">Connexion</Th>
                                        <Th hint="Nom de la queue dans laquelle le job a été enregistré.">Queue</Th>
                                        <Th hint="Classe d'exception levée par le job, première ligne du stack trace.">
                                            Exception
                                        </Th>
                                        <Th hint="Horodatage UTC de l'échec.">Échec</Th>
                                    </Tr>
                                </THead>
                                <TBody>
                                    {snapshot.failed_jobs.recent.map((job) => (
                                        <Tr key={job.id}>
                                            <Td className="font-mono text-xs text-ink-600 dark:text-ink-400">
                                                #{job.id}
                                            </Td>
                                            <Td className="text-sm">{job.connection}</Td>
                                            <Td className="text-sm">{job.queue}</Td>
                                            <Td className="max-w-md truncate text-sm text-ink-700 dark:text-ink-300" title={job.exception_class}>
                                                {job.exception_class}
                                            </Td>
                                            <Td className="font-mono text-xs text-ink-500 dark:text-ink-400">
                                                {job.failed_at}
                                            </Td>
                                        </Tr>
                                    ))}
                                </TBody>
                            </Table>
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function StatusCard({
    title,
    status,
    bullets,
    error,
}: {
    title: string;
    status: Status;
    bullets: string[];
    error: string | null;
}) {
    return (
        <div className="rounded-2xl border border-ink-100 bg-white p-5 dark:border-ink-700/60 dark:bg-ink-800">
            <div className="flex items-center justify-between">
                <h4 className="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{title}</h4>
                <Badge tone={tone(status)} size="sm" dot={status !== 'unknown'}>
                    {STATUS_LABEL[status]}
                </Badge>
            </div>
            <ul className="mt-3 space-y-1.5 text-xs leading-relaxed text-ink-600 dark:text-ink-300">
                {bullets.map((b) => (
                    <li key={b} className="flex items-start gap-2">
                        <span className="mt-1 size-1 shrink-0 rounded-full bg-ink-300 dark:bg-ink-600" />
                        <span>{b}</span>
                    </li>
                ))}
            </ul>
            {error && (
                <p className="mt-3 rounded-lg bg-danger-50 px-3 py-2 text-xs text-danger-700 dark:bg-danger-900/30 dark:text-danger-300">
                    {error}
                </p>
            )}
        </div>
    );
}

// ─── Icons ──────────────────────────────────────────────────────────────────
function RefreshIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
            <path d="M3 12a9 9 0 0114.5-7M21 12a9 9 0 01-14.5 7M21 3v6h-6M3 21v-6h6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function LayersIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
        </svg>
    );
}
function DatabaseIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
        </svg>
    );
}
function BoltIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" strokeLinecap="round" strokeLinejoin="round" />
            <line x1="12" y1="9" x2="12" y2="13" strokeLinecap="round" />
            <line x1="12" y1="17" x2="12.01" y2="17" strokeLinecap="round" />
        </svg>
    );
}
