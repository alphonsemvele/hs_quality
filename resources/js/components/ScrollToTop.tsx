import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

interface ScrollToTopProps {
    /** Vertical scroll threshold (in pixels) before the button appears. */
    threshold?: number;
}

/**
 * Floating "back to top" button.
 *
 * Mounted once in the dashboard layout. Listens to scroll with a passive
 * handler, shows the button after the user has scrolled past `threshold`
 * pixels. Clicking smooth-scrolls to the top. Hidden in print to keep
 * exported reports clean.
 */
export function ScrollToTop({ threshold = 600 }: ScrollToTopProps) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const onScroll = () => setVisible(window.scrollY > threshold);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, [threshold]);

    const scrollUp = () => {
        if (typeof window === 'undefined') return;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <button
            type="button"
            onClick={scrollUp}
            aria-label="Revenir en haut de la page"
            aria-hidden={!visible}
            tabIndex={visible ? 0 : -1}
            className={cn(
                'print:hidden fixed bottom-6 right-6 z-30 inline-flex size-11 items-center justify-center rounded-full border border-ink-200 bg-white text-ink-700 shadow-lg transition-all duration-200 hover:bg-ink-50 hover:text-ink-900 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:bg-ink-700',
                visible ? 'pointer-events-auto translate-y-0 opacity-100' : 'pointer-events-none translate-y-3 opacity-0',
            )}
        >
            <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
                <path d="M12 19V5M5 12l7-7 7 7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        </button>
    );
}
