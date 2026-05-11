import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';

// Aligné avec CDC §5 (Conformité et Sécurité).

const REGULATIONS: { id: string; title: string; scope: string; description: string }[] = [
    {
        id: 'rgpd',
        title: 'RGPD (UE 2016/679)',
        scope: 'Europe',
        description:
            "Consentement explicite, droit à l'effacement (Art. 17), portabilité, DPO nommé. Audit log par tenant pour chaque accès aux données de santé. Purge automatisée des données expirées.",
    },
    {
        id: 'hds',
        title: 'HDS — Hébergement de Données de Santé',
        scope: 'France',
        description:
            "Hébergement obligatoire chez un prestataire HDS-certifié (AWS Paris ou OVHcloud France). Chiffrement au repos AES-256, en transit TLS 1.3. Cloisonnement multi-tenant strict.",
    },
    {
        id: 'has',
        title: 'Référentiel HAS — Évaluation externe',
        scope: 'France',
        description:
            "Module Audits intégrant les exigences du référentiel d'évaluation des établissements et services médico-sociaux. Préparation guidée à l'évaluation externe quinquennale.",
    },
    {
        id: 'afnor',
        title: 'AFNOR NF X50-056',
        scope: 'France',
        description:
            "Norme qualité dédiée aux services à la personne. Grilles d'audit standard et personnalisables intégrées au module M6.",
    },
    {
        id: 'iso',
        title: 'ISO 9001',
        scope: 'International',
        description:
            "Système de management de la qualité. Indicateurs, plans d'amélioration continue et revue de direction couverts par les modules M6 et M7.",
    },
    {
        id: 'caphandeo',
        title: 'Caphandeo',
        scope: 'France',
        description:
            "Grille d'évaluation Caphandeo couplée au référentiel de certification des établissements de santé pour la qualité des soins, comme attendu pour les référents qualité.",
    },
    {
        id: 'loi-2002',
        title: 'Loi du 2 janvier 2002',
        scope: 'France',
        description:
            "Droits des usagers dans les structures médico-sociales : information, consentement, accès au dossier. Couvert par le portail bénéficiaires (M8).",
    },
    {
        id: 'rps',
        title: 'Code du travail — RPS',
        scope: 'France',
        description:
            "Obligations de prévention des risques psychosociaux. Couvert par le baromètre QVCT (M3) avec cartographie des RPS, suivi individualisé et plans d'actions.",
    },
];

const SECURITY_PILLARS: { title: string; bullets: string[] }[] = [
    {
        title: 'Authentification & accès',
        bullets: [
            'MFA TOTP obligatoire pour les rôles privilégiés (dirigeant, coordinateur, référent qualité, RH)',
            'SMS et email refusés comme MFA — recommandation NIST/ANSSI/CNIL pour données de santé',
            'Sessions cookies CSRF côté web · tokens Sanctum côté mobile (révocables)',
            'Habilitations granulaires par rôle (Spatie laravel-permission, ~70 permissions)',
        ],
    },
    {
        title: 'Chiffrement & stockage',
        bullets: [
            'AES-256 au repos (champs sensibles + S3 SSE-KMS)',
            'TLS 1.3 en transit',
            'Hébergement HDS-certifié en France (AWS Paris / OVHcloud)',
            'Strip EXIF automatique sur les photos d\'intervention (anti-fuite GPS / identité)',
        ],
    },
    {
        title: 'Cloisonnement multi-tenant',
        bullets: [
            'Isolation row-level avec global scope sur chaque table métier',
            'Cross-tenant access → 404 (jamais 403) — l\'enregistrement est invisible',
            'Test cross-tenant obligatoire pour chaque modèle métier (CI bloque sinon)',
            'Permissions Spatie scopées par tenant via team_id',
        ],
    },
    {
        title: 'Audit & traçabilité',
        bullets: [
            'Audit log tenant-aware sur tous les modèles touchant les données de santé',
            'Middleware log_sensitive_read sur l\'accès au dossier bénéficiaire (CDC §5.2)',
            'Journalisation exhaustive des accès via Laravel Auditing',
            'Pas de fuite cross-tenant dans les logs',
        ],
    },
    {
        title: 'Durcissement HTTP',
        bullets: [
            'Headers de sécurité globaux : CSP, HSTS, X-Frame-Options, X-Content-Type-Options',
            'Rate limiting par route : login 5/min, MFA 5/min, déclaration incident 30/min',
            'Throttle API mobile 300 req/min',
            'Idempotency-Key sur écritures mobile (rejeu 24 h sécurisé)',
        ],
    },
    {
        title: 'Continuité & supervision',
        bullets: [
            'PRA : RPO < 1 h · RTO < 4 h',
            'Sauvegardes automatiques toutes les heures',
            'Architecture redondante sans SPOF',
            'Monitoring 24/7 (Sentry + santé applicative)',
            'Audit de sécurité annuel par un prestataire indépendant',
        ],
    },
];

