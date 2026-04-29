import { cn } from '@/lib/utils';
import { ButtonHTMLAttributes, forwardRef, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'subtle';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    leadingIcon?: ReactNode;
    trailingIcon?: ReactNode;
    loading?: boolean;
}

const VARIANT: Record<Variant, string> = {
    primary:
        'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800 disabled:bg-ink-200 disabled:text-ink-400',
    secondary:
        'bg-white text-ink-900 border border-ink-200 hover:bg-ink-50 hover:border-ink-300 active:bg-ink-100 disabled:bg-ink-100 disabled:text-ink-400 disabled:border-ink-200',
    ghost: 'bg-transparent text-ink-700 hover:bg-ink-100 active:bg-ink-200 disabled:text-ink-400',
    danger: 'bg-danger-600 text-white hover:bg-danger-700 active:bg-danger-700 disabled:bg-ink-200 disabled:text-ink-400',
    subtle: 'bg-brand-50 text-brand-700 hover:bg-brand-100 active:bg-brand-200 disabled:bg-ink-100 disabled:text-ink-400',
};

const SIZE: Record<Size, string> = {
    sm: 'h-8 px-3 text-xs gap-1.5 rounded-md',
    md: 'h-10 px-4 text-sm gap-2 rounded-lg',
    lg: 'h-12 px-5 text-sm gap-2 rounded-lg',
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    { variant = 'primary', size = 'md', leadingIcon, trailingIcon, loading, disabled, className, children, ...rest },
    ref,
) {
    return (
        <button
            ref={ref}
            disabled={disabled || loading}
            className={cn(
                'inline-flex items-center justify-center font-medium transition-colors disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 focus-visible:ring-offset-1',
                VARIANT[variant],
                SIZE[size],
                className,
            )}
            {...rest}
        >
            {loading ? (
                <span
                    className="inline-block size-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                    aria-hidden
                />
            ) : (
                leadingIcon
            )}
            {children}
            {!loading && trailingIcon}
        </button>
    );
});
