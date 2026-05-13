import { Badge, Card, CardBody, CardHeader, EmptyState, PageHeader, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../dashboard/layout';

interface PerStructureFlag {
    structure_id: number;
    structure_code: string;
    structure_name: string;
    structure_status: string;
    active: boolean;
}

interface FlagSummary {
    key: string;
    short_key: string;
    label: string;
    description: string;
    default_on: boolean;
    per_structure: PerStructureFlag[];
    active_count: number;
    total_count: number;
}

interface Props {
    flags: FlagSummary[];
}

export default function FeatureFlagsIndex({ flags }: Props) {
    const [pendingKey, setPendingKey] = useState<string | null>(null);

    const toggle = (flagKey: string, structureId: number, currentlyActive: boolean) => {
        const rowKey = `${flagKey}::${structureId}`;
        setPendingKey(rowKey);
        router.post(
            '/admin/feature-flags/toggle',
            {
                flag: flagKey,
                structure_id: structureId,
                active: !currentlyActive,
            },
            {
                preserveScroll: true,
                onFinish: () => setPendingKey(null),
            },
        );
    };

    return (
        <DashboardLayout title="Feature flags" subtitle="Pilotage Pennant par tenant — administration plateforme">
            <PageHeader
                title="Feature flags"
                subtitle="Activer ou désactiver les fonctionnalités par tenant — utile pour les pilotes, les bêta tests et la lutte contre l'alert fatigue."
                breadcrumb={[
                    { label: 'Plateforme', href: '/admin' },
                    { label: 'Feature flags' },
                ]}
            />

            {flags.length === 0 ? (
                <Card>
                    <EmptyState
                        title="Aucun feature flag défini"
                        description="Définissez une classe sous app/Features/ puis référencez-la dans FeatureFlagController::FLAGS."
                    />
                </Card>
            ) : (
                <div className="space-y-6">
                    {flags.map((flag) => (
                        <Card key={flag.key}>
                            <CardHeader>
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="text-base font-semibold text-ink-900 dark:text-white">
                                                {flag.label}
                                            </h3>
                                            <Badge tone={flag.default_on ? 'sage' : 'neutral'} size="xs">
                                                Défaut {flag.default_on ? 'ON' : 'OFF'}
                                            </Badge>
                                            <span className="rounded-md bg-ink-100 px-2 py-0.5 font-mono text-[11px] text-ink-600 dark:bg-ink-700 dark:text-ink-300">
                                                {flag.short_key}
                                            </span>
                                        </div>
                                        <p className="mt-2 max-w-3xl text-sm leading-relaxed text-ink-600 dark:text-ink-400">
                                            {flag.description}
                                        </p>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        <p className="font-mono text-2xl font-bold tracking-tight text-ink-900 dark:text-white">
                                            {flag.active_count}
                                            <span className="text-base font-normal text-ink-400 dark:text-ink-500">
                                                {' '}
                                                / {flag.total_count}
                                            </span>
                                        </p>
                                        <p className="text-[11px] uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                            tenants actifs
                                        </p>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardBody className="p-0">
                                <Table>
                                    <THead>
                                        <Tr>
                                            <Th hint="Tenant client (raison sociale + code court interne).">Structure</Th>
                                            <Th hint="État opérationnel du tenant. Un tenant suspendu ne peut pas se connecter — le flag reste mémorisé pour quand l'accès reprend.">
                                                Statut tenant
                                            </Th>
                                            <Th
                                                hint="État du flag pour ce tenant. Cliquez pour basculer. Le changement est immédiat — aucune purge de cache nécessaire."
                                                className="text-right"
                                            >
                                                Flag
                                            </Th>
                                        </Tr>
                                    </THead>
                                    <TBody>
                                        {flag.per_structure.map((row) => {
                                            const rowKey = `${flag.key}::${row.structure_id}`;
                                            const pending = pendingKey === rowKey;
                                            return (
                                                <Tr key={row.structure_id}>
                                                    <Td>
                                                        <p className="font-medium text-ink-900 dark:text-white">{row.structure_name}</p>
                                                        <p className="font-mono text-xs text-ink-500 dark:text-ink-400">
                                                            {row.structure_code}
                                                        </p>
                                                    </Td>
                                                    <Td>
                                                        <Badge tone={row.structure_status === 'active' ? 'sage' : 'warning'} size="sm" dot>
                                                            {row.structure_status === 'active' ? 'Actif' : 'Suspendu'}
                                                        </Badge>
                                                    </Td>
                                                    <Td className="text-right">
                                                        <ToggleSwitch
                                                            active={row.active}
                                                            disabled={pending}
                                                            onChange={() => toggle(flag.key, row.structure_id, row.active)}
                                                            ariaLabel={`${row.active ? 'Désactiver' : 'Activer'} ${flag.short_key} pour ${row.structure_name}`}
                                                        />
                                                    </Td>
                                                </Tr>
                                            );
                                        })}
                                    </TBody>
                                </Table>
                            </CardBody>
                        </Card>
                    ))}
                </div>
            )}
        </DashboardLayout>
    );
}

function ToggleSwitch({
    active,
    disabled,
    onChange,
    ariaLabel,
}: {
    active: boolean;
    disabled: boolean;
    onChange: () => void;
    ariaLabel: string;
}) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={active}
            aria-label={ariaLabel}
            onClick={onChange}
            disabled={disabled}
            className={
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 ' +
                (disabled ? 'cursor-wait opacity-60 ' : '') +
                (active
                    ? 'bg-sage-500 dark:bg-sage-600'
                    : 'bg-ink-200 dark:bg-ink-700')
            }
        >
            <span
                className={
                    'inline-block size-5 transform rounded-full bg-white shadow-sm transition-transform ' +
                    (active ? 'translate-x-5' : 'translate-x-0.5')
                }
            />
        </button>
    );
}
