import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    acceptAll,
    readConsent,
    rejectNonEssential,
    resetConsent,
    writeConsent,
    type ConsentChoice,
    type CookieConsent,
} from '@/lib/cookie-consent';
import { LegalProseStyle } from './legal-mentions';

const updatedAt = '19 juin 2026';

export default function CookiesPage() {
    const [consent, setConsent] = useState<CookieConsent | null>(null);
    const [analytics, setAnalytics] = useState<ConsentChoice>('denied');
    const [marketing, setMarketing] = useState<ConsentChoice>('denied');
    const [savedAt, setSavedAt] = useState<number | null>(null);

    useEffect(() => {
        const sync = () => {
            const c = readConsent();
            setConsent(c);
            setAnalytics(c?.analytics ?? 'denied');
            setMarketing(c?.marketing ?? 'denied');
        };
        sync();
        window.addEventListener('cookie-consent-changed', sync);
        return () => window.removeEventListener('cookie-consent-changed', sync);
    }, []);

    const flashSaved = () => {
        setSavedAt(Date.now());
        window.setTimeout(() => setSavedAt(null), 2500);
    };

    return (
        <MarketingPage>
            <Head title="Préférences cookies — HS Quality" />
            <LegalProseStyle />

            <article className="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-20">
                <header>
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Confidentialité
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                        Préférences cookies
                    </h1>
                    <p className="mt-2 text-xs text-ink-400">Dernière mise à jour : {updatedAt}</p>
                </header>

                <div className="prose-legal mt-10">
                    <p>
                        Cette page vous permet de revoir et modifier à tout moment votre consentement aux cookies
                        non essentiels. Les cookies strictement nécessaires (session, sécurité, CSRF) restent
                        toujours actifs car ils sont indispensables au fonctionnement de la plateforme.
                    </p>

                    <h2>État actuel</h2>
                    {consent ? (
                        <p>
                            Vos préférences ont été enregistrées le{' '}
                            <strong>{new Date(consent.decidedAt).toLocaleString('fr-FR')}</strong>. Elles seront
                            conservées 13 mois conformément aux recommandations de la CNIL.
                        </p>
                    ) : (
                        <p>
                            Vous n'avez pas encore exprimé de choix. La bannière s'affiche à votre prochain
                            chargement de page tant qu'une décision n'a pas été prise.
                        </p>
                    )}
                </div>

                <section className="mt-8 space-y-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-sm">
                    <CategoryRow
                        label="Strictement nécessaires"
                        description="Session de connexion, jeton CSRF, préférences UI (thème, sidebar). Indispensables au service ; ne peuvent pas être désactivés."
                        checked
                        disabled
                        onChange={() => undefined}
                    />
                    <CategoryRow
                        label="Mesure d'audience"
                        description="Statistiques d'usage anonymisées (pages consultées, temps de session). Aucun service tiers actif aujourd'hui — votre choix sera respecté dès leur introduction."
                        checked={analytics === 'granted'}
                        onChange={(v) => setAnalytics(v ? 'granted' : 'denied')}
                    />
                    <CategoryRow
                        label="Marketing"
                        description="Personnalisation hors plateforme (retargeting, mesure d'acquisition). Aucun service tiers actif aujourd'hui."
                        checked={marketing === 'granted'}
                        onChange={(v) => setMarketing(v ? 'granted' : 'denied')}
                    />
                </section>

                <div className="mt-5 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            writeConsent({ analytics, marketing });
                            flashSaved();
                        }}
                        className="inline-flex items-center justify-center rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700"
                    >
                        Enregistrer mes choix
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            acceptAll();
                            flashSaved();
                        }}
                        className="inline-flex items-center justify-center rounded-full border border-ink-200 bg-white px-5 py-2 text-sm font-medium text-ink-700 transition-colors hover:bg-ink-50"
                    >
                        Tout accepter
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            rejectNonEssential();
                            flashSaved();
                        }}
                        className="inline-flex items-center justify-center rounded-full border border-ink-200 bg-white px-5 py-2 text-sm font-medium text-ink-700 transition-colors hover:bg-ink-50"
                    >
                        Tout refuser
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            resetConsent();
                            flashSaved();
                        }}
                        className="inline-flex items-center justify-center rounded-full px-5 py-2 text-sm font-medium text-ink-500 transition-colors hover:text-ink-700"
                    >
                        Réinitialiser
                    </button>
                    {savedAt && (
                        <span className="text-xs font-medium text-sage-600" role="status">
                            ✓ Préférences enregistrées
                        </span>
                    )}
                </div>

                <div className="prose-legal mt-12">
                    <h2>Cookies déposés aujourd'hui</h2>
                    <table className="w-full border-collapse text-sm">
                        <thead>
                            <tr className="border-b border-ink-200 text-left">
                                <th className="py-2 font-semibold text-ink-900">Cookie</th>
                                <th className="py-2 font-semibold text-ink-900">Finalité</th>
                                <th className="py-2 font-semibold text-ink-900">Durée</th>
                            </tr>
                        </thead>
                        <tbody className="text-ink-600">
                            <tr className="border-b border-ink-100">
                                <td className="py-2 font-mono text-xs">XSRF-TOKEN</td>
                                <td className="py-2">Protection CSRF (Laravel)</td>
                                <td className="py-2">Session</td>
                            </tr>
                            <tr className="border-b border-ink-100">
                                <td className="py-2 font-mono text-xs">hs_quality_session</td>
                                <td className="py-2">Identifiant de session authentifiée</td>
                                <td className="py-2">2 h</td>
                            </tr>
                            <tr className="border-b border-ink-100">
                                <td className="py-2 font-mono text-xs">qd_cookie_consent</td>
                                <td className="py-2">Mémorise votre choix de consentement</td>
                                <td className="py-2">13 mois</td>
                            </tr>
                            <tr>
                                <td className="py-2 font-mono text-xs">qd.theme</td>
                                <td className="py-2">Préférence de thème (clair/sombre)</td>
                                <td className="py-2">12 mois</td>
                            </tr>
                        </tbody>
                    </table>

                    <h2>Vos droits</h2>
                    <p>
                        Conformément au RGPD vous pouvez à tout moment accéder à vos données, les rectifier, demander
                        leur portabilité ou leur effacement. Consultez la{' '}
                        <a href="/confidentialite">politique de confidentialité</a> pour la procédure complète ou
                        contactez notre DPO à l'adresse <strong>dpo@hsquality.fr</strong>.
                    </p>
                </div>
            </article>
        </MarketingPage>
    );
}

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
        <label className="flex items-start gap-3 rounded-xl px-2 py-2.5">
            <input
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(e) => onChange(e.target.checked)}
                className="mt-1 size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
            />
            <span className="flex-1">
                <span className="block text-sm font-semibold text-ink-900">{label}</span>
                <span className="mt-0.5 block text-xs leading-relaxed text-ink-500">{description}</span>
            </span>
            <span
                className={`shrink-0 self-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${
                    disabled
                        ? 'bg-ink-100 text-ink-500'
                        : checked
                            ? 'bg-sage-50 text-sage-700'
                            : 'bg-ink-50 text-ink-500'
                }`}
            >
                {disabled ? 'Toujours actif' : checked ? 'Activé' : 'Désactivé'}
            </span>
        </label>
    );
}
