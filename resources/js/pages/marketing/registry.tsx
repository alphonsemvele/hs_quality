import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head } from '@inertiajs/react';
import { LegalProseStyle } from './legal-mentions';

const updatedAt = '19 juin 2026';

interface ProcessingActivity {
    name: string;
    purpose: string;
    legalBasis: string;
    categories: string[];
    recipients: string[];
    retention: string;
    role: 'controller' | 'processor';
}

const ACTIVITIES: ProcessingActivity[] = [
    {
        name: 'Gestion des comptes utilisateurs',
        purpose: 'Authentifier les utilisateurs, gérer leurs droits, sécuriser l\'accès à la plateforme.',
        legalBasis: 'Exécution du contrat de service souscrit par la structure cliente.',
        categories: [
            'Identifiants (nom, prénom, email professionnel)',
            'Rôle, fonction, employee_number',
            'Hash du mot de passe, secret TOTP chiffré',
            'Métadonnées de connexion (IP, user-agent, horodatage)',
        ],
        recipients: ['Personnels habilités de la structure cliente', 'HS Quality (hébergement & support technique)'],
        retention: '12 mois après la fin de la relation contractuelle, puis anonymisation.',
        role: 'processor',
    },
    {
        name: 'Traçabilité des interventions à domicile',
        purpose: 'Documenter chaque intervention (date, intervenant, bénéficiaire, tâches, photo, signature).',
        legalBasis: 'Obligation légale (code de l\'action sociale et des familles, art. L. 311-3).',
        categories: [
            'Identité de l\'intervenant',
            'Identité du bénéficiaire',
            'Horodatage GPS du check-in/check-out',
            'Photo de l\'intervention (EXIF retiré)',
            'Signature électronique',
        ],
        recipients: ['Personnels habilités de la structure cliente', 'Autorités sanitaires en cas de contrôle (ARS, HAS)'],
        retention: '5 ans glissants conformément aux exigences HAS.',
        role: 'processor',
    },
    {
        name: 'Dossier bénéficiaire (données de santé — RGPD Art. 9)',
        purpose: 'Adapter l\'accompagnement à domicile aux besoins médicaux et autonomie de la personne.',
        legalBasis: 'Consentement explicite et intérêt vital de la personne (RGPD art. 9.2.h).',
        categories: [
            'État civil, adresse, contacts',
            'GIR (groupe iso-ressources)',
            'Notes médicales chiffrées',
            'Allergies, traitements en cours',
            'Médecin référent, personne de confiance',
        ],
        recipients: [
            'Intervenants assignés au bénéficiaire',
            'Coordinateurs et référents qualité de la structure',
            'Membre de la famille via portail (sur invitation)',
        ],
        retention: 'Durée du contrat de prise en charge + 3 ans, puis anonymisation. Sortie anticipée sur demande RGPD.',
        role: 'processor',
    },
    {
        name: 'Déclaration d\'événements indésirables',
        purpose: 'Recueillir, analyser et signaler aux autorités sanitaires les incidents graves.',
        legalBasis: 'Obligation légale (code de la santé publique, art. L. 1413-14).',
        categories: [
            'Identifiant interne de l\'incident',
            'Date, heure, gravité, catégorie',
            'Personnes impliquées (pseudonymisé pour les bénéficiaires)',
            'Actions correctives engagées',
        ],
        recipients: ['Dirigeants & coordinateurs de la structure', 'ARS de la région compétente pour les incidents graves'],
        retention: '5 ans glissants à compter de la clôture de l\'incident.',
        role: 'processor',
    },
    {
        name: 'Baromètre QVCT (anonymisé)',
        purpose: 'Mesurer le climat social et détecter les risques psychosociaux à l\'échelle équipe.',
        legalBasis: 'Consentement du salarié au sens de l\'article 9.2.a du RGPD.',
        categories: [
            'Réponses anonymisées au questionnaire',
            'Métadonnées d\'équipe (pas de re-identification)',
            'Score agrégé par dimension RPS',
        ],
        recipients: ['Responsable RH et dirigeant de la structure (agrégats uniquement)'],
        retention: '24 mois après la fin de la campagne.',
        role: 'processor',
    },
    {
        name: 'Journal d\'audit applicatif',
        purpose: 'Garantir l\'auditabilité des accès et des modifications portant sur les données de santé.',
        legalBasis: 'Obligation légale (Référentiel HDS, sécurité des SI de santé).',
        categories: [
            'Identifiant utilisateur',
            'Action effectuée, ressource ciblée',
            'Adresse IP, horodatage UTC',
        ],
        recipients: ['Équipe sécurité HS Quality', 'Auditeurs externes mandatés', 'Autorités sur réquisition légale'],
        retention: '13 mois (article L. 34-1 du Code des postes et communications électroniques).',
        role: 'processor',
    },
    {
        name: 'Facturation & abonnement',
        purpose: 'Émettre et conserver les factures, gérer les paiements via prestataire Stripe.',
        legalBasis: 'Obligation légale (Code de commerce, art. L. 123-22).',
        categories: [
            'Raison sociale, SIRET, adresse de facturation',
            'Email de facturation',
            'Identifiant Stripe customer / subscription',
            'Historique des factures',
        ],
        recipients: ['Stripe Payments Europe Ltd. (sous-traitant niveau 2)', 'Comptable HS Quality', 'Administration fiscale'],
        retention: '10 ans (Code de commerce).',
        role: 'controller',
    },
    {
        name: 'Demande de contact (site public)',
        purpose: 'Répondre aux sollicitations commerciales du formulaire /contact.',
        legalBasis: 'Consentement de la personne effectuant la demande.',
        categories: ['Nom, prénom, email, téléphone, structure, message'],
        recipients: ['Équipe commerciale HS Quality', 'CRM (HubSpot)'],
        retention: '24 mois après le dernier échange.',
        role: 'controller',
    },
];

