import { HoverCard } from '@/components/ui';
import { Link } from '@inertiajs/react';

interface Props {
    name: string;
    initials?: string;
    role?: string;
    email?: string;
    href?: string;
}

export function UserHoverCard({ name, initials, role, email, href }: Props) {
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
            trigger={<span className="cursor-default font-medium text-ink-900 underline decoration-dotted decoration-ink-300 underline-offset-4 hover:decoration-brand-400 dark:text-white dark:decoration-ink-600">{name}</span>}
            ariaLabel={`Aperçu du profil de ${name}`}
        >
            <div className="space-y-2.5">
                <div className="flex items-center gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">
                        {computedInitials}
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">{name}</p>
                        {role && <p className="text-[11px] text-ink-500 dark:text-ink-400">{role}</p>}
                    </div>
                </div>
                {email && (
                    <p className="font-mono text-[11px] text-ink-500 dark:text-ink-400">{email}</p>
                )}
                {href && (
                    <Link
                        href={href}
                        className="block rounded-md border border-ink-200 px-2 py-1.5 text-center text-[11px] font-medium text-brand-600 hover:border-brand-300 hover:bg-brand-50 dark:border-ink-700 dark:text-brand-400 dark:hover:border-brand-500 dark:hover:bg-brand-900/20"
                    >
                        Voir le profil →
                    </Link>
                )}
            </div>
        </HoverCard>
    );
}
