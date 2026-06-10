import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

interface StickySaveBarProps {
    /** Show/hide the bar. Typically wired to `form.isDirty`. */
    visible: boolean;
    /** Submit button label. */
    saveLabel?: string;
    /** Disabled/loading flag forwarded from the form. */
    saving?: boolean;
    /**
     * Click handler for the save button. If omitted, the button renders as
     * `type="submit"` (use this when the bar is rendered inside a <form>).
     */
    onSave?: () => void;
    /** Called when the user clicks the secondary "Annuler" button. */
    onCancel?: () => void;
    /** Optional summary slot (e.g. "3 modifications non sauvegardées"). */
    summary?: ReactNode;
    /** Optional extra slot rendered before the save button. */
    extra?: ReactNode;
    className?: string;
}

/**
 * Floating bar that appears at the bottom of the viewport when a form has
 * pending changes. Pairs with Inertia's `useForm` (`form.isDirty` /
 * `form.processing`) on long preference / settings pages where the save
 * button would otherwise scroll out of sight.
 *
 * Render this inside the same <form> as your inputs so the bar's submit
 * button submits the form natively.
 */
export function StickySaveBar({
    visible,
    saveLabel = 'Enregistrer',
    saving = false,
    onSave,
    onCancel,
    summary,
    extra,
    className,
}: StickySaveBarProps) {
    return (
        <div
            aria-hidden={!visible}
            className={cn(
                'pointer-events-none fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-3xl items-center justify-between gap-3 rounded-2xl border border-ink-200 bg-white px-4 py-3 shadow-lg transition-all duration-200 sm:left-1/2 sm:-translate-x-1/2 dark:border-ink-700 dark:bg-ink-800 dark:shadow-[0_8px_24px_rgba(0,0,0,0.4)]',
                visible
                    ? 'pointer-events-auto translate-y-0 opacity-100'
                    : 'translate-y-4 opacity-0',
                className,
            )}
            role="region"
            aria-label="Modifications non sauvegardées"
        >
            <div className="flex min-w-0 items-center gap-3">
                <span className="inline-flex size-2 shrink-0 rounded-full bg-warning-500 dark:bg-warning-400" aria-hidden />
                <div className="min-w-0 text-sm text-ink-700 dark:text-ink-200">
                    {summary ?? 'Vous avez des modifications non sauvegardées.'}
                </div>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                {extra}
                {onCancel && (
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={saving}
                        className="rounded-lg border border-ink-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-700 transition-colors hover:bg-ink-50 disabled:opacity-50 dark:border-ink-600 dark:bg-ink-700 dark:text-ink-200 dark:hover:bg-ink-600"
                    >
                        Annuler
                    </button>
                )}
                <button
                    type={onSave ? 'button' : 'submit'}
                    onClick={onSave}
                    disabled={saving}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-brand-700 disabled:opacity-60 dark:bg-brand-500 dark:hover:bg-brand-400"
                >
                    {saving && (
                        <svg className="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" className="opacity-25" />
                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                        </svg>
                    )}
                    {saving ? 'Sauvegarde…' : saveLabel}
                </button>
            </div>
        </div>
    );
}
