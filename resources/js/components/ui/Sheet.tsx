import { cn } from '@/lib/utils';
import { ReactNode, useEffect, useRef } from 'react';

type Side = 'right' | 'left' | 'bottom';
type Size = 'sm' | 'md' | 'lg' | 'xl';

interface SheetProps {
    open: boolean;
    onClose: () => void;
    side?: Side;
    size?: Size;
    title?: string;
    description?: string;
    icon?: ReactNode;
    iconTone?: 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';
    footer?: ReactNode;
    hideCloseButton?: boolean;
    closeOnBackdrop?: boolean;
    children: ReactNode;
}

const SIZE_RIGHT_LEFT: Record<Size, string> = {
    sm: 'w-full sm:max-w-sm',
    md: 'w-full sm:max-w-md',
    lg: 'w-full sm:max-w-lg',
    xl: 'w-full sm:max-w-2xl',
};

const SIZE_BOTTOM: Record<Size, string> = {
    sm: 'max-h-[40vh]',
    md: 'max-h-[60vh]',
    lg: 'max-h-[80vh]',
    xl: 'max-h-[90vh]',
};

const ICON_TONE = {
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300',
    sage: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
    warning: 'bg-warning-50 text-warning-600 dark:bg-warning-900/40 dark:text-warning-300',
    danger: 'bg-danger-50 text-danger-600 dark:bg-danger-900/40 dark:text-danger-300',
    neutral: 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300',
} as const;

export function Sheet({
    open,
    onClose,
    side = 'right',
    size = 'md',
    title,
    description,
    icon,
    iconTone = 'brand',
    footer,
    hideCloseButton = false,
    closeOnBackdrop = true,
    children,
}: SheetProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const previousFocusRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!open) return;
        previousFocusRef.current = document.activeElement as HTMLElement | null;
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            const el = containerRef.current?.querySelector<HTMLElement>(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
            );
            el?.focus();
        }, 30);

        return () => {
            document.body.style.overflow = prevOverflow;
            previousFocusRef.current?.focus?.();
        };
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.stopPropagation();
                onClose();
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open, onClose]);

    if (!open) return null;

    const isHorizontal = side === 'right' || side === 'left';

    return (
        <div
            className="fixed inset-0 z-[100]"
            role="dialog"
            aria-modal="true"
            aria-labelledby={title ? 'sheet-title' : undefined}
            aria-describedby={description ? 'sheet-description' : undefined}
        >
            <button
                type="button"
                aria-label="Fermer"
                onClick={closeOnBackdrop ? onClose : undefined}
                tabIndex={-1}
                className="absolute inset-0 cursor-default bg-ink-900/40 backdrop-blur-sm dark:bg-ink-900/70"
            />

            <div
                ref={containerRef}
                className={cn(
                    'absolute flex flex-col bg-white shadow-2xl dark:bg-ink-800',
                    isHorizontal ? 'h-full' : 'w-full',
                    side === 'right' && cn('right-0 top-0', SIZE_RIGHT_LEFT[size]),
                    side === 'left' && cn('left-0 top-0', SIZE_RIGHT_LEFT[size]),
                    side === 'bottom' && cn('bottom-0 left-0 right-0 rounded-t-2xl', SIZE_BOTTOM[size]),
                    isHorizontal && 'border-l border-ink-200 dark:border-ink-700',
                    side === 'left' && 'border-l-0 border-r border-ink-200 dark:border-ink-700',
                )}
            >
                {(title || icon || !hideCloseButton) && (
                    <header className="flex items-start gap-3 border-b border-ink-100 px-5 py-4 dark:border-ink-700/60">
                        {icon && (
                            <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-xl', ICON_TONE[iconTone])}>
                                {icon}
                            </span>
                        )}
                        <div className="min-w-0 flex-1">
                            {title && (
                                <h2 id="sheet-title" className="text-sm font-semibold text-ink-900 dark:text-white">
                                    {title}
                                </h2>
                            )}
                            {description && (
                                <p id="sheet-description" className="mt-0.5 text-xs leading-relaxed text-ink-500 dark:text-ink-400">
                                    {description}
                                </p>
                            )}
                        </div>
                        {!hideCloseButton && (
                            <button
                                type="button"
                                onClick={onClose}
                                aria-label="Fermer"
                                className="shrink-0 rounded-md p-1 text-ink-400 transition-colors hover:bg-ink-100 hover:text-ink-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:text-ink-500 dark:hover:bg-ink-700 dark:hover:text-ink-200"
                            >
                                <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <line x1="18" y1="6" x2="6" y2="18" strokeLinecap="round" />
                                    <line x1="6" y1="6" x2="18" y2="18" strokeLinecap="round" />
                                </svg>
                            </button>
                        )}
                    </header>
                )}

                <div className="flex-1 overflow-y-auto px-5 py-4">{children}</div>

                {footer && (
                    <footer className="flex items-center justify-end gap-2 border-t border-ink-100 bg-ink-50/60 px-5 py-3 dark:border-ink-700/60 dark:bg-ink-900/40">
                        {footer}
                    </footer>
                )}
            </div>
        </div>
    );
}
