import { cn } from '@/lib/utils';
import { ReactNode, useEffect, useRef, useState } from 'react';

interface HoverCardProps {
    trigger: ReactNode;
    children: ReactNode;
    openDelay?: number;
    closeDelay?: number;
    side?: 'top' | 'bottom';
    width?: number;
    /** Wraps the trigger in a button or just a span (button if interactive). */
    asButton?: boolean;
    /** Forwarded to the inner button when asButton=true. */
    ariaLabel?: string;
    onClick?: () => void;
}

/**
 * Lightweight hover-or-focus popover used to expose contextual mini-profiles
 * over user avatars, beneficiary references, etc. Designed to never block
 * keyboard users: opening happens on focus too and closing on blur/escape.
 */
export function HoverCard({
    trigger,
    children,
    openDelay = 250,
    closeDelay = 120,
    side = 'bottom',
    width = 280,
    asButton = false,
    ariaLabel,
    onClick,
}: HoverCardProps) {
    const [open, setOpen] = useState(false);
    const openTimer = useRef<number | null>(null);
    const closeTimer = useRef<number | null>(null);
    const containerRef = useRef<HTMLSpanElement>(null);

    const cancelTimers = () => {
        if (openTimer.current) window.clearTimeout(openTimer.current);
        if (closeTimer.current) window.clearTimeout(closeTimer.current);
        openTimer.current = null;
        closeTimer.current = null;
    };

    const requestOpen = () => {
        cancelTimers();
        openTimer.current = window.setTimeout(() => setOpen(true), openDelay);
    };
    const requestClose = () => {
        cancelTimers();
        closeTimer.current = window.setTimeout(() => setOpen(false), closeDelay);
    };

    useEffect(() => () => cancelTimers(), []);

    useEffect(() => {
        if (!open) return;
        const handleKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('keydown', handleKey);
        return () => document.removeEventListener('keydown', handleKey);
    }, [open]);

    const Trigger = asButton ? 'button' : 'span';

    return (
        <span ref={containerRef} className="relative inline-flex" onMouseEnter={requestOpen} onMouseLeave={requestClose}>
            <Trigger
                type={asButton ? 'button' : undefined}
                onFocus={requestOpen}
                onBlur={requestClose}
                onClick={onClick}
                aria-describedby={open ? 'hover-card-content' : undefined}
                aria-label={ariaLabel}
                className={cn(asButton && 'cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 focus-visible:ring-offset-1 rounded-md')}
            >
                {trigger}
            </Trigger>

            {open && (
                <span
                    id="hover-card-content"
                    role="tooltip"
                    onMouseEnter={cancelTimers}
                    onMouseLeave={requestClose}
                    className={cn(
                        'absolute left-0 z-50 rounded-xl border border-ink-200 bg-white p-3 text-left shadow-xl dark:border-ink-700 dark:bg-ink-800',
                        side === 'top' ? 'bottom-full mb-2' : 'top-full mt-2',
                    )}
                    style={{ width }}
                >
                    {children}
                </span>
            )}
        </span>
    );
}
