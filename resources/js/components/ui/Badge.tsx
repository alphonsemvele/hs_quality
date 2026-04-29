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
    neutral: { bg: 'bg-ink-100', text: 'text-ink-700', ring: 'ring-ink-200', dot: 'bg-ink-400' },
    brand: { bg: 'bg-brand-50', text: 'text-brand-700', ring: 'ring-brand-100', dot: 'bg-brand-500' },
    sage: { bg: 'bg-sage-50', text: 'text-sage-700', ring: 'ring-sage-200', dot: 'bg-sage-500' },
    warning: { bg: 'bg-warning-50', text: 'text-warning-700', ring: 'ring-warning-200', dot: 'bg-warning-500' },
    danger: { bg: 'bg-danger-50', text: 'text-danger-700', ring: 'ring-danger-200', dot: 'bg-danger-500' },
    info: { bg: 'bg-brand-50', text: 'text-brand-700', ring: 'ring-brand-100', dot: 'bg-brand-500' },
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
