import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import {
    acceptAll,
    readConsent,
    rejectNonEssential,
    writeConsent,
    type ConsentChoice,
} from '@/lib/cookie-consent';
import { cn } from '@/lib/utils';

type Mode = 'banner' | 'settings';

/**
 * RGPD cookie consent banner.
 *
 * Shown on every page load until the visitor has made an explicit
 * decision (accept all / reject non-essential / customise). Reads/writes
 * via the shared helper so the preferences page can rehydrate from the
 * same source.
 *
 * The banner self-mounts only when no decision has been recorded.
 * It also listens to the 'cookie-consent-changed' window event so the
 * preferences page can re-show it after a "réinitialiser" action.
 */
export function CookieConsentBanner() {
    const [visible, setVisible] = useState(false);
    const [mode, setMode] = useState<Mode>('banner');
    const [analytics, setAnalytics] = useState<ConsentChoice>('denied');
    const [marketing, setMarketing] = useState<ConsentChoice>('denied');

    useEffect(() => {
        const sync = () => {
            const consent = readConsent();
            setVisible(consent === null);
            if (consent) {
                setAnalytics(consent.analytics);
                setMarketing(consent.marketing);
            }
        };
        sync();
        const onChange = () => sync();
        window.addEventListener('cookie-consent-changed', onChange);
        return () => window.removeEventListener('cookie-consent-changed', onChange);
    }, []);

    if (!visible) return null;

    const close = () => setVisible(false);

    const handleAcceptAll = () => {
        acceptAll();
        close();
    };

    const handleReject = () => {
        rejectNonEssential();
        close();
    };

    const handleSave = () => {
        writeConsent({ analytics, marketing });
        close();
    };

    return (
        <div
            role="dialog"
            aria-modal="false"
            aria-labelledby="cookie-banner-title"
            aria-describedby="cookie-banner-desc"
            className="fixed inset-x-2 bottom-2 z-[110] sm:inset-x-4 sm:bottom-4"
        >
            <div className="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-2xl ring-1 ring-black/5 dark:border-ink-700/60 dark:bg-ink-800 dark:ring-white/5">
                <div className="px-5 py-4 sm:px-6 sm:py-5">
                    <div className="flex items-start gap-3">
                        <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                            <CookieIcon />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p
                                id="cookie-banner-title"
                                className="text-sm font-semibold text-ink-900 dark:text-white"
                            >
                                Cookies & confidentialité
                            </p>
                            <p
                                id="cookie-banner-desc"
                                className="mt-1 text-xs leading-relaxed text-ink-600 dark:text-ink-300"
                            >
                                Nous utilisons des cookies strictement nécessaires au fonctionnement
                                de la plateforme (session, sécurité). D'autres cookies, optionnels,
                                pourraient nous aider à mesurer l'usage du service et améliorer
                                l'expérience. Vous pouvez les accepter, les refuser, ou les
                                paramétrer. Vos données de santé ne sont jamais utilisées à des
                                fins de mesure.{' '}
                                <Link
                                    href="/confidentialite"
                                    className="font-medium text-brand-600 underline decoration-dotted underline-offset-2 hover:text-brand-700 dark:text-brand-300"
                                >
                                    Politique de confidentialité
                                </Link>
                            </p>
                        </div>
                    </div>

                    {mode === 'settings' && (
                        <fieldset className="mt-4 space-y-2 rounded-xl border border-ink-100 bg-ink-50/60 p-3 dark:border-ink-700/60 dark:bg-ink-900/30">
                            <legend className="sr-only">Catégories de cookies</legend>
                            <CategoryRow
                                label="Strictement nécessaires"
                                description="Session, CSRF, préférences UI. Indispensables ; non désactivables."
                                checked
                                disabled
                                onChange={() => undefined}
                            />
                            <CategoryRow
                                label="Mesure d'audience"
                                description="Statistiques d'usage anonymisées (aucun service tiers actif aujourd'hui)."
                                checked={analytics === 'granted'}
                                onChange={(v) => setAnalytics(v ? 'granted' : 'denied')}
                            />
                            <CategoryRow
                                label="Marketing"
                                description="Personnalisation hors plateforme (aucun service tiers actif aujourd'hui)."
                                checked={marketing === 'granted'}
                                onChange={(v) => setMarketing(v ? 'granted' : 'denied')}
                            />
                        </fieldset>
                    )}

                    <div className="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
                        {mode === 'banner' ? (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setMode('settings')}
                                    className={btnSecondary}
                                >
                                    Personnaliser
                                </button>
                                <button
                                    type="button"
                                    onClick={handleReject}
                                    className={btnSecondary}
                                >
                                    Refuser les optionnels
                                </button>
                                <button
                                    type="button"
                                    onClick={handleAcceptAll}
                                    className={btnPrimary}
                                >
                                    Tout accepter
                                </button>
                            </>
                        ) : (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setMode('banner')}
                                    className={btnSecondary}
                                >
                                    Retour
                                </button>
                                <button
                                    type="button"
                                    onClick={handleSave}
                                    className={btnPrimary}
                                >
                                    Enregistrer mes choix
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

const btnPrimary =
    'inline-flex items-center justify-center rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40';

const btnSecondary =
    'inline-flex items-center justify-center rounded-full border border-ink-200 bg-white px-5 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-ink-300 hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:bg-ink-700/40';

function CategoryRow({
    label,
    description,
    checked,
    disabled,
    onChange,
}: {
    label: string;
    description: string;
    checked: boolean;
    disabled?: boolean;
    onChange: (v: boolean) => void;
}) {
    return (
        <label
            className={cn(
                'flex items-start gap-3 rounded-lg px-2 py-2 transition-colors',
                disabled ? 'opacity-70' : 'hover:bg-white dark:hover:bg-ink-800/60',
            )}
        >
            <input
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(e) => onChange(e.target.checked)}
                className="mt-0.5 size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
            />
            <span className="flex-1">
                <span className="block text-xs font-semibold text-ink-900 dark:text-white">
                    {label}
                </span>
                <span className="mt-0.5 block text-[11px] leading-relaxed text-ink-500 dark:text-ink-400">
                    {description}
                </span>
            </span>
        </label>
    );
}

function CookieIcon() {
    return (
        <svg
            className="size-5"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.75}
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"
            />
            <circle cx="8.5" cy="11.5" r="0.75" fill="currentColor" />
            <circle cx="13" cy="14.5" r="0.75" fill="currentColor" />
            <circle cx="15.5" cy="9.5" r="0.75" fill="currentColor" />
        </svg>
    );
}
