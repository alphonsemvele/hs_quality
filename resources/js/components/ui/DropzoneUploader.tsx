import { cn } from '@/lib/utils';
import { ChangeEvent, DragEvent, useEffect, useRef, useState } from 'react';

/**
 * Status of a single staged file as it moves through the upload pipeline.
 */
type UploadStatus = 'staged' | 'uploading' | 'done' | 'error';

interface StagedFile {
    id: string;
    file: File;
    progress: number;
    status: UploadStatus;
    errorMessage?: string;
    abort?: () => void;
}

export interface DropzoneFieldDefinition {
    /** Form field name. */
    name: string;
    /** Visible label. */
    label: string;
    /** Optional helper text under the field. */
    help?: string;
    /** Optional default value. */
    defaultValue?: string;
    /** When true, the field is required (HTML attribute, browser validation only). */
    required?: boolean;
}

interface DropzoneUploaderProps {
    /** URL the multipart POST is sent to. */
    endpoint: string;
    /** Comma-separated list of accepted MIME types (e.g. "application/pdf,image/*"). */
    accept?: string;
    /** Allowed extensions for client-side display only — not authoritative. */
    acceptLabel?: string;
    /** Max bytes per file (client-side guard; server is authoritative). */
    maxBytes?: number;
    /** Form field name carrying the file in multipart payload. Default: `file`. */
    fieldName?: string;
    /** Allow multiple files (each uploaded as a separate request). Default true. */
    multiple?: boolean;
    /** Extra metadata fields to render under the dropzone (e.g. title, description). */
    fields?: DropzoneFieldDefinition[];
    /** CSRF token for Laravel. If omitted, falls back to the page meta tag. */
    csrfToken?: string;
    /** Called after at least one file uploads successfully. */
    onUploaded?: (response: unknown, file: File) => void;
    /** Called when all uploads finish (success or failure). Useful to refresh a list. */
    onAllSettled?: () => void;
    /** Optional className for the outer wrapper. */
    className?: string;
}

/**
 * Reusable drag-and-drop file uploader with client-side validation, per-file
 * progress bars and abort support. Submits each file as a separate multipart
 * POST so a single failure does not invalidate the others.
 *
 * Designed to plug into Laravel endpoints that accept a `file` field plus
 * any number of named metadata fields (e.g. `title`, `description`).
 *
 * Security boundaries:
 * - MIME and size validation here is convenience only; the server MUST
 *   re-validate (see `DocumentLibraryService::upload`).
 * - Image files get EXIF stripped server-side via Intervention Image.
 * - No URL is constructed from user input; only `endpoint` is used.
 */
