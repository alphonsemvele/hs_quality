import { Badge, Button, Card, CardBody, ConfirmDialog, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface Grid {
    id: string;
    title: string;
    description: string | null;
    source: string;
    is_active: boolean;
}

interface Props {
    grid: Grid;
}

const SOURCE_META: Record<string, { label: string; tone: 'sage' | 'brand' | 'warning' | 'neutral' }> = {
    has: { label: 'HAS', tone: 'sage' },
    iso_9001: { label: 'ISO 9001', tone: 'brand' },
    afnor_x50_056: { label: 'AFNOR NF X50-056', tone: 'warning' },
    custom: { label: 'Référentiel interne', tone: 'neutral' },
};

export default function AuditGridEdit({ grid }: Props) {
    const meta = SOURCE_META[grid.source] ?? { label: grid.source, tone: 'neutral' as const };
    const [confirmDelete, setConfirmDelete] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        title: grid.title,
        description: grid.description ?? '',
        is_active: grid.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/audits/grids/${grid.id}`);
    };

    const destroy = () => {
        router.delete(`/audits/grids/${grid.id}`);
    };

    return (
        <DashboardLayout title={`Éditer — ${grid.title}`} subtitle="Audit grid edit">
            <PageHeader
                title="Modifier le référentiel"
                subtitle="Mettez à jour le nom, la description ou archivez ce référentiel."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Référentiels', href: '/audits/grids' },
                    { label: grid.title, href: `/audits/grids/${grid.id}` },
                    { label: 'Éditer' },
                ]}
            />

            <form onSubmit={submit} className="max-w-2xl">
                <Card>
                    <CardBody className="space-y-5">
                        <div className="flex items-center gap-2 text-xs text-ink-500 dark:text-ink-400">
                            <span>Source :</span>
                            <Badge tone={meta.tone} size="sm">
                                {meta.label}
                            </Badge>
                        </div>

                        <FormField label="Nom du référentiel" required error={errors.title}>
                            <Input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                maxLength={255}
                                required
                            />
                        </FormField>

                        <FormField label="Description" error={errors.description}>
                            <Textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={5}
                                maxLength={5000}
                            />
                        </FormField>

                        <label className="flex items-center gap-3 rounded-lg border border-ink-200 p-3 text-sm dark:border-ink-700">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                            />
                            <span>
                                <span className="font-medium text-ink-900 dark:text-white">Référentiel actif</span>
                                <span className="block text-xs text-ink-500 dark:text-ink-400">
                                    Décochez pour le retirer de la bibliothèque sans le supprimer.
                                </span>
                            </span>
                        </label>
                    </CardBody>
                </Card>

                <div className="mt-5 flex items-center justify-between gap-2">
                    {grid.source === 'custom' ? (
                        <Button type="button" variant="danger" onClick={() => setConfirmDelete(true)}>
                            Archiver
                        </Button>
                    ) : (
                        <span className="text-xs text-ink-500 dark:text-ink-400">
                            Les référentiels standards (HAS, ISO, AFNOR) ne peuvent pas être archivés.
                        </span>
                    )}
                    <div className="flex items-center gap-2">
                        <Link href={`/audits/grids/${grid.id}`}>
                            <Button type="button" variant="ghost">
                                Annuler
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing || data.title.trim().length === 0}>
                            {processing ? 'Enregistrement…' : 'Enregistrer'}
                        </Button>
                    </div>
                </div>
            </form>

            <ConfirmDialog
                open={confirmDelete}
                onClose={() => setConfirmDelete(false)}
                onConfirm={destroy}
                title="Archiver ce référentiel ?"
                description="Le référentiel sera retiré de la bibliothèque. Les audits déjà conduits restent intacts. Vous pouvez le restaurer depuis le registre d'audit."
                confirmLabel="Archiver"
                tone="danger"
            />
        </DashboardLayout>
    );
}
