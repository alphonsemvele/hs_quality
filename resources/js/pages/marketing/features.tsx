import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

// Aligné avec CDC-QUALITE-DOM-2024-v2.0 §4 — 9 modules fonctionnels.
const MODULES: {
    num: string;
    title: string;
    summary: string;
    bullets: string[];
    tier: 'Essentiel' | 'Pro' | 'Premium';
}[] = [
    {
        num: 'M1',
        title: 'Traçabilité des interventions',
        summary:
            "Documenter chaque intervention de manière structurée, sécurisée et horodatée — du domicile au tableau de bord.",
        bullets: [
            'Dossier bénéficiaire : état civil, situation médicale et sociale, habitudes de vie',
            'Plan d\'accompagnement personnalisé avec objectifs et tâches planifiées',
            'Pointage mobile géolocalisé arrivée / départ',
            'Compte-rendu mobile avec dictée vocale (voice-to-text)',
            'Photos documentaires + signature électronique du bénéficiaire',
            'Synchronisation différée pour les zones blanches',
            'Alertes en cas de non-intervention prévue',
        ],
        tier: 'Essentiel',
    },
    {
        num: 'M2',
        title: 'Gestion des incidents et événements indésirables',
        summary:
            "Déclarer, analyser et traiter chaque incident en conformité avec les exigences HAS — en moins de 2 minutes sur le terrain.",
        bullets: [
            'Déclaration mobile guidée (chute, agression, erreur médicamenteuse, maltraitance, danger…)',
            'Algorithme de gravité automatique : mineur / significatif / grave / critique',
            'Notification automatique au responsable de secteur',
            'Analyse des causes (5 pourquoi / Ishikawa)',
            'Plan d\'actions correctives avec responsable, échéance et suivi',
            'Tableau de bord d\'évolution et comparaison inter-périodes',
            'Notification automatique aux autorités (ARS, Conseil Départemental) pour les EI graves',
        ],
        tier: 'Pro',
    },
    {
        num: 'M3',
        title: 'QVCT — Qualité de Vie et Conditions de Travail',
        summary:
            "Détecter les signaux faibles, mesurer le bien-être et prévenir les risques psychosociaux dans un secteur à fort turnover.",
        bullets: [
            'Baromètre interne anonyme — fréquence paramétrable',
            'Détection des signaux faibles (baisse de moral, surcharge, conflits)',
            'Suivi individualisé des risques psychosociaux + alerte référent RH',
            'Journal de bord émotionnel optionnel',
            'Demande d\'échange RH directe par l\'intervenant',
            'Cartographie des RPS par équipe et par secteur',
            'Suivi des indicateurs : absentéisme, turnover, accidents du travail',
            'Plan d\'actions QVCT avec mesure d\'impact',
        ],
        tier: 'Pro',
    },
    {
        num: 'M4',
        title: 'Communication interne',
        summary:
            "Briser l'isolement professionnel des intervenants — l'enjeu n°1 du secteur — par un espace d'échange structuré.",
        bullets: [
            'Messagerie instantanée sécurisée intervenants / coordinateurs',
            'Groupes de discussion par équipe, secteur ou thématique',
            'Fil d\'actualité de la structure',
            'Bibliothèque de protocoles et fiches pratiques',
            'Forum questions / réponses animé par les référents qualité',
            'Visioconférence intégrée (offre Premium)',
            'Notifications push mobile pour les informations urgentes',
        ],
        tier: 'Pro',
    },
    {
        num: 'M5',
        title: 'Gestion des compétences et de la formation',
        summary:
            "Cartographier les habilitations, anticiper les expirations et orchestrer la montée en compétences.",
        bullets: [
            'Cartographie des compétences : habilitations, certifications, formations',
            'Alertes automatiques sur les dates d\'expiration',
            'Plan de formation individualisé basé sur les lacunes',
            'Bibliothèque de modules e-learning courts (offre Premium)',
            'Évaluation des compétences en situation par le responsable',
            'Parcours d\'onboarding pour les nouveaux intervenants',
            'Tableau de bord RH : taux de formation, besoins prévisionnels',
        ],
        tier: 'Pro',
    },
    {
        num: 'M6',
        title: 'Audits et conformité réglementaire',
        summary:
            "Préparer la prochaine évaluation HAS sereinement — grilles standard ou personnalisées, scoring automatique, PAC généré depuis les écarts.",
        bullets: [
            'Bibliothèque de grilles : HAS, ISO 9001, AFNOR NF X50-056, Caphandeo',
            'Grilles personnalisables selon le type de structure',
            'Audit réalisable sur tablette pendant les visites',
            'Score automatique avec identification des écarts',
            'Plan d\'amélioration continue (PAC) généré automatiquement',
            'Suivi de l\'avancement avec indicateurs de progression',
            'Préparation guidée à l\'évaluation externe HAS',
            'Tableau de bord de conformité longitudinal',
        ],
        tier: 'Pro',
    },
    {
        num: 'M7',
        title: 'Indicateurs et tableaux de bord',
        summary:
            "Une vision synthétique et en temps réel — du dashboard intervenant aux KPI exécutifs pour les dirigeants.",
        bullets: [
            'Dashboard exécutif : KPIs synthétiques pour les dirigeants',
            'Dashboard opérationnel : suivi quotidien pour les coordinateurs',
            'Indicateurs qualité : incidents, conformité, satisfaction',
            'Indicateurs QVCT : absentéisme, turnover, baromètre',
            'Graphiques d\'évolution temporelle et comparaisons inter-équipes',
            'Alertes sur les indicateurs hors-seuil',
            'Export PDF / Excel à la demande',
            'Rapport annuel qualité auto-généré pour les autorités',
        ],
        tier: 'Pro',
    },
    {
        num: 'M8',
        title: 'Portail bénéficiaires et familles',
        summary:
            "Impliquer les bénéficiaires et leurs familles dans la démarche qualité — droit à l'information, consentement, transparence.",
        bullets: [
            'Accès sécurisé au plan d\'accompagnement personnalisé',
            'Consultation de l\'historique des interventions réalisées',
            'Questionnaire de satisfaction périodique',
            'Signalement direct d\'une insatisfaction ou d\'un incident',
            'Messagerie sécurisée avec le coordinateur référent',
            'Notifications des changements d\'intervenant ou de planning',
        ],
        tier: 'Premium',
    },
    {
        num: 'M9',
        title: 'Intelligence artificielle et analyse prédictive',
        summary:
            "Exploiter les données collectées pour anticiper les risques avant qu'ils ne se matérialisent.",
        bullets: [
            'Détection précoce des bénéficiaires à risque de perte d\'autonomie',
            'Prédiction du risque de burnout des intervenants (signaux QVCT + charge)',
            'Identification automatique des intervenants nécessitant un soutien',
            'Benchmark anonymisé entre structures comparables',
            'Suggestions automatiques d\'actions préventives',
            'Analyse sémantique des comptes-rendus pour détecter les signaux d\'alerte',
        ],
        tier: 'Premium',
    },
];

