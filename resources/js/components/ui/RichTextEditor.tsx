import { cn } from '@/lib/utils';
import { renderSafeMarkdown } from '@/lib/safe-markdown';
import { ChangeEvent, KeyboardEvent, useRef, useState } from 'react';

interface RichTextEditorProps {
    value: string;
    onChange: (value: string) => void;
    /** Optional minimum number of visible rows (default 8). */
    rows?: number;
    /** Optional placeholder shown when the editor is empty. */
    placeholder?: string;
    /** Optional id for the underlying textarea (useful for `<label htmlFor>`). */
    id?: string;
    /** Disable input (e.g. while submitting). */
    disabled?: boolean;
    /** Override container class. */
    className?: string;
}

type Mode = 'write' | 'preview';

/**
 * Lightweight Markdown rich text editor. Backed by a `<textarea>` so it works
 * without any contentEditable quirks; the toolbar inserts Markdown syntax at
 * the cursor (or wraps the current selection). Live preview is rendered by
 * `renderSafeMarkdown` — no `dangerouslySetInnerHTML`, ever.
 *
 * Designed for news posts, internal announcements and other short-form
 * content. For richer needs (tables, images, embeds), upgrade to a
 * dedicated editor like TipTap.
 */
export function RichTextEditor({
    value,
    onChange,
    rows = 8,
    placeholder,
    id,
    disabled,
    className,
}: RichTextEditorProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [mode, setMode] = useState<Mode>('write');

    const applyWrap = (left: string, right: string = left, placeholderInside?: string) => {
        const el = textareaRef.current;
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const selected = value.slice(start, end);
        const inner = selected || placeholderInside || '';
        const before = value.slice(0, start);
        const after = value.slice(end);
        const updated = `${before}${left}${inner}${right}${after}`;
        onChange(updated);
        // Restore selection on the inserted inner text.
        requestAnimationFrame(() => {
            el.focus();
            const cursorStart = start + left.length;
            const cursorEnd = cursorStart + inner.length;
            el.setSelectionRange(cursorStart, cursorEnd);
        });
    };

    const applyLinePrefix = (prefix: string) => {
        const el = textareaRef.current;
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const before = value.slice(0, start);
        const selected = value.slice(start, end) || '';
        const after = value.slice(end);

        // Find the start of the current line.
        const lineStart = before.lastIndexOf('\n') + 1;
        const block = (before.slice(lineStart) + selected).split('\n');
        const prefixed = block.map((line) => (line ? `${prefix}${line}` : line)).join('\n');
        const updated = value.slice(0, lineStart) + prefixed + after;
        onChange(updated);
        requestAnimationFrame(() => {
            el.focus();
            const newCursor = lineStart + prefixed.length;
            el.setSelectionRange(newCursor, newCursor);
        });
    };

    const insertLink = () => {
        const el = textareaRef.current;
        if (!el) return;
        const url = window.prompt('URL du lien (https://…)');
        if (!url) return;
        if (!/^https?:\/\//i.test(url)) {
            window.alert('Seules les URLs https:// (ou http://) sont autorisées.');
            return;
        }
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const selected = value.slice(start, end);
        const label = selected || 'lien';
        applyWrap(`[${label}](${url})`, '', '');
        // We used wrap but supplied the full markdown in `left` — the
        // selection now spans the inserted segment.
    };

    const onKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (!(e.metaKey || e.ctrlKey)) return;
        switch (e.key.toLowerCase()) {
            case 'b':
                e.preventDefault();
                applyWrap('**', '**', 'texte');
                return;
            case 'i':
                e.preventDefault();
                applyWrap('_', '_', 'texte');
                return;
            case 'k':
                e.preventDefault();
                insertLink();
                return;
        }
    };

    const onTextareaChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
        onChange(e.target.value);
    };

    return (
        <div
            className={cn(
                'overflow-hidden rounded-2xl border border-ink-200 bg-white dark:border-ink-700 dark:bg-ink-800',
                disabled && 'opacity-60',
                className,
            )}
        >
            {/* Mode + toolbar header */}
            <div className="flex items-center justify-between gap-2 border-b border-ink-100 bg-ink-50/60 px-3 py-2 dark:border-ink-700 dark:bg-ink-800">
                <div className="inline-flex items-center gap-1 rounded-full bg-white p-0.5 shadow-sm dark:bg-ink-900">
                    <TabButton active={mode === 'write'} onClick={() => setMode('write')}>
                        Rédiger
                    </TabButton>
                    <TabButton active={mode === 'preview'} onClick={() => setMode('preview')}>
                        Aperçu
                    </TabButton>
                </div>
                {mode === 'write' && (
                    <div className="flex flex-wrap items-center gap-1">
                        <ToolbarButton title="Titre (## Texte)" onClick={() => applyLinePrefix('## ')}>
                            H₂
                        </ToolbarButton>
                        <ToolbarButton title="Sous-titre (### Texte)" onClick={() => applyLinePrefix('### ')}>
                            H₃
                        </ToolbarButton>
                        <span className="mx-1 h-4 w-px bg-ink-200 dark:bg-ink-600" />
                        <ToolbarButton title="Gras (⌘B)" onClick={() => applyWrap('**', '**', 'gras')}>
                            <span className="font-bold">B</span>
                        </ToolbarButton>
                        <ToolbarButton title="Italique (⌘I)" onClick={() => applyWrap('_', '_', 'italique')}>
                            <span className="italic">I</span>
                        </ToolbarButton>
                        <ToolbarButton title="Code inline" onClick={() => applyWrap('`', '`', 'code')}>
                            <span className="font-mono text-[12px]">{'<>'}</span>
                        </ToolbarButton>
                        <span className="mx-1 h-4 w-px bg-ink-200 dark:bg-ink-600" />
                        <ToolbarButton title="Liste à puces" onClick={() => applyLinePrefix('- ')}>
                            •
                        </ToolbarButton>
                        <ToolbarButton title="Liste numérotée" onClick={() => applyLinePrefix('1. ')}>
                            1.
                        </ToolbarButton>
                        <span className="mx-1 h-4 w-px bg-ink-200 dark:bg-ink-600" />
                        <ToolbarButton title="Lien (⌘K)" onClick={insertLink}>
                            🔗
                        </ToolbarButton>
                    </div>
                )}
            </div>

            {/* Body */}
            {mode === 'write' ? (
                <div className="relative">
                    <textarea
                        id={id}
                        ref={textareaRef}
                        rows={rows}
                        value={value}
                        onChange={onTextareaChange}
                        onKeyDown={onKeyDown}
                        placeholder={placeholder}
                        disabled={disabled}
                        spellCheck
                        className="block w-full resize-y border-0 bg-transparent px-4 py-3 font-mono text-[13px] leading-relaxed text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-0 dark:text-ink-100"
                    />
                </div>
            ) : (
                <div className="min-h-[10rem] px-4 py-4">
                    {value.trim() === '' ? (
                        <p className="text-sm italic text-ink-400 dark:text-ink-500">L'aperçu apparaîtra ici.</p>
                    ) : (
                        renderSafeMarkdown(value)
                    )}
                </div>
            )}

            {/* Hint footer */}
            <div className="flex items-center gap-3 border-t border-ink-100 px-3 py-1.5 text-[11px] text-ink-500 dark:border-ink-700 dark:text-ink-400">
                <span>
                    <kbd className="rounded border border-ink-200 bg-white px-1 font-mono dark:border-ink-600 dark:bg-ink-700">⌘B</kbd>{' '}
                    gras ·{' '}
                    <kbd className="rounded border border-ink-200 bg-white px-1 font-mono dark:border-ink-600 dark:bg-ink-700">⌘I</kbd>{' '}
                    italique ·{' '}
                    <kbd className="rounded border border-ink-200 bg-white px-1 font-mono dark:border-ink-600 dark:bg-ink-700">⌘K</kbd>{' '}
                    lien
                </span>
                <span className="ml-auto font-mono">{value.length} caractères</span>
            </div>
        </div>
    );
}

function TabButton({ active, onClick, children }: { active: boolean; onClick: () => void; children: React.ReactNode }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'rounded-full px-3 py-1 text-xs font-semibold transition-colors',
                active
                    ? 'bg-brand-600 text-white shadow-sm'
                    : 'text-ink-600 hover:text-ink-900 dark:text-ink-300 dark:hover:text-white',
            )}
        >
            {children}
        </button>
    );
}

function ToolbarButton({
    title,
    onClick,
    children,
}: {
    title: string;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            title={title}
            onClick={onClick}
            className="inline-flex size-7 items-center justify-center rounded-md text-sm text-ink-600 transition-colors hover:bg-white hover:text-brand-600 hover:shadow-sm dark:text-ink-300 dark:hover:bg-ink-700 dark:hover:text-brand-400"
        >
            {children}
        </button>
    );
}
