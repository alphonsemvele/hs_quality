import { Badge, HoverCard } from '@/components/ui';
import { Link } from '@inertiajs/react';

type Gravite = 'mineur' | 'significatif' | 'grave' | 'critique';
type Statut = 'declare' | 'en_analyse' | 'plan_actions' | 'clos';

interface Props {
    title: string;
    categorie?: string | null;
    gravite?: Gravite | string | null;
    statut?: Statut | string | null;
    occurredAt?: string | null;
    declaredBy?: string | null;
    href?: string;
}

const GRAVITE_TONE: Record<string, 'neutral' | 'warning' | 'danger'> = {
    mineur: 'neutral',
    significatif: 'warning',
    grave: 'warning',
    critique: 'danger',
};

const STATUT_TONE: Record<string, 'danger' | 'warning' | 'brand' | 'neutral'> = {
    declare: 'danger',
    en_analyse: 'warning',
    plan_actions: 'brand',
    clos: 'neutral',
};

const STATUT_LABEL: Record<string, string> = {
    declare: 'Déclaré',
    en_analyse: 'En analyse',
    plan_actions: "Plan d'actions",
    clos: 'Clos',
};

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    } catch {
        return iso;
    }
}

export function IncidentHoverCard({ title, categorie, gravite, statut, occurredAt, declaredBy, href }: Props) {
    const graviteTone = gravite ? GRAVITE_TONE[gravite] ?? 'neutral' : 'neutral';
    const statutTone = statut ? STATUT_TONE[statut] ?? 'neutral' : 'neutral';
    const statutLabel = statut ? STATUT_LABEL[statut] ?? statut : null;

    return (
        <HoverCard
            trigger={
                <span className="cursor-default font-medium text-ink-900 underline decoration-dotted decoration-danger-300 underline-offset-4 hover:decoration-danger-500 dark:text-white dark:decoration-danger-700">
                    {title}
                </span>
            }
            ariaLabel={`Aperçu de l'incident ${title}`}
        >
            <div className="space-y-2.5">
                <div className="flex items-start gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-danger-500 to-danger-700 text-white">
                        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" strokeLinecap="round" strokeLinejoin="round" />
                            <line x1="12" y1="9" x2="12" y2="13" strokeLinecap="round" />
                            <line x1="12" y1="17" x2="12.01" y2="17" strokeLinecap="round" />
                        </svg>
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">{title}</p>
                        {categorie && (
                            <p className="text-[11px] text-ink-500 dark:text-ink-400">Catégorie : {categorie}</p>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-1.5">
                    {gravite && (
                        <Badge tone={graviteTone} size="xs" dot>
                            {gravite}
                        </Badge>
                    )}
                    {statutLabel && (
                        <Badge tone={statutTone} size="xs">
                            {statutLabel}
                        </Badge>
                    )}
                </div>

                <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px]">
                    {occurredAt && (
                        <>
                            <dt className="text-ink-500 dark:text-ink-400">Survenu</dt>
                            <dd className="font-mono text-ink-700 dark:text-ink-200">{formatDate(occurredAt)}</dd>
                        </>
                    )}
                    {declaredBy && (
                        <>
                            <dt className="text-ink-500 dark:text-ink-400">Déclaré par</dt>
                            <dd className="text-ink-700 dark:text-ink-200">{declaredBy}</dd>
                        </>
                    )}
                </dl>

                {href && (
                    <Link
                        href={href}
                        className="block rounded-md border border-ink-200 px-2 py-1.5 text-center text-[11px] font-medium text-danger-700 hover:border-danger-300 hover:bg-danger-50 dark:border-ink-700 dark:text-danger-300 dark:hover:border-danger-600 dark:hover:bg-danger-900/20"
                    >
                        Ouvrir l'incident →
                    </Link>
                )}
            </div>
        </HoverCard>
    );
}
