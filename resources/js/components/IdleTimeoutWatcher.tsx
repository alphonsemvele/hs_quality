import { Modal } from '@/components/ui';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface IdleTimeoutWatcherProps {
    /** Minutes of inactivity before the warning modal opens. Default 15 min. */
    idleMinutes?: number;
    /** Seconds the user has to react in the warning modal before auto-logout. */
    graceSeconds?: number;
}

const ACTIVITY_EVENTS: Array<keyof DocumentEventMap> = [
    'mousemove',
    'mousedown',
    'keydown',
    'scroll',
    'touchstart',
    'visibilitychange',
];

/**
 * Session idle watcher.
 *
 * After `idleMinutes` of no user activity (mouse, keyboard, scroll,
 * touch, tab focus), a modal pops asking the user to confirm they're
 * still around. If they don't react within `graceSeconds`, we POST to
 * /logout and Fortify handles the rest.
 *
 * Activity detection is throttled to fire at most once every 30s to
 * avoid flooding state updates on heavy interactions.
 *
 * Pure frontend — does NOT change the server-side session TTL. Use
 * SESSION_LIFETIME on the backend if you want a hard cap.
 */
export function IdleTimeoutWatcher({ idleMinutes = 15, graceSeconds = 30 }: IdleTimeoutWatcherProps) {
    const [warning, setWarning] = useState(false);
    const [countdown, setCountdown] = useState(graceSeconds);
    const lastActivity = useRef<number>(Date.now());
    const checkRef = useRef<ReturnType<typeof setInterval> | null>(null);
    const countdownRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const idleMs = idleMinutes * 60 * 1000;

    // ── Track activity (throttled) ─────────────────────────────
    useEffect(() => {
        let lastThrottle = 0;
        const onActivity = () => {
            const now = Date.now();
            if (now - lastThrottle < 30_000) return;
            lastThrottle = now;
            lastActivity.current = now;
            if (warning) {
                // user came back during the warning — close it.
                setWarning(false);
            }
        };
        for (const event of ACTIVITY_EVENTS) {
            document.addEventListener(event, onActivity, { passive: true });
        }
        return () => {
            for (const event of ACTIVITY_EVENTS) {
                document.removeEventListener(event, onActivity);
            }
        };
    }, [warning]);

    // ── Idle polling ────────────────────────────────────────────
    useEffect(() => {
        checkRef.current = setInterval(() => {
            if (warning) return;
            const elapsed = Date.now() - lastActivity.current;
            if (elapsed >= idleMs) {
                setWarning(true);
                setCountdown(graceSeconds);
            }
        }, 30_000);
        return () => {
            if (checkRef.current) clearInterval(checkRef.current);
        };
    }, [idleMs, graceSeconds, warning]);

    // ── Countdown while warning is open ─────────────────────────
    useEffect(() => {
        if (!warning) {
            if (countdownRef.current) clearInterval(countdownRef.current);
            return;
        }
        countdownRef.current = setInterval(() => {
            setCountdown((c) => {
                if (c <= 1) {
                    if (countdownRef.current) clearInterval(countdownRef.current);
                    // Auto logout — POST through Inertia so CSRF works.
                    router.post('/logout');
                    return 0;
                }
                return c - 1;
            });
        }, 1000);
        return () => {
            if (countdownRef.current) clearInterval(countdownRef.current);
        };
    }, [warning]);

    const stayActive = (): void => {
        lastActivity.current = Date.now();
        setWarning(false);
    };

    const logoutNow = (): void => {
        router.post('/logout');
    };

    return (
        <Modal
            open={warning}
            onClose={stayActive}
            title="Vous êtes inactif"
            iconTone="warning"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            }
            closeOnBackdrop={false}
        >
            <div className="space-y-4">
                <p className="text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                    Pour la sécurité des données de santé, votre session sera fermée dans{' '}
                    <span className="font-mono text-base font-bold text-ink-900 dark:text-white">
                        {countdown}
                    </span>{' '}
                    secondes.
                </p>
                <p className="text-xs text-ink-500 dark:text-ink-400">
                    Toute activité (déplacer la souris, taper une touche) relance le compteur.
                </p>

                <div className="flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        onClick={logoutNow}
                        className="rounded-full border border-ink-200 bg-white px-5 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-ink-300 hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        Se déconnecter
                    </button>
                    <button
                        type="button"
                        onClick={stayActive}
                        autoFocus
                        className="rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40"
                    >
                        Rester connecté
                    </button>
                </div>
            </div>
        </Modal>
    );
}
