import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface FlashProps {
    flash?: {
        success?: string | null;
        error?: string | null;
        info?: string | null;
        warning?: string | null;
    };
    [key: string]: unknown;
}

type ToastType = 'success' | 'error' | 'info' | 'warning';

interface Toast {
    id: number;
    type: ToastType;
    message: string;
}

const TOAST_CONFIG: Record<ToastType, { icon: React.ReactNode; bg: string; text: string; border: string; progress: string }> = {
    success: {
        icon: <CheckIcon />,
        bg: 'bg-sage-50 dark:bg-sage-900/30',
        text: 'text-sage-800 dark:text-sage-200',
        border: 'border-sage-200 dark:border-sage-700/50',
        progress: 'bg-sage-500',
    },
    error: {
        icon: <XCircleIcon />,
        bg: 'bg-danger-50 dark:bg-danger-900/30',
        text: 'text-danger-800 dark:text-danger-200',
        border: 'border-danger-200 dark:border-danger-700/50',
        progress: 'bg-danger-500',
    },
    info: {
        icon: <InfoIcon />,
        bg: 'bg-brand-50 dark:bg-brand-900/30',
        text: 'text-brand-800 dark:text-brand-200',
        border: 'border-brand-200 dark:border-brand-700/50',
        progress: 'bg-brand-500',
    },
    warning: {
        icon: <WarningIcon />,
        bg: 'bg-warning-50 dark:bg-warning-900/30',
        text: 'text-warning-800 dark:text-warning-200',
        border: 'border-warning-200 dark:border-warning-700/50',
        progress: 'bg-warning-500',
    },
};

let toastCounter = 0;

export function FlashToasts() {
    const { props } = usePage<FlashProps>();
    const [toasts, setToasts] = useState<Toast[]>([]);

    useEffect(() => {
        const flash = props.flash;
        if (!flash) return;

        const newToasts: Toast[] = [];
        const types: ToastType[] = ['success', 'error', 'info', 'warning'];

        for (const type of types) {
            const message = flash[type];
            if (message) {
                newToasts.push({ id: ++toastCounter, type, message });
            }
        }

        if (newToasts.length > 0) {
            setToasts((prev) => [...prev, ...newToasts]);
        }
    }, [props.flash]);

    const dismiss = (id: number) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    };

    if (toasts.length === 0) return null;

    return (
        <div
            className="fixed right-4 top-20 z-[100] flex flex-col gap-2.5"
            aria-live="polite"
            aria-label="Notifications"
        >
            {toasts.map((toast) => (
                <ToastItem key={toast.id} toast={toast} onDismiss={dismiss} />
            ))}
        </div>
    );
}

function ToastItem({ toast, onDismiss }: { toast: Toast; onDismiss: (id: number) => void }) {
    const config = TOAST_CONFIG[toast.type];

    useEffect(() => {
        const duration = toast.type === 'error' ? 6000 : 4000;
        const timer = setTimeout(() => onDismiss(toast.id), duration);
        return () => clearTimeout(timer);
    }, [toast.id, toast.type, onDismiss]);

    return (
        <div
            className={cn(
                'relative w-80 overflow-hidden rounded-xl border shadow-lg backdrop-blur-sm',
                'animate-[slideIn_200ms_ease-out]',
                config.bg,
                config.border,
            )}
            role="alert"
        >
            <div className="flex items-start gap-3 px-4 py-3">
                <span className={cn('mt-0.5 shrink-0', config.text)}>{config.icon}</span>
                <p className={cn('flex-1 text-sm font-medium leading-snug', config.text)}>
                    {toast.message}
                </p>
                <button
                    type="button"
                    onClick={() => onDismiss(toast.id)}
                    className={cn(
                        'shrink-0 rounded-md p-0.5 transition-opacity hover:opacity-70',
                        config.text,
                    )}
                    aria-label="Fermer"
                >
                    <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div className="h-0.5 w-full bg-black/5 dark:bg-white/5">
                <div
                    className={cn('h-full animate-[shrink_4s_linear_forwards]', config.progress)}
                    style={{
                        animationDuration: toast.type === 'error' ? '6s' : '4s',
                    }}
                />
            </div>
        </div>
    );
}

function CheckIcon() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );
}

function XCircleIcon() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );
}

function InfoIcon() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
    );
}

function WarningIcon() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
    );
}
