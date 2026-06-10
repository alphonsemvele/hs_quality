import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

interface GoToMapping {
    key: string;
    href: string;
    label: string;
}

/**
 * Vim-style two-key navigation: press `g`, then one of the keys below,
 * within 1.2 seconds to jump to the matching dashboard page.
 *
 * `g` is consumed only if the next key is actually a registered mapping;
 * otherwise it's released as a normal keystroke so it doesn't shadow
 * typing in the address bar / forms.
 *
 * Mounted once in the dashboard layout next to the other global affordances.
 */
const MAPPINGS: GoToMapping[] = [
    { key: 'd', href: '/dashboard', label: 'Tableau de bord' },
    { key: 'i', href: '/interventions', label: 'Interventions' },
    { key: 'b', href: '/beneficiaries', label: 'Bénéficiaires' },
    { key: 'a', href: '/audits', label: 'Audits' },
    { key: 'p', href: '/plans-amelioration', label: "Plans d'amélioration" },
    { key: 'q', href: '/qvct', label: 'QVCT' },
    { key: 'n', href: '/incidents', label: 'Incidents' },
    { key: 'f', href: '/formations', label: 'Formations' },
    { key: 'c', href: '/communication', label: 'Communication' },
];

const PREFIX_TIMEOUT = 1200;

function isTextInput(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) return false;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return true;
    return target.isContentEditable;
}

export function GoToShortcuts() {
    const armedRef = useRef<number | null>(null);

    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.metaKey || e.ctrlKey || e.altKey) return;
            if (isTextInput(e.target)) return;

            // We're "armed" after a `g`: next keystroke is the navigation key.
            if (armedRef.current !== null) {
                window.clearTimeout(armedRef.current);
                armedRef.current = null;
                const mapping = MAPPINGS.find((m) => m.key === e.key.toLowerCase());
                if (mapping) {
                    e.preventDefault();
                    router.visit(mapping.href);
                }
                return;
            }

            if (e.key === 'g') {
                armedRef.current = window.setTimeout(() => {
                    armedRef.current = null;
                }, PREFIX_TIMEOUT);
            }
        };
        document.addEventListener('keydown', handler);
        return () => {
            document.removeEventListener('keydown', handler);
            if (armedRef.current !== null) window.clearTimeout(armedRef.current);
        };
    }, []);

    return null;
}

export const GO_TO_MAPPINGS = MAPPINGS;
