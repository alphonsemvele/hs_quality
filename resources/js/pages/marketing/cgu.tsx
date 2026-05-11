import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head } from '@inertiajs/react';
import { LegalProseStyle } from './legal-mentions';

export default function CguPage() {
    const updatedAt = '6 mai 2026';

    return (
        <MarketingPage>
            <Head title="Conditions générales d'utilisation — HS Quality" />

            <article className="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-20">
                <header>
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Légal
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                        Conditions générales d'utilisation
                    </h1>
                    <p className="mt-2 text-xs text-ink-400">Dernière mise à jour : {updatedAt}</p>
                </header>

                <div className="prose-legal mt-10">
                    <h2>1. Objet</h2>
                    <p>
                        Les présentes conditions générales d'utilisation (« CGU ») régissent l'accès et l'utilisation du site
                        public de HS Quality SAS et de la plateforme SaaS associée. L'accès au site et à la plateforme
                        implique l'acceptation pleine et entière des présentes CGU.
                    </p>

                    <h2>2. Définitions</h2>
                    <ul>
                        <li><strong>Plateforme</strong> : l'application SaaS de pilotage qualité et QVCT de HS Quality.</li>
                        <li><strong>Structure</strong> : organisation cliente abonnée à la plateforme (SAAD, SSIAD, etc.).</li>
                        <li><strong>Utilisateur</strong> : personne physique disposant d'un compte sur la plateforme.</li>
                        <li><strong>Pilote</strong> : période d'essai gratuite de 3 mois, sans engagement.</li>
                    </ul>

                    <h2>3. Accès au service</h2>
                    <p>
                        L'accès à la plateforme est réservé aux utilisateurs disposant d'un compte créé par leur structure.
                        L'accès se fait par identifiant et mot de passe, complété d'une authentification multi-facteurs
                        obligatoire pour les rôles à risque (dirigeant, coordinateur, référent qualité, RH).
                    </p>

                    <h2>4. Disponibilité du service</h2>
                    <p>
                        HS Quality s'engage à une disponibilité de la plateforme de 99,9 % hors maintenance planifiée. Les
                        opérations de maintenance sont annoncées par email au moins 48 h à l'avance lorsque possible.
                    </p>

                    <h2>5. Obligations de l'utilisateur</h2>
                    <ul>
                        <li>Maintenir la confidentialité de ses identifiants de connexion</li>
                        <li>Activer l'authentification multi-facteurs si son rôle l'exige</li>
                        <li>Ne pas tenter de contourner les mesures de sécurité ou de cloisonnement multi-tenant</li>
                        <li>Respecter les droits des bénéficiaires dont les données sont traitées sur la plateforme</li>
                        <li>Signaler sans délai tout incident de sécurité au support de HS Quality</li>
                    </ul>

                    <h2>6. Pilote gratuit 3 mois</h2>
                    <p>
                        Le pilote gratuit, d'une durée de 3 mois calendaires à compter de la date de provisioning, est sans
                        engagement et sans saisie de carte bancaire. À l'issue du pilote, l'utilisation de la plateforme
                        nécessite la souscription à une offre payante (Essentiel, Pro ou Premium). En cas de non-souscription,
                        les données sont restituées au format CSV ou supprimées sur simple demande, dans les 30 jours.
                    </p>

                    <h2>7. Tarification</h2>
                    <p>
                        La tarification est établie par utilisateur actif et par mois (8 €, 15 € ou 25 € HT selon l'offre,
                        conformément au cahier des charges CDC-QUALITE-DOM-2024-v2.0 §7.1). Des remises volume sont disponibles
                        à partir de 100 intervenants, sur demande.
                    </p>

                    <h2>8. Propriété intellectuelle</h2>
                    <p>
                        La plateforme, son code source, son design et sa documentation sont la propriété exclusive de HS
                        Quality SAS. Les données saisies par les structures clientes restent leur propriété pleine et entière.
                    </p>

                    <h2>9. Données personnelles</h2>
                    <p>
                        Le traitement des données personnelles fait l'objet d'une <a href="/confidentialite">politique de
                        confidentialité dédiée</a>. HS Quality intervient en qualité de sous-traitant au sens de l'article 28
                        du RGPD pour les données traitées sur la plateforme au nom des structures clientes.
                    </p>

                    <h2>10. Responsabilité</h2>
                    <p>
                        HS Quality met en œuvre les mesures de sécurité conformes à l'état de l'art (HDS, chiffrement,
                        audit logs). Sa responsabilité ne saurait être engagée pour un usage non conforme de la plateforme
                        par les structures clientes ou leurs utilisateurs.
                    </p>

                    <h2>11. Résiliation</h2>
                    <p>
                        Le contrat peut être résilié par chacune des parties moyennant un préavis de 30 jours. À l'issue du
                        contrat, les données du client sont restituées au format CSV puis supprimées définitivement dans les
                        30 jours (conformément à l'article 17 du RGPD).
                    </p>

                    <h2>12. Loi applicable et juridiction</h2>
                    <p>
                        Les présentes CGU sont régies par le droit français. Tout litige relatif à leur exécution relève de
                        la compétence exclusive des tribunaux français.
                    </p>
                </div>
            </article>
            <LegalProseStyle />
        </MarketingPage>
    );
}
