import { cn } from '@/lib/utils';
import { ReactNode, useEffect, useRef } from 'react';

type Size = 'sm' | 'md' | 'lg' | 'xl';

const SIZE: Record<Size, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

interface ModalProps {
    open: boolean;
    onClose: () => void;
    title?: string;
    description?: string;
    size?: Size;
    icon?: ReactNode;
    iconTone?: 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';
    closeOnBackdrop?: boolean;
    closeOnEscape?: boolean;
    hideCloseButton?: boolean;
    footer?: ReactNode;
    children: ReactNode;
}

const ICON_TONE = {
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300',
    sage: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
    warning: 'bg-warning-50 text-warning-600 dark:bg-warning-900/40 dark:text-warning-300',
    danger: 'bg-danger-50 text-danger-600 dark:bg-danger-900/40 dark:text-danger-300',
    neutral: 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300',
} as const;

export function Modal({
    open,
    onClose,
    title,
    description,
    size = 'md',
    icon,
    iconTone = 'brand',
    closeOnBackdrop = true,
    closeOnEscape = true,
    hideCloseButton = false,
    footer,
    children,
}: ModalProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const previousFocusRef = useRef<HTMLElement | null>(null);

    // ── Body scroll lock + focus restoration ─────────────────────
    useEffect(() => {
        if (!open) return;
        previousFocusRef.current = document.activeElement as HTMLElement | null;
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        // Focus first focusable element inside dialog
        setTimeout(() => {
            const el = containerRef.current?.querySelector<HTMLElement>(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
            );
            el?.focus();
        }, 0);

        return () => {
            document.body.style.overflow = prevOverflow;
            previousFocusRef.current?.focus?.();
        };
    }, [open]);

    // ── Escape key ───────────────────────────────────────────────
    useEffect(() => {
        if (!open || !closeOnEscape) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.stopPropagation();
                onClose();
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open, closeOnEscape, onClose]);

    // ── Focus trap (Tab cycle) ───────────────────────────────────
    useEffect(() => {
        if (!open) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key !== 'Tab') return;
            const focusable = containerRef.current?.querySelectorAll<HTMLElement>(
                'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            );
            if (!focusable || focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto p-4 pt-[8vh] sm:items-center sm:pt-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby={title ? 'modal-title' : undefined}
            aria-describedby={description ? 'modal-description' : undefined}
        >
            <button
                type="button"
                aria-label="Fermer"
                onClick={closeOnBackdrop ? onClose : undefined}
                tabIndex={-1}
                className={cn(
                    'absolute inset-0 cursor-default bg-ink-900/40 backdrop-blur-sm dark:bg-ink-900/70',
                    !closeOnBackdrop && 'cursor-default',
                )}
            />
            <div
                ref={containerRef}
                className={cn(
                    'relative w-full overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-2xl dark:border-ink-700 dark:bg-ink-800',
                    SIZE[size],
                )}
            >
                {(title || icon || !hideCloseButton) && (
                    <div className="flex items-start gap-3 border-b border-ink-100 px-5 py-4 dark:border-ink-700/60">
                        {icon && (
                            <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-xl', ICON_TONE[iconTone])}>
                                {icon}
                            </span>
                        )}
                        <div className="min-w-0 flex-1">
                            {title && (
                                <h2 id="modal-title" className="text-sm font-semibold text-ink-900 dark:text-white">
                                    {title}
                                </h2>
                            )}
                            {description && (
                                <p id="modal-description" className="mt-0.5 text-xs leading-relaxed text-ink-500 dark:text-ink-400">
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
                    </div>
                )}

                <div className="px-5 py-4">{children}</div>

                {footer && (
                    <div className="flex items-center justify-end gap-2 border-t border-ink-100 bg-ink-50/60 px-5 py-3 dark:border-ink-700/60 dark:bg-ink-900/40">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}
