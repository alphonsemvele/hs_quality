/**
 * RGPD cookie consent — single source of truth for what categories of
 * cookies the visitor has accepted. CNIL recommends a 13-month retention
 * for the consent itself.
 *
 * Three categories:
 *   - necessary: session cookies, CSRF token. Always on; no opt-out.
 *   - analytics: future product telemetry (none active today).
 *   - marketing: future ads/retargeting (none active today).
 *
 * Storage: localStorage (single source) + a non-HttpOnly cookie so
 * server-rendered Blade pages can also read it without a roundtrip.
 */

export type ConsentChoice = 'granted' | 'denied';

export interface CookieConsent {
    version: 1;
    necessary: 'granted';
    analytics: ConsentChoice;
    marketing: ConsentChoice;
    decidedAt: string;
}

const STORAGE_KEY = 'qd.cookie-consent.v1';
const COOKIE_NAME = 'qd_cookie_consent';
const RETENTION_DAYS = 395; // 13 months — CNIL recommendation

/** Read the consent record. Returns null if the user hasn't decided yet. */
export function readConsent(): CookieConsent | null {
    if (typeof window === 'undefined') return null;
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw) as CookieConsent;
        if (parsed?.version !== 1) return null;
        return parsed;
    } catch {
        return null;
    }
}

/** Persist a consent decision in localStorage and a 13-month cookie. */
export function writeConsent(choice: Omit<CookieConsent, 'version' | 'necessary' | 'decidedAt'>): CookieConsent {
    const consent: CookieConsent = {
        version: 1,
        necessary: 'granted',
        analytics: choice.analytics,
        marketing: choice.marketing,
        decidedAt: new Date().toISOString(),
    };

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));

        const expires = new Date();
        expires.setDate(expires.getDate() + RETENTION_DAYS);
        const value = encodeURIComponent(`${consent.analytics}:${consent.marketing}`);
        document.cookie = `${COOKIE_NAME}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;

        window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: consent }));
    }

    return consent;
}

export function acceptAll(): CookieConsent {
    return writeConsent({ analytics: 'granted', marketing: 'granted' });
}

export function rejectNonEssential(): CookieConsent {
    return writeConsent({ analytics: 'denied', marketing: 'denied' });
}

/** Wipe the decision — used by the preferences page "réinitialiser" action. */
export function resetConsent(): void {
    if (typeof window === 'undefined') return;
    window.localStorage.removeItem(STORAGE_KEY);
    document.cookie = `${COOKIE_NAME}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax`;
    window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: null }));
}

export function hasResponded(): boolean {
    return readConsent() !== null;
}
