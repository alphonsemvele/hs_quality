import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head } from '@inertiajs/react';
import { LegalProseStyle } from './legal-mentions';

export default function PrivacyPage() {
    const updatedAt = '6 mai 2026';

    return (
        <MarketingPage>
            <Head title="Politique de confidentialité — HS Quality" />

            <article className="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-20">
                <header>
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Légal
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Politique de confidentialité</h1>
                    <p className="mt-2 text-xs text-ink-400">Dernière mise à jour : {updatedAt}</p>
                </header>

                <div className="prose-legal mt-10">
                    <p>
                        HS Quality SAS (« nous ») s'engage à protéger la vie privée des personnes qui utilisent notre plateforme
                        ainsi que des bénéficiaires dont les données sont traitées par nos clients. La présente politique
                        explique comment nous collectons, utilisons, stockons et sécurisons les données personnelles, en
                        conformité avec le Règlement Général sur la Protection des Données (UE 2016/679 — RGPD).
                    </p>

                    <h2>1. Responsable de traitement</h2>
                    <p>
                        Pour les données collectées via le site public (formulaire de contact, navigation), HS Quality SAS est
                        responsable de traitement. Pour les données traitées sur la plateforme SaaS au nom des structures
                        clientes, HS Quality agit en qualité de <strong>sous-traitant au sens de l'article 28 du RGPD</strong>.
                    </p>

                    <h2>2. Données collectées et finalités</h2>
                    <h3>Site public</h3>
                    <ul>
                        <li>Formulaire de contact : nom, prénom, email, téléphone, structure, type, taille, message</li>
                        <li>Logs techniques : adresse IP, user-agent, horodatage (rétention 12 mois)</li>
                    </ul>
                    <h3>Plateforme SaaS</h3>
                    <ul>
                        <li>Données d'identification des intervenants et utilisateurs (nom, email, fonction)</li>
                        <li>Données de santé des bénéficiaires (au sens de l'article 9 du RGPD) traitées au nom du client</li>
                        <li>Données QVCT pseudonymisées (réponses aux baromètres)</li>
                        <li>Logs d'accès aux dossiers (audit trail)</li>
                    </ul>

                    <h2>3. Bases légales</h2>
                    <ul>
                        <li><strong>Consentement</strong> : formulaire de contact, baromètre QVCT</li>
                        <li><strong>Exécution d'un contrat</strong> : usage de la plateforme par les clients abonnés</li>
                        <li><strong>Obligation légale</strong> : conservation des audit logs (Code de la santé publique)</li>
                        <li><strong>Intérêt légitime</strong> : sécurité et journalisation</li>
                    </ul>

                    <h2>4. Durées de conservation</h2>
                    <ul>
                        <li>Données du formulaire de contact : 24 mois après le dernier échange</li>
                        <li>Données plateforme : pour la durée du contrat + 5 ans (audit HAS quinquennal)</li>
                        <li>Audit logs d'accès aux données de santé : 6 ans (Code de la santé publique)</li>
                        <li>Données pseudonymisées de benchmark : durée indéterminée (dataset agrégé)</li>
                    </ul>

                    <h2>5. Hébergement et localisation</h2>
                    <p>
                        Toutes les données sont hébergées exclusivement en France, chez un prestataire HDS-certifié (AWS
                        Europe Paris ou OVHcloud France). Aucun transfert hors Union Européenne n'est effectué.
                    </p>

                    <h2>6. Sécurité</h2>
                    <ul>
                        <li>Chiffrement au repos (AES-256) et en transit (TLS 1.3)</li>
                        <li>Authentification multi-facteurs obligatoire pour les rôles à risque</li>
                        <li>Cloisonnement multi-tenant strict (chaque structure isolée)</li>
                        <li>Audit log exhaustif des accès aux données de santé</li>
                        <li>Audit de sécurité annuel par un prestataire indépendant certifié</li>
                    </ul>

                    <h2>7. Vos droits (articles 15 à 22 du RGPD)</h2>
                    <p>
                        Vous disposez des droits suivants sur vos données personnelles : accès, rectification, effacement
                        (« droit à l'oubli »), limitation du traitement, opposition, portabilité, et le droit de définir des
                        directives post-mortem. Pour les exercer, contactez notre DPO à
                        <a href="mailto:dpo@hsquality.fr"> dpo@hsquality.fr</a>.
                    </p>
                    <p>
                        Vous disposez également du droit d'introduire une réclamation auprès de la CNIL (
                        <a href="https://www.cnil.fr" target="_blank" rel="noopener noreferrer">www.cnil.fr</a>).
                    </p>

                    <h2>8. Cookies</h2>
                    <p>
                        Le site public utilise uniquement des cookies strictement nécessaires (session, CSRF). Aucun cookie
                        de tracking publicitaire n'est déposé. Vous pouvez à tout moment configurer votre navigateur pour
                        refuser les cookies.
                    </p>

                    <h2>9. Sous-traitants</h2>
                    <p>
                        Liste à jour des sous-traitants disposant d'un accès aux données personnelles disponible sur demande
                        à <a href="mailto:dpo@hsquality.fr">dpo@hsquality.fr</a>. Tous nos sous-traitants sont liés par
                        contrat aux exigences de l'article 28 du RGPD.
                    </p>

                    <h2>10. Mise à jour</h2>
                    <p>
                        Cette politique peut être mise à jour. La date de dernière révision est indiquée en haut de page.
                        Toute modification substantielle est notifiée aux utilisateurs concernés.
                    </p>
                </div>
            </article>
            <LegalProseStyle />
        </MarketingPage>
    );
}