export default function RegistryPage() {
    return (
        <MarketingPage>
            <Head title="Registre des traitements — HS Quality" />
            <LegalProseStyle />

            <article className="mx-auto max-w-4xl px-5 py-16 sm:px-8 sm:py-20">
                <header>
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Conformité RGPD
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                        Registre des activités de traitement
                    </h1>
                    <p className="mt-2 text-xs text-ink-400">Article 30 du RGPD &middot; Dernière mise à jour : {updatedAt}</p>
                </header>

                <div className="prose-legal mt-10">
                    <p>
                        Le présent registre recense l'ensemble des traitements de données personnelles
                        effectués par HS Quality, qu'elle agisse en qualité de <strong>responsable de
                        traitement</strong> (site public, facturation) ou de <strong>sous-traitant au sens de
                        l'article 28 du RGPD</strong> (plateforme SaaS exploitée pour le compte des structures
                        clientes).
                    </p>
                    <p>
                        Une copie du DPA (Data Processing Agreement) est disponible sur simple demande auprès
                        de notre DPO à l'adresse <strong>dpo@hsquality.fr</strong>.
                    </p>
                </div>

                <section className="mt-10 space-y-4">
                    {ACTIVITIES.map((a) => (
                        <ActivityCard key={a.name} activity={a} />
                    ))}
                </section>

                <aside className="mt-12 rounded-2xl border border-brand-200 bg-brand-50/40 p-6 text-sm text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                    <h2 className="text-base font-semibold">Exercer vos droits</h2>
                    <p className="mt-2 leading-relaxed">
                        Vous disposez d'un droit d'accès, de rectification, d'effacement, de portabilité et
                        d'opposition sur vos données personnelles. Pour exercer ces droits :
                    </p>
                    <ul className="mt-2 list-inside list-disc">
                        <li>Depuis votre compte : <a className="underline" href="/dashboard/profile/gdpr">Mes données personnelles</a></li>
                        <li>Par email : <strong>dpo@hsquality.fr</strong></li>
                        <li>Réclamation : <a className="underline" href="https://www.cnil.fr" target="_blank" rel="noreferrer">cnil.fr</a></li>
                    </ul>
                </aside>
            </article>
        </MarketingPage>
    );
}

function ActivityCard({ activity }: { activity: ProcessingActivity }) {
    const roleLabel = activity.role === 'controller' ? 'Responsable de traitement' : 'Sous-traitant (Art. 28)';
    const roleClass =
        activity.role === 'controller'
            ? 'bg-warning-100 text-warning-800'
            : 'bg-brand-100 text-brand-800';

    return (
        <article className="rounded-2xl border border-ink-100 bg-white p-5 shadow-sm dark:border-ink-700/60 dark:bg-ink-800">
            <header className="flex flex-wrap items-start justify-between gap-3">
                <h3 className="text-base font-semibold text-ink-900 dark:text-white">{activity.name}</h3>
                <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${roleClass}`}>
                    {roleLabel}
                </span>
            </header>

            <dl className="mt-4 space-y-3 text-sm">
                <Row label="Finalité" value={activity.purpose} />
                <Row label="Base légale" value={activity.legalBasis} />
                <Row label="Catégories de données" value={<List items={activity.categories} />} />
                <Row label="Destinataires" value={<List items={activity.recipients} />} />
                <Row label="Durée de conservation" value={activity.retention} />
            </dl>
        </article>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-3">
            <dt className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                {label}
            </dt>
            <dd className="text-sm text-ink-700 dark:text-ink-200">{value}</dd>
        </div>
    );
}

function List({ items }: { items: string[] }) {
    return (
        <ul className="list-inside list-disc space-y-0.5">
            {items.map((i) => (
                <li key={i}>{i}</li>
            ))}
        </ul>
    );
}
