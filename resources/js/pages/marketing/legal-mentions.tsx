import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head } from '@inertiajs/react';

export default function LegalMentionsPage() {
    const updatedAt = '6 mai 2026';

    return (
        <MarketingPage>
            <Head title="Mentions légales — HS Quality" />

            <article className="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-20">
                <header>
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Légal
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Mentions légales</h1>
                    <p className="mt-2 text-xs text-ink-400">Dernière mise à jour : {updatedAt}</p>
                </header>

                <div className="prose-legal mt-10">
                    <h2>Éditeur</h2>
                    <p>
                        Le présent site est édité par <strong>HS Quality SAS</strong>, société par actions simplifiée au capital
                        social à compléter, immatriculée au Registre du Commerce et des Sociétés sous le numéro à compléter,
                        dont le siège social est situé à compléter.
                    </p>
                    <ul>
                        <li>Numéro SIRET : à compléter</li>
                        <li>Numéro de TVA intracommunautaire : à compléter</li>
                        <li>Directeur de la publication : à compléter</li>
                        <li>Email de contact : <a href="mailto:contact@hsquality.fr">contact@hsquality.fr</a></li>
                    </ul>

                    <h2>Hébergeur</h2>
                    <p>
                        Hébergement de l'application sur infrastructure HDS-certifiée (Hébergeur de Données de Santé) — AWS
                        Europe (Paris) ou OVHcloud (France). Les données de santé sont stockées exclusivement en France,
                        conformément aux exigences de l'Agence du Numérique en Santé (ANS).
                    </p>

                    <h2>Délégué à la protection des données (DPO)</h2>
                    <p>
                        Conformément au RGPD (Règlement UE 2016/679), HS Quality a nommé un Délégué à la Protection des Données.
                        Pour toute demande relative au traitement de vos données personnelles, vous pouvez le contacter à
                        l'adresse <a href="mailto:dpo@hsquality.fr">dpo@hsquality.fr</a>.
                    </p>

                    <h2>Propriété intellectuelle</h2>
                    <p>
                        L'ensemble des contenus du site (textes, images, logos, graphismes, code source) est la propriété
                        exclusive de HS Quality SAS, à l'exception des marques et logos appartenant à leurs propriétaires
                        respectifs (HAS, AFNOR, ISO, Caphandeo). Toute reproduction, représentation, modification, publication
                        ou adaptation totale ou partielle, par quelque procédé que ce soit, est interdite sans autorisation
                        écrite préalable.
                    </p>

                    <h2>Responsabilité</h2>
                    <p>
                        HS Quality s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site,
                        sans toutefois pouvoir en garantir l'exhaustivité. Les contenus présents sur ce site sont fournis à
                        titre informatif et ne sauraient se substituer à un conseil juridique, médical ou réglementaire
                        personnalisé.
                    </p>

                    <h2>Droit applicable</h2>
                    <p>
                        Les présentes mentions légales sont régies par le droit français. Tout litige relatif au site relève
                        de la compétence exclusive des tribunaux français.
                    </p>
                </div>
            </article>
            <LegalProseStyle />
        </MarketingPage>
    );
}

export function LegalProseStyle() {
    return (
        <style>{`
            .prose-legal h2 { font-size: 18px; font-weight: 600; color: rgb(15 23 42); margin-top: 28px; margin-bottom: 8px; }
            .prose-legal h3 { font-size: 15px; font-weight: 600; color: rgb(15 23 42); margin-top: 20px; margin-bottom: 6px; }
            .prose-legal p { font-size: 14px; line-height: 1.7; color: rgb(71 85 105); margin-bottom: 12px; }
            .prose-legal ul { padding-left: 22px; margin: 12px 0; list-style: disc; }
            .prose-legal li { font-size: 14px; line-height: 1.7; color: rgb(71 85 105); margin-bottom: 4px; }
            .prose-legal a { color: rgb(21 101 172); }
            .prose-legal a:hover { text-decoration: underline; }
            .prose-legal strong { color: rgb(15 23 42); font-weight: 600; }
        `}</style>
    );
}
