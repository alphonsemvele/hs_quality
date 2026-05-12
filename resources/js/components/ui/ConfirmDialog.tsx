import { ReactNode, useEffect, useState } from 'react';
import { Button } from './Button';
import { Modal } from './Modal';

type Tone = 'danger' | 'warning' | 'info';

interface ConfirmDialogProps {
    open: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    description?: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: Tone;
    loading?: boolean;
    requireTyped?: string;
}

const ICON: Record<Tone, ReactNode> = {
    danger: (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    ),
    warning: (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
    ),
    info: (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12.01" y2="8" />
        </svg>
    ),
};

const ICON_TONE_MAP: Record<Tone, 'danger' | 'warning' | 'brand'> = {
    danger: 'danger',
    warning: 'warning',
    info: 'brand',
};

const BUTTON_VARIANT_MAP: Record<Tone, 'danger' | 'primary'> = {
    danger: 'danger',
    warning: 'primary',
    info: 'primary',
};

export function ConfirmDialog({
    open,
    onClose,
    onConfirm,
    title,
    description,
    confirmLabel = 'Confirmer',
    cancelLabel = 'Annuler',
    tone = 'info',
    loading = false,
    requireTyped,
}: ConfirmDialogProps) {
    const [typed, setTyped] = useTyped(requireTyped, open);
    const canConfirm = requireTyped ? typed === requireTyped : true;

    return (
        <Modal
            open={open}
            onClose={loading ? () => {} : onClose}
            title={title}
            size="sm"
            icon={ICON[tone]}
            iconTone={ICON_TONE_MAP[tone]}
            closeOnBackdrop={!loading}
            closeOnEscape={!loading}
            footer={
                <>
                    <Button variant="ghost" onClick={onClose} disabled={loading}>
                        {cancelLabel}
                    </Button>
                    <Button
                        variant={BUTTON_VARIANT_MAP[tone]}
                        onClick={onConfirm}
                        loading={loading}
                        disabled={!canConfirm}
                    >
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            {description && (
                <div className="text-sm leading-relaxed text-ink-700 dark:text-ink-200">{description}</div>
            )}
            {requireTyped && (
                <div className="mt-4">
                    <label htmlFor="confirm-typed" className="block text-xs font-medium text-ink-700 dark:text-ink-200">
                        Pour confirmer, tapez <span className="font-mono font-semibold text-danger-700 dark:text-danger-400">{requireTyped}</span>
                    </label>
                    <input
                        id="confirm-typed"
                        type="text"
                        value={typed}
                        onChange={(e) => setTyped(e.target.value)}
                        autoComplete="off"
                        className="mt-1.5 h-10 w-full rounded-lg border border-ink-200 bg-white px-3 font-mono text-sm text-ink-900 focus:border-danger-400 focus:outline-none focus:ring-2 focus:ring-danger-100 dark:border-ink-700 dark:bg-ink-900/40 dark:text-white dark:focus:ring-danger-900/30"
                    />
                </div>
            )}
        </Modal>
    );
}

function useTyped(requirement: string | undefined, open: boolean): [string, (v: string) => void] {
    const [value, setValue] = useState('');
    useEffect(() => {
        if (!open) setValue('');
    }, [open]);
    if (!requirement) return [value, setValue];
    return [value, setValue];
}
