import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head } from '@inertiajs/react';
import { LegalProseStyle } from './legal-mentions';

export default function AccessibilityPage() {
    const updatedAt = '6 mai 2026';

    return (
        <MarketingPage>
            <Head title="Déclaration d'accessibilité — HS Quality" />

            <article className="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-20">
                <header>
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Légal
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Déclaration d'accessibilité</h1>
                    <p className="mt-2 text-xs text-ink-400">Dernière mise à jour : {updatedAt}</p>
                </header>

                <div className="prose-legal mt-10">
                    <h2>Engagement</h2>
                    <p>
                        HS Quality s'engage à rendre son site et sa plateforme accessibles aux personnes en situation de
                        handicap, conformément aux Web Content Accessibility Guidelines (WCAG) 2.1 niveau AA et au
                        Référentiel Général d'Amélioration de l'Accessibilité (RGAA) version 4.1.
                    </p>

                    <h2>État de conformité</h2>
                    <p>
                        Le présent site et la plateforme sont en cours d'audit. Une déclaration de conformité complète sera
                        publiée à l'issue du premier audit externe RGAA, prévu à la fin de la Phase 1 (M4).
                    </p>

                    <h2>Mesures déjà mises en œuvre</h2>
                    <ul>
                        <li>Conformité WCAG 2.1 AA pour l'interface web (objectif design)</li>
                        <li>Taille de police et boutons adaptés aux utilisateurs seniors ou avec difficultés visuelles</li>
                        <li>Mode sombre disponible</li>
                        <li>Support de la dictée vocale pour la saisie des comptes-rendus (CDC §6.4)</li>
                        <li>Mode simplifié pour les intervenants peu à l'aise avec le numérique</li>
                        <li>Interface mobile conçue pour une utilisation avec des gants ou en situation de mobilité</li>
                        <li>Navigation clavier complète</li>
                        <li>Skip-link « Aller au contenu principal » sur chaque page</li>
                        <li>Contraste de couleur respectant les ratios WCAG AA</li>
                        <li>Étiquettes ARIA pour les composants interactifs</li>
                    </ul>

                    <h2>Limitations connues</h2>
                    <p>
                        Certains modules en cours de développement ne respectent pas encore intégralement les critères WCAG
                        2.1 AA. Ces écarts sont identifiés et planifiés dans le plan d'amélioration continue de l'accessibilité.
                    </p>

                    <h2>Retour utilisateur</h2>
                    <p>
                        Si vous rencontrez un défaut d'accessibilité empêchant l'accès à un contenu ou à une fonctionnalité,
                        contactez-nous à <a href="mailto:accessibilite@hsquality.fr">accessibilite@hsquality.fr</a>. Nous nous
                        engageons à vous répondre sous 5 jours ouvrés et à proposer une solution alternative si nécessaire.
                    </p>

                    <h2>Voies de recours</h2>
                    <p>
                        Si une réponse ne vous a pas été apportée, vous pouvez :
                    </p>
                    <ul>
                        <li>
                            Contacter le Défenseur des droits (
                            <a href="https://www.defenseurdesdroits.fr" target="_blank" rel="noopener noreferrer">
                                www.defenseurdesdroits.fr
                            </a>
                            )
                        </li>
                        <li>
                            Adresser un courrier postal au Défenseur des droits, Libre réponse 71120, 75342 Paris CEDEX 07
                        </li>
                    </ul>
                </div>
            </article>
            <LegalProseStyle />
        </MarketingPage>
    );
}
