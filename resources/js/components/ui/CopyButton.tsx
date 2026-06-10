import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

interface CopyButtonProps {
    /** The text to copy. Computed lazily so callers can pass a getter for fresh values. */
    value: string | (() => string);
    /** Accessible label for screen readers. Defaults to "Copier". */
    label?: string;
    /** Optional visible label rendered next to the icon. */
    children?: string;
    /** Visual size — `xs` is meant for inline placement next to a code snippet. */
    size?: 'xs' | 'sm';
    className?: string;
}

/**
 * One-click clipboard copy with success confirmation.
 *
 * Falls back gracefully when the page is served over plain HTTP (where
 * `navigator.clipboard` is unavailable): a hidden textarea + `execCommand`
 * keeps the trigger working on the same set of browsers as our app.
 *
 * @example
 *   <CopyButton value={token} label="Copier le jeton API" />
 */
export function CopyButton({ value, label = 'Copier', children, size = 'sm', className }: CopyButtonProps) {
    const [state, setState] = useState<'idle' | 'copied' | 'error'>('idle');

    useEffect(() => {
        if (state === 'idle') return;
        const t = window.setTimeout(() => setState('idle'), 1500);
        return () => window.clearTimeout(t);
    }, [state]);

    const onCopy = async () => {
        const text = typeof value === 'function' ? value() : value;
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
            } else {
                // execCommand fallback for non-secure contexts
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'absolute';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            setState('copied');
        } catch {
            setState('error');
        }
    };

    const sizeCls = size === 'xs'
        ? 'h-6 gap-1 px-1.5 text-[10px] rounded-md'
        : 'h-7 gap-1.5 px-2 text-[11px] rounded-md';

    return (
        <button
            type="button"
            onClick={onCopy}
            aria-label={label}
            aria-live="polite"
            className={cn(
                'inline-flex items-center font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40',
                state === 'copied'
                    ? 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300'
                    : state === 'error'
                        ? 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300'
                        : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900 dark:text-ink-300 dark:hover:bg-ink-700 dark:hover:text-white',
                sizeCls,
                className,
            )}
        >
            {state === 'copied' ? (
                <svg className="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" aria-hidden>
                    <path d="M5 12l5 5L20 7" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            ) : (
                <svg className="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
                    <rect x="9" y="9" width="11" height="11" rx="2" />
                    <path d="M5 15V5a2 2 0 012-2h10" />
                </svg>
            )}
            {(children || state === 'copied') && (
                <span>{state === 'copied' ? 'Copié !' : children}</span>
            )}
        </button>
    );
}
