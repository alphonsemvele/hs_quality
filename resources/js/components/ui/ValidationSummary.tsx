import { cn } from '@/lib/utils';

interface FieldError {
    /** Field id (matches the input's `id` attribute) so the anchor link can focus it. */
    field: string;
    /** Human-readable label of the field (e.g. "Date de naissance"). */
    label: string;
    /** Validation message. */
    message: string;
}

interface ValidationSummaryProps {
    /** Either a `Record<field, message>` (Inertia useForm.errors shape) or a pre-built list. */
    errors: Record<string, string> | FieldError[];
    /** Optional map of field id -> display label. Used to enrich the error map. */
    labels?: Record<string, string>;
    className?: string;
}

/**
 * Validation summary banner — appears at the top of a long form when
 * submission fails, listing every invalid field as an anchor link that
 * scrolls + focuses the corresponding input.
 *
 * Accepts either Inertia's `errors` map or a pre-built list. The anchor
 * scroll uses `getElementById(field)` to find the input (FormField's
 * `htmlFor` must match — already a convention in this codebase).
 */
export function ValidationSummary({ errors, labels = {}, className }: ValidationSummaryProps) {
    const list: FieldError[] = Array.isArray(errors)
        ? errors
        : Object.entries(errors).map(([field, message]) => ({
            field,
            label: labels[field] ?? humanise(field),
            message,
        }));

    if (list.length === 0) return null;

    const onJump = (field: string) => (e: React.MouseEvent<HTMLAnchorElement>) => {
        e.preventDefault();
        const target = document.getElementById(field);
        if (!target) return;
        const top = target.getBoundingClientRect().top + window.scrollY - 96;
        window.scrollTo({ top, behavior: 'smooth' });
        if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement) {
            target.focus({ preventScroll: true });
        }
    };

    return (
        <div
            role="alert"
            aria-live="polite"
            className={cn(
                'rounded-2xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-700/50 dark:bg-danger-900/20',
                className,
            )}
        >
            <div className="flex items-start gap-3">
                <span className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-danger-100 text-danger-700 dark:bg-danger-900/50 dark:text-danger-300">
                    <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </span>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-danger-900 dark:text-danger-100">
                        {list.length} erreur{list.length > 1 ? 's' : ''} à corriger
                    </p>
                    <ul className="mt-2 space-y-1">
                        {list.map((err) => (
                            <li key={err.field} className="text-xs text-danger-800 dark:text-danger-200">
                                <a
                                    href={`#${err.field}`}
                                    onClick={onJump(err.field)}
                                    className="font-medium underline-offset-2 hover:underline"
                                >
                                    {err.label}
                                </a>
                                {' — '}
                                {err.message}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );
}

function humanise(field: string): string {
    return field
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}
