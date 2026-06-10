import { useEffect } from 'react';

interface Options {
    /** Whether the modal is currently visible — listener only attaches while true. */
    open: boolean;
    /** Submit handler invoked on Cmd/Ctrl+Enter. */
    onSubmit: () => void;
    /** Skip the shortcut while a request is in-flight to avoid duplicate submissions. */
    disabled?: boolean;
}

/**
 * Cmd/Ctrl+Enter to submit the form inside an open modal.
 *
 * The keystroke fires even when focus is inside a multi-line `<textarea>`
 * or a custom contenteditable — the regular Enter key still inserts a
 * newline, which is what users expect. The modifier requirement keeps it
 * safe in single-line inputs too (regular Enter falls through to native
 * form-submit behaviour).
 */
export function useModalSubmitShortcut({ open, onSubmit, disabled }: Options): void {
    useEffect(() => {
        if (!open || disabled) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key !== 'Enter') return;
            if (!e.metaKey && !e.ctrlKey) return;
            e.preventDefault();
            onSubmit();
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open, onSubmit, disabled]);
}
