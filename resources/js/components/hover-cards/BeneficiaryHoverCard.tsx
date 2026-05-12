import { Badge, HoverCard } from '@/components/ui';
import { Link } from '@inertiajs/react';

interface Props {
    name: string;
    initials?: string;
    gir?: number | null;
    age?: number | null;
    city?: string | null;
    statusLabel?: string | null;
    href?: string;
}

export function BeneficiaryHoverCard({ name, initials, gir, age, city, statusLabel, href }: Props) {
    const computedInitials =
        initials ??
        name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);

    return (
        <HoverCard
            trigger={<span className="cursor-default font-medium text-ink-900 underline decoration-dotted decoration-sage-300 underline-offset-4 hover:decoration-sage-500 dark:text-white dark:decoration-sage-700">{name}</span>}
            ariaLabel={`Aperçu de ${name}`}
        >
            <div className="space-y-2.5">
                <div className="flex items-center gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-sage-500 to-sage-700 text-xs font-bold text-white">
                        {computedInitials}
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">{name}</p>
                        <p className="text-[11px] text-ink-500 dark:text-ink-400">
                            {age !== null && age !== undefined ? `${age} ans` : 'Âge non renseigné'}
                            {city ? ` · ${city}` : ''}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-1.5">
                    {gir !== null && gir !== undefined && (
                        <Badge tone="brand" size="xs">
                            GIR {gir}
                        </Badge>
                    )}
                    {statusLabel && (
                        <Badge tone="sage" size="xs" dot>
                            {statusLabel}
                        </Badge>
                    )}
                </div>

                {href && (
                    <Link
                        href={href}
                        className="block rounded-md border border-ink-200 px-2 py-1.5 text-center text-[11px] font-medium text-sage-700 hover:border-sage-300 hover:bg-sage-50 dark:border-ink-700 dark:text-sage-300 dark:hover:border-sage-600 dark:hover:bg-sage-900/20"
                    >
                        Voir le dossier →
                    </Link>
                )}
            </div>
        </HoverCard>
    );
}
