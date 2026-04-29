import { cn } from '@/lib/utils';
import { forwardRef, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';

const baseField =
    'w-full rounded-lg border border-ink-200 bg-white px-3 text-sm text-ink-900 placeholder:text-ink-400 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-ink-50 disabled:text-ink-400';

const errorField = 'border-danger-300 hover:border-danger-400 focus:border-danger-500 focus:ring-danger-500/20';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    invalid?: boolean;
    leadingIcon?: ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
    { invalid, leadingIcon, className, ...rest },
    ref,
) {
    if (leadingIcon) {
        return (
            <div className={cn('relative', className)}>
                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-400">{leadingIcon}</span>
                <input
                    ref={ref}
                    className={cn(baseField, 'h-10 pl-10 pr-3', invalid && errorField)}
                    {...rest}
                />
            </div>
        );
    }
    return <input ref={ref} className={cn(baseField, 'h-10', invalid && errorField, className)} {...rest} />;
});

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    invalid?: boolean;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
    { invalid, className, ...rest },
    ref,
) {
    return (
        <textarea
            ref={ref}
            className={cn(baseField, 'min-h-24 py-2.5 leading-relaxed', invalid && errorField, className)}
            {...rest}
        />
    );
});

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    invalid?: boolean;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
    { invalid, className, children, ...rest },
    ref,
) {
    return (
        <select
            ref={ref}
            className={cn(
                baseField,
                'h-10 pr-10 appearance-none bg-[url("data:image/svg+xml,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20viewBox=%270%200%2024%2024%27%20fill=%27none%27%20stroke=%27%2364748b%27%20stroke-width=%272%27%20stroke-linecap=%27round%27%20stroke-linejoin=%27round%27%3E%3Cpolyline%20points=%276%209%2012%2015%2018%209%27/%3E%3C/svg%3E")] bg-[length:16px_16px] bg-[position:right_0.75rem_center] bg-no-repeat',
                invalid && errorField,
                className,
            )}
            {...rest}
        >
            {children}
        </select>
    );
});

export function Label({ children, htmlFor, required }: { children: React.ReactNode; htmlFor?: string; required?: boolean }) {
    return (
        <label htmlFor={htmlFor} className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600">
            {children}
            {required && <span className="ml-1 text-danger-500">*</span>}
        </label>
    );
}

export function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return (
        <p className="mt-1.5 flex items-center gap-1 text-xs text-danger-600">
            <svg className="size-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden>
                <path
                    fillRule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clipRule="evenodd"
                />
            </svg>
            {message}
        </p>
    );
}

export function FieldHelp({ children }: { children: React.ReactNode }) {
    return <p className="mt-1.5 text-xs text-ink-500">{children}</p>;
}

export function FormField({
    label,
    htmlFor,
    required,
    error,
    help,
    children,
}: {
    label: string;
    htmlFor?: string;
    required?: boolean;
    error?: string;
    help?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <div>
            <Label htmlFor={htmlFor} required={required}>
                {label}
            </Label>
            {children}
            <FieldError message={error} />
            {!error && help && <FieldHelp>{help}</FieldHelp>}
        </div>
    );
}