export default function CompliancePage() {
    return (
        <MarketingPage>
            <Head title="Conformité & sécurité — HS Quality" />

            {/* Hero */}
            <section className="border-b border-ink-100 bg-gradient-to-b from-ink-50/40 to-white px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Conformité &amp; sécurité
                    </span>
                    <h1 className="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl">
                        Une plateforme conçue pour la <span className="gradient-text">conformité française</span>.
                    </h1>
                    <p className="mt-5 max-w-2xl text-base font-light leading-relaxed text-ink-600">
                        HS Quality répond aux exigences réglementaires du secteur médico-social : RGPD, HDS, référentiel HAS,
                        AFNOR NF X50-056, ISO 9001. La sécurité n'est pas un module en option — c'est une exigence de design dès
                        le premier commit.
                    </p>
                    <div className="mt-7 flex flex-wrap gap-2">
                        {['RGPD', 'HDS France', 'HAS', 'AFNOR NF X50-056', 'ISO 9001', 'Caphandeo', 'CNIL', 'Code du travail RPS'].map((b) => (
                            <span
                                key={b}
                                className="inline-flex items-center gap-1.5 rounded-md border border-ink-200 bg-white px-2.5 py-1 text-xs font-semibold text-ink-700"
                            >
                                <span className="size-1.5 rounded-full bg-sage-500" />
                                {b}
                            </span>
                        ))}
                    </div>
                </div>
            </section>

            {/* Réglementations */}
            <section className="bg-white px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <h2 className="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Cadre réglementaire</h2>
                    <p className="mt-3 max-w-2xl text-sm text-ink-600">
                        Les huit référentiels couverts. Chaque exigence du cahier des charges fait l'objet d'un contrôle automatisé
                        à chaque déploiement.
                    </p>
                    <div className="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2">
                        {REGULATIONS.map((r) => (
                            <article
                                key={r.id}
                                id={r.id}
                                className="card-hover scroll-mt-24 rounded-2xl border border-ink-100 bg-white p-6"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <h3 className="text-base font-semibold text-ink-900">{r.title}</h3>
                                    <span className="rounded-full bg-ink-50 px-2.5 py-0.5 text-[11px] font-semibold text-ink-600">
                                        {r.scope}
                                    </span>
                                </div>
                                <p className="mt-3 text-sm leading-relaxed text-ink-600">{r.description}</p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            {/* Sécurité */}
            <section className="bg-ink-50/40 px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <h2 className="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Architecture de sécurité</h2>
                    <p className="mt-3 max-w-2xl text-sm text-ink-600">
                        Six piliers pour protéger les données de santé de vos bénéficiaires et les données QVCT de vos
                        intervenants.
                    </p>
                    <div className="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {SECURITY_PILLARS.map((p) => (
                            <div key={p.title} className="rounded-2xl border border-ink-100 bg-white p-6">
                                <h3 className="text-base font-semibold text-ink-900">{p.title}</h3>
                                <ul className="mt-4 space-y-2.5">
                                    {p.bullets.map((b) => (
                                        <li key={b} className="flex items-start gap-2.5">
                                            <span className="mt-1 size-1.5 shrink-0 rounded-full bg-brand-500" />
                                            <span className="text-[13px] leading-relaxed text-ink-700">{b}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Engagements de service */}
            <section className="bg-white px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <h2 className="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Engagements de service</h2>
                    <div className="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                        {[
                            { kpi: '99,9%', label: 'Disponibilité hors maintenance planifiée' },
                            { kpi: '< 1 h', label: 'RPO — perte maximale de données' },
                            { kpi: '< 4 h', label: 'RTO — temps de reprise après incident' },
                            { kpi: '24/7', label: 'Monitoring + astreinte technique' },
                        ].map((x) => (
                            <div key={x.label} className="rounded-2xl border border-ink-100 bg-white p-6 text-center">
                                <div className="mono text-3xl font-bold tracking-tight text-ink-900">{x.kpi}</div>
                                <p className="mt-2 text-xs leading-relaxed text-ink-500">{x.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="bg-ink-50/40 px-5 py-16 sm:px-8 sm:py-20">
                <div className="mx-auto max-w-3xl text-center">
                    <h2 className="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
                        Une question juridique, RGPD ou sécurité ?
                    </h2>
                    <p className="mx-auto mt-3 max-w-xl text-sm text-ink-600">
                        Notre DPO et notre RSSI répondent sous 24 h ouvrées. Documentation HDS, schéma de cloisonnement et
                        registre des traitements disponibles sur demande.
                    </p>
                    <Link
                        href="/contact"
                        className="mt-7 inline-block rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg"
                    >
                        Contacter notre équipe
                    </Link>
                </div>
            </section>
        </MarketingPage>
    );
}
