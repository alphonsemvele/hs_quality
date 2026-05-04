import { cn } from '@/lib/utils';
import { HTMLAttributes, ReactNode } from 'react';

type Tone = 'neutral' | 'brand' | 'sage' | 'warning' | 'danger' | 'info';
type Size = 'xs' | 'sm';

interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
    tone?: Tone;
    size?: Size;
    dot?: boolean;
    leadingIcon?: ReactNode;
}

const TONE: Record<Tone, { bg: string; text: string; ring: string; dot: string }> = {
    neutral: { bg: 'bg-ink-100 dark:bg-ink-700', text: 'text-ink-700 dark:text-ink-300', ring: 'ring-ink-200 dark:ring-ink-600', dot: 'bg-ink-400 dark:bg-ink-500' },
    brand: { bg: 'bg-brand-50 dark:bg-brand-900/30', text: 'text-brand-700 dark:text-brand-300', ring: 'ring-brand-100 dark:ring-brand-800/50', dot: 'bg-brand-500 dark:bg-brand-400' },
    sage: { bg: 'bg-sage-50 dark:bg-sage-900/30', text: 'text-sage-700 dark:text-sage-300', ring: 'ring-sage-200 dark:ring-sage-800/50', dot: 'bg-sage-500 dark:bg-sage-400' },
    warning: { bg: 'bg-warning-50 dark:bg-warning-900/30', text: 'text-warning-700 dark:text-warning-300', ring: 'ring-warning-200 dark:ring-warning-700/50', dot: 'bg-warning-500 dark:bg-warning-400' },
    danger: { bg: 'bg-danger-50 dark:bg-danger-900/30', text: 'text-danger-700 dark:text-danger-300', ring: 'ring-danger-200 dark:ring-danger-700/50', dot: 'bg-danger-500 dark:bg-danger-400' },
    info: { bg: 'bg-brand-50 dark:bg-brand-900/30', text: 'text-brand-700 dark:text-brand-300', ring: 'ring-brand-100 dark:ring-brand-800/50', dot: 'bg-brand-500 dark:bg-brand-400' },
};

const SIZE: Record<Size, string> = {
    xs: 'h-5 text-[11px] px-2 gap-1',
    sm: 'h-6 text-xs px-2.5 gap-1.5',
};

export function Badge({ tone = 'neutral', size = 'xs', dot, leadingIcon, className, children, ...rest }: BadgeProps) {
    const t = TONE[tone];
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full font-medium ring-1 ring-inset',
                t.bg,
                t.text,
                t.ring,
                SIZE[size],
                className,
            )}
            {...rest}
        >
            {dot && <span className={cn('size-1.5 rounded-full', t.dot)} aria-hidden />}
            {leadingIcon}
            {children}
        </span>
    );
}
