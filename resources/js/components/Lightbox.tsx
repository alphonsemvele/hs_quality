import { cn } from '@/lib/utils';
import { useCallback, useEffect, useState } from 'react';

interface LightboxImage {
    src: string;
    alt?: string;
}

/**
 * Global image lightbox.
 *
 * Mounted once in the dashboard layout. Any `<img data-lightbox>` (or
 * `<a data-lightbox href="...">` that wraps a thumb) becomes clickable
 * to open fullscreen. Multiple images sharing the same
 * `data-lightbox-group` attribute become a navigable gallery — arrow
 * keys cycle, Esc closes.
 *
 * Image URLs are NOT validated against an allowlist — only pass URLs
 * coming from your own backend (same-origin or signed S3) to avoid
 * inadvertently rendering attacker-controlled content fullscreen.
 */
export function Lightbox() {
    const [images, setImages] = useState<LightboxImage[]>([]);
    const [index, setIndex] = useState(0);
    const isOpen = images.length > 0;

    const close = useCallback(() => {
        setImages([]);
        setIndex(0);
    }, []);

    const next = useCallback(() => {
        setIndex((i) => (i + 1) % Math.max(images.length, 1));
    }, [images.length]);

    const prev = useCallback(() => {
        setIndex((i) => (i - 1 + images.length) % Math.max(images.length, 1));
    }, [images.length]);

    useEffect(() => {
        if (typeof document === 'undefined') return;

        const onClick = (e: MouseEvent) => {
            const target = e.target as HTMLElement | null;
            if (!target) return;
            const trigger = target.closest('[data-lightbox]') as HTMLElement | null;
            if (!trigger) return;

            // Resolve image url from either the element itself (img) or an
            // explicit `data-lightbox` attribute value, or the href of an <a>.
            let src: string | null = null;
            let alt: string | undefined;
            if (trigger instanceof HTMLImageElement) {
                src = trigger.currentSrc || trigger.src;
                alt = trigger.alt;
            } else if (trigger instanceof HTMLAnchorElement) {
                src = trigger.href;
                alt = trigger.getAttribute('data-lightbox-alt') ?? undefined;
            }
            const datasetSrc = trigger.getAttribute('data-lightbox');
            if (datasetSrc && datasetSrc !== '' && datasetSrc !== 'true') {
                src = datasetSrc;
            }
            if (!src) return;

            e.preventDefault();
            const group = trigger.getAttribute('data-lightbox-group');
            if (group) {
                const peers = Array.from(
                    document.querySelectorAll(`[data-lightbox-group='${CSS.escape(group)}']`),
                ) as HTMLElement[];
                const collected: LightboxImage[] = peers
                    .map((el) => {
                        if (el instanceof HTMLImageElement) {
                            return { src: el.currentSrc || el.src, alt: el.alt };
                        }
                        if (el instanceof HTMLAnchorElement) {
                            return {
                                src: el.getAttribute('data-lightbox') || el.href,
                                alt: el.getAttribute('data-lightbox-alt') ?? undefined,
                            };
                        }
                        const ds = el.getAttribute('data-lightbox');
                        return ds ? { src: ds, alt: el.getAttribute('data-lightbox-alt') ?? undefined } : null;
                    })
                    .filter((x): x is LightboxImage => x !== null);
                const startIndex = Math.max(
                    0,
                    collected.findIndex((img) => img.src === src),
                );
                setImages(collected);
                setIndex(startIndex);
            } else {
                setImages([{ src, alt }]);
                setIndex(0);
            }
        };

        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, []);

    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') close();
            else if (e.key === 'ArrowRight' && images.length > 1) next();
            else if (e.key === 'ArrowLeft' && images.length > 1) prev();
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [isOpen, images.length, next, prev, close]);

    if (!isOpen) return null;

    const current = images[index];
    const showNav = images.length > 1;

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Aperçu de l'image"
            className="fixed inset-0 z-[60] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
            onClick={close}
        >
            <button
                type="button"
                onClick={(e) => {
                    e.stopPropagation();
                    close();
                }}
                aria-label="Fermer l'aperçu"
                className="absolute right-4 top-4 inline-flex size-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
            >
                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
                </svg>
            </button>

            {showNav && (
                <>
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            prev();
                        }}
                        aria-label="Image précédente"
                        className="absolute left-4 top-1/2 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
                    >
                        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M15 6l-6 6 6 6" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            next();
                        }}
                        aria-label="Image suivante"
                        className="absolute right-16 top-1/2 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
                    >
                        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M9 6l6 6-6 6" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </button>
                </>
            )}

            <figure
                className={cn('relative flex max-h-full max-w-full flex-col items-center')}
                onClick={(e) => e.stopPropagation()}
            >
                <img
                    src={current.src}
                    alt={current.alt ?? ''}
                    className="max-h-[85vh] max-w-[90vw] rounded-lg object-contain shadow-2xl"
                />
                {(current.alt || showNav) && (
                    <figcaption className="mt-3 text-center text-xs text-white/80">
                        {current.alt}
                        {showNav && (
                            <span className="ml-2 font-mono text-white/50">
                                {index + 1} / {images.length}
                            </span>
                        )}
                    </figcaption>
                )}
            </figure>
        </div>
    );
}