export function DropzoneUploader({
    endpoint,
    accept = '*/*',
    acceptLabel,
    maxBytes = 25 * 1024 * 1024,
    fieldName = 'file',
    multiple = true,
    fields,
    csrfToken,
    onUploaded,
    onAllSettled,
    className,
}: DropzoneUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [staged, setStaged] = useState<StagedFile[]>([]);
    const [extras, setExtras] = useState<Record<string, string>>(() => {
        const initial: Record<string, string> = {};
        for (const field of fields ?? []) {
            if (field.defaultValue !== undefined) {
                initial[field.name] = field.defaultValue;
            }
        }
        return initial;
    });
    const [isOver, setIsOver] = useState(false);

    // Read CSRF token from the meta tag injected by Laravel's Blade layout.
    const csrf = csrfToken ?? readCsrfFromDom();

    useEffect(() => {
        return () => {
            // Abort any pending uploads if the component unmounts mid-flight.
            for (const item of staged) {
                item.abort?.();
            }
        };
    }, [staged]);

    const updateOne = (id: string, patch: Partial<StagedFile>) => {
        setStaged((prev) => prev.map((item) => (item.id === id ? { ...item, ...patch } : item)));
    };

    const validate = (file: File): string | null => {
        if (file.size > maxBytes) {
            return `Fichier trop volumineux (${formatBytes(file.size)} > ${formatBytes(maxBytes)}).`;
        }
        if (accept !== '*/*' && !matchesAccept(file, accept)) {
            return `Type ${file.type || 'inconnu'} non autorisé.`;
        }
        return null;
    };

    const beginUpload = (item: StagedFile) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint);
        xhr.setRequestHeader('Accept', 'application/json');
        if (csrf) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        }
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                const pct = Math.round((event.loaded / event.total) * 100);
                updateOne(item.id, { progress: pct });
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                let body: unknown = xhr.responseText;
                try {
                    body = JSON.parse(xhr.responseText);
                } catch {
                    /* tolerated — non-JSON response is fine */
                }
                updateOne(item.id, { progress: 100, status: 'done', abort: undefined });
                onUploaded?.(body, item.file);
            } else {
                let message = 'Échec de l\'envoi.';
                try {
                    const parsed = JSON.parse(xhr.responseText) as { message?: string };
                    if (parsed?.message) {
                        message = parsed.message;
                    }
                } catch {
                    /* keep default message */
                }
                updateOne(item.id, { status: 'error', errorMessage: message, abort: undefined });
            }
            maybeFinish();
        };

        xhr.onerror = () => {
            updateOne(item.id, { status: 'error', errorMessage: 'Connexion interrompue.', abort: undefined });
            maybeFinish();
        };

        const form = new FormData();
        form.append(fieldName, item.file);
        for (const [key, value] of Object.entries(extras)) {
            if (value !== '' && value !== undefined) {
                form.append(key, value);
            }
        }

        updateOne(item.id, {
            status: 'uploading',
            progress: 0,
            abort: () => xhr.abort(),
        });

        xhr.send(form);
    };

    const maybeFinish = () => {
        setStaged((prev) => {
            const allDone = prev.every((s) => s.status === 'done' || s.status === 'error');
            if (allDone) {
                onAllSettled?.();
            }
            return prev;
        });
    };

    const handleFiles = (incoming: FileList | File[]) => {
        const list = Array.from(incoming);
        const newItems: StagedFile[] = [];
        for (const file of list) {
            const error = validate(file);
            const id = `${file.name}-${file.size}-${file.lastModified}-${Math.random().toString(36).slice(2, 8)}`;
            newItems.push({
                id,
                file,
                progress: 0,
                status: error ? 'error' : 'staged',
                errorMessage: error ?? undefined,
            });
        }
        setStaged((prev) => [...prev, ...newItems]);
    };

    const startAll = () => {
        for (const item of staged) {
            if (item.status === 'staged') {
                beginUpload(item);
            }
        }
    };

    const remove = (id: string) => {
        setStaged((prev) => {
            const target = prev.find((item) => item.id === id);
            target?.abort?.();
            return prev.filter((item) => item.id !== id);
        });
    };

    const onFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        if (event.target.files) {
            handleFiles(event.target.files);
            event.target.value = '';
        }
    };

    const onDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsOver(false);
        if (event.dataTransfer.files.length > 0) {
            handleFiles(event.dataTransfer.files);
        }
    };

    const stagedCount = staged.filter((s) => s.status === 'staged').length;
    const uploadingCount = staged.filter((s) => s.status === 'uploading').length;

    return (
        <div className={cn('space-y-4', className)}>
            {/* Metadata fields */}
            {fields && fields.length > 0 && (
                <div className="grid gap-3 sm:grid-cols-2">
                    {fields.map((field) => (
                        <label key={field.name} className="block">
                            <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                                {field.label}
                                {field.required && <span className="ml-0.5 text-danger-500">*</span>}
                            </span>
                            <input
                                type="text"
                                name={field.name}
                                value={extras[field.name] ?? ''}
                                required={field.required}
                                onChange={(e) => setExtras((prev) => ({ ...prev, [field.name]: e.target.value }))}
                                className="mt-1 block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                            />
                            {field.help && (
                                <span className="mt-1 block text-[11px] text-ink-500 dark:text-ink-400">{field.help}</span>
                            )}
                        </label>
                    ))}
                </div>
            )}

            {/* Drop zone */}
            <div
                onDragEnter={(e) => {
                    e.preventDefault();
                    setIsOver(true);
                }}
                onDragOver={(e) => {
                    e.preventDefault();
                    setIsOver(true);
                }}
                onDragLeave={() => setIsOver(false)}
                onDrop={onDrop}
                onClick={() => inputRef.current?.click()}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        inputRef.current?.click();
                    }
                }}
                role="button"
                tabIndex={0}
                aria-label="Cliquer ou déposer pour téléverser"
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition-colors',
                    isOver
                        ? 'border-brand-400 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/30 dark:text-brand-300'
                        : 'border-ink-200 bg-ink-50/40 text-ink-600 hover:border-brand-300 hover:bg-brand-50/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:border-brand-500 dark:hover:bg-brand-900/30',
                )}
            >
                <div className="flex size-12 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm dark:bg-ink-700 dark:text-brand-400">
                    <UploadIcon />
                </div>
                <p className="mt-3 text-sm font-semibold">
                    Déposez vos fichiers ici, ou <span className="text-brand-600 underline-offset-2 hover:underline dark:text-brand-400">cliquez pour parcourir</span>
                </p>
                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                    {acceptLabel ?? 'Tous formats'} · max {formatBytes(maxBytes)} par fichier
                </p>
                <input
                    ref={inputRef}
                    type="file"
                    accept={accept}
                    multiple={multiple}
                    onChange={onFileChange}
                    className="hidden"
                />
            </div>

            {/* Staged file list */}
            {staged.length > 0 && (
                <div className="space-y-2">
                    {staged.map((item) => (
                        <StagedRow key={item.id} item={item} onRemove={() => remove(item.id)} />
                    ))}
                </div>
            )}

            {/* Actions */}
            {staged.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-ink-50/60 px-4 py-3 dark:bg-ink-800">
                    <p className="text-xs text-ink-500 dark:text-ink-400">
                        {stagedCount > 0 && `${stagedCount} en attente · `}
                        {uploadingCount > 0 && `${uploadingCount} en cours · `}
                        {staged.length} fichier{staged.length > 1 ? 's' : ''} au total
                    </p>
                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={() => setStaged([])}
                            disabled={uploadingCount > 0}
                            className="rounded-full border border-ink-200 bg-white px-4 py-1.5 text-xs font-medium text-ink-700 transition-colors hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                        >
                            Tout retirer
                        </button>
                        <button
                            type="button"
                            onClick={startAll}
                            disabled={stagedCount === 0}
                            className="rounded-full bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-ink-300 dark:disabled:bg-ink-600"
                        >
                            Téléverser ({stagedCount})
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

function StagedRow({ item, onRemove }: { item: StagedFile; onRemove: () => void }) {
    return (
        <div className="rounded-xl border border-ink-100 bg-white px-4 py-3 dark:border-ink-700/60 dark:bg-ink-800">
            <div className="flex items-center gap-3">
                <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500 dark:bg-ink-700 dark:text-ink-300">
                    <FileTypeGlyph mime={item.file.type} />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{item.file.name}</p>
                    <p className="text-[11px] text-ink-500 dark:text-ink-400">
                        {formatBytes(item.file.size)} · {item.file.type || 'type inconnu'}
                    </p>
                </div>
                <StatusBadge item={item} />
                {(item.status === 'staged' || item.status === 'error') && (
                    <button
                        type="button"
                        onClick={onRemove}
                        aria-label="Retirer ce fichier"
                        className="rounded-full p-1.5 text-ink-400 transition-colors hover:bg-danger-50 hover:text-danger-600 dark:text-ink-500 dark:hover:bg-danger-900/30"
                    >
                        <XIcon />
                    </button>
                )}
            </div>

            {item.status === 'uploading' && (
                <div className="mt-3">
                    <div className="h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                        <div
                            className="h-full rounded-full bg-brand-500 transition-[width] duration-200 ease-out"
                            style={{ width: `${item.progress}%` }}
                        />
                    </div>
                    <p className="mt-1 text-right text-[10px] font-mono text-ink-500 dark:text-ink-400">{item.progress}%</p>
                </div>
            )}

            {item.status === 'error' && item.errorMessage && (
                <p className="mt-2 text-xs text-danger-600 dark:text-danger-400">{item.errorMessage}</p>
            )}
        </div>
    );
}

function StatusBadge({ item }: { item: StagedFile }) {
    switch (item.status) {
        case 'staged':
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-ink-100 px-2.5 py-0.5 text-[11px] font-semibold text-ink-700 dark:bg-ink-700 dark:text-ink-300">
                    En attente
                </span>
            );
        case 'uploading':
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2.5 py-0.5 text-[11px] font-semibold text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    En cours · {item.progress}%
                </span>
            );
        case 'done':
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-sage-100 px-2.5 py-0.5 text-[11px] font-semibold text-sage-700 dark:bg-sage-900/40 dark:text-sage-300">
                    Téléversé
                </span>
            );
        case 'error':
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-danger-100 px-2.5 py-0.5 text-[11px] font-semibold text-danger-700 dark:bg-danger-900/40 dark:text-danger-300">
                    Erreur
                </span>
            );
    }
}

