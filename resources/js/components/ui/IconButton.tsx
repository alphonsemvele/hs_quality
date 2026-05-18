import { cn } from '@/lib/utils';
import { ButtonHTMLAttributes, forwardRef, ReactNode } from 'react';

type Variant = 'ghost' | 'subtle' | 'danger' | 'brand';
type Size = 'xs' | 'sm' | 'md';

interface IconButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children' | 'aria-label'> {
    /** SVG icon. Should be sized via Tailwind (`size-4` / `size-5`). */
    icon: ReactNode;
    /** REQUIRED — describes the action for screen readers. */
    'aria-label': string;
    variant?: Variant;
    size?: Size;
}

const VARIANT: Record<Variant, string> = {
    ghost:
        'bg-transparent text-ink-500 hover:bg-ink-100 hover:text-ink-700 active:bg-ink-200 disabled:text-ink-300 dark:text-ink-400 dark:hover:bg-ink-700 dark:hover:text-white dark:active:bg-ink-600 dark:disabled:text-ink-600',
    subtle:
        'bg-ink-100 text-ink-700 hover:bg-ink-200 active:bg-ink-300 disabled:bg-ink-50 disabled:text-ink-400 dark:bg-ink-700 dark:text-ink-200 dark:hover:bg-ink-600 dark:active:bg-ink-500 dark:disabled:bg-ink-800 dark:disabled:text-ink-500',
    danger:
        'bg-transparent text-danger-500 hover:bg-danger-50 hover:text-danger-700 active:bg-danger-100 disabled:text-ink-300 dark:text-danger-400 dark:hover:bg-danger-900/30 dark:hover:text-danger-300',
    brand:
        'bg-transparent text-brand-600 hover:bg-brand-50 hover:text-brand-700 active:bg-brand-100 disabled:text-ink-300 dark:text-brand-400 dark:hover:bg-brand-900/30 dark:hover:text-brand-300',
};

const SIZE: Record<Size, string> = {
    xs: 'size-7 rounded-md',
    sm: 'size-8 rounded-md',
    md: 'size-9 rounded-lg',
};

/**
 * Icon-only button. Same focus ring, hover, disabled and dark-mode
 * conventions as `Button`, but optimised for square trigger surfaces
 * (table row actions, toolbar icons, dropdown openers).
 *
 * `aria-label` is required (TypeScript-enforced) because the visible
 * label is missing — without it the button is invisible to screen
 * readers.
 *
 * @example
 *   <IconButton aria-label="Supprimer" variant="danger" icon={<TrashIcon />} onClick={onDelete} />
 */
export const IconButton = forwardRef<HTMLButtonElement, IconButtonProps>(function IconButton(
    { icon, variant = 'ghost', size = 'sm', className, type = 'button', ...rest },
    ref,
) {
    return (
        <button
            ref={ref}
            type={type}
            className={cn(
                'inline-flex items-center justify-center transition-colors disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 focus-visible:ring-offset-1 dark:focus-visible:ring-brand-400/40 dark:focus-visible:ring-offset-ink-900',
                VARIANT[variant],
                SIZE[size],
                className,
            )}
            {...rest}
        >
            {icon}
        </button>
    );
});