const TIER_TONE: Record<'Essentiel' | 'Pro' | 'Premium', string> = {
    Essentiel: 'bg-ink-100 text-ink-700 ring-ink-200',
    Pro: 'bg-sage-50 text-sage-700 ring-sage-200',
    Premium: 'bg-brand-50 text-brand-700 ring-brand-200',
};

export default function FeaturesPage() {
    return (
        <MarketingPage>
            <Head title="Fonctionnalités — HS Quality" />

            {/* Hero */}
            <section className="border-b border-ink-100 bg-gradient-to-b from-ink-50/40 to-white px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Fonctionnalités
                    </span>
                    <h1 className="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl">
                        Les <span className="gradient-text">9 modules</span> qui structurent votre démarche qualité.
                    </h1>
                    <p className="mt-5 max-w-2xl text-base font-light leading-relaxed text-ink-600">
                        Chaque module répond à une exigence concrète du cahier des charges HAS et du référentiel d'évaluation des
                        établissements médico-sociaux. Activés selon votre offre.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Link
                            href="/contact"
                            className="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                        >
                            Démarrer le pilote 3 mois
                        </Link>
                        <a
                            href="/#pricing"
                            className="rounded-full border border-ink-200 bg-white px-6 py-3 text-sm font-semibold text-ink-700 transition-colors hover:border-ink-300"
                        >
                            Voir les tarifs
                        </a>
                    </div>
                </div>
            </section>

            {/* Modules */}
            <section className="bg-white px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        {MODULES.map((m) => (
                            <ModuleCard key={m.num} module={m} />
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="bg-ink-50/40 px-5 py-16 sm:px-8 sm:py-20">
                <div className="mx-auto max-w-3xl text-center">
                    <h2 className="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
                        Une démo vaut mille captures d'écran.
                    </h2>
                    <p className="mx-auto mt-3 max-w-xl text-sm text-ink-600">
                        Échangeons sur vos enjeux qualité : nous configurons un environnement pilote en 48 h pour vos coordinateurs.
                    </p>
                    <Link
                        href="/contact"
                        className="mt-7 inline-block rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg"
                    >
                        Demander une démo
                    </Link>
                </div>
            </section>
        </MarketingPage>
    );
}

function ModuleCard({ module: m }: { module: (typeof MODULES)[number] }) {
    return (
        <div className="card-hover flex h-full flex-col rounded-2xl border border-ink-100 bg-white p-7">
            <div className="flex items-center justify-between gap-3">
                <span className="mono inline-flex items-center rounded-md bg-ink-50 px-2.5 py-1 text-xs font-semibold text-ink-700">
                    {m.num}
                </span>
                <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset ${TIER_TONE[m.tier]}`}>
                    {m.tier}
                </span>
            </div>
            <h3 className="mt-4 text-lg font-semibold text-ink-900">{m.title}</h3>
            <p className="mt-2 text-sm leading-relaxed text-ink-600">{m.summary}</p>
            <ul className="mt-5 flex-1 space-y-2">
                {m.bullets.map((b) => (
                    <li key={b} className="flex items-start gap-2.5">
                        <CheckIcon />
                        <span className="text-sm leading-relaxed text-ink-700">{b}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function CheckIcon(): ReactNode {
    return (
        <span className="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-sage-50 text-sage-600">
            <svg className="size-2.5" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </span>
    );
}