// ─── Helpers ────────────────────────────────────────────────────────────────
function readCsrfFromDom(): string | undefined {
    if (typeof document === 'undefined') return undefined;
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content;
}

function matchesAccept(file: File, accept: string): boolean {
    const fragments = accept.split(',').map((s) => s.trim().toLowerCase());
    const type = file.type.toLowerCase();
    const name = file.name.toLowerCase();
    return fragments.some((frag) => {
        if (frag === '*/*') return true;
        if (frag.startsWith('.')) return name.endsWith(frag);
        if (frag.endsWith('/*')) return type.startsWith(frag.slice(0, -1));
        return type === frag;
    });
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} ko`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
}

// ─── Icons ──────────────────────────────────────────────────────────────────
function UploadIcon() {
    return (
        <svg className="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function XIcon() {
    return (
        <svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
            <path d="M18 6L6 18M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function FileTypeGlyph({ mime }: { mime: string }) {
    if (mime.startsWith('image/')) {
        return (
            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="9" cy="9" r="2" />
                <path d="M21 15l-5-5L5 21" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    }
    if (mime.includes('pdf')) {
        return (
            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" strokeLinecap="round" strokeLinejoin="round" />
                <polyline points="14 2 14 8 20 8" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    }
    if (mime.includes('sheet') || mime.includes('excel')) {
        return (
            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <line x1="3" y1="9" x2="21" y2="9" />
                <line x1="3" y1="15" x2="21" y2="15" />
                <line x1="9" y1="3" x2="9" y2="21" />
                <line x1="15" y1="3" x2="15" y2="21" />
            </svg>
        );
    }
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" strokeLinecap="round" strokeLinejoin="round" />
            <polyline points="14 2 14 8 20 8" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
