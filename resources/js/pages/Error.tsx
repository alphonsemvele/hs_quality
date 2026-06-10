import { Head, Link } from '@inertiajs/react';

interface ErrorInfo {
    title: string;
    description: string;
}

const ERRORS: Record<number, ErrorInfo> = {
    403: {
        title: 'Accès refusé',
        description:
            "Vous n'avez pas les droits nécessaires pour accéder à cette page. Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur de votre structure.",
    },
    404: {
        title: 'Page introuvable',
        description: "La page que vous recherchez n'existe pas, a été déplacée ou n'est plus disponible.",
    },
    419: {
        title: 'Session expirée',
        description: 'Votre session a expiré pour des raisons de sécurité. Rechargez la page puis réessayez.',
    },
    429: {
        title: 'Trop de requêtes',
        description: 'Vous avez effectué trop de requêtes en peu de temps. Patientez un court instant avant de réessayer.',
    },
    500: {
        title: 'Erreur interne',
        description: "Une erreur inattendue s'est produite de notre côté. Nos équipes ont été notifiées automatiquement.",
    },
    503: {
        title: 'Service indisponible',
        description: 'Le service est momentanément en maintenance. Merci de réessayer dans quelques minutes.',
    },
};

const FALLBACK: ErrorInfo = {
    title: 'Une erreur est survenue',
    description: "Quelque chose s'est mal passé. Merci de réessayer dans un instant.",
};

export default function Error({ status }: { status: number }) {
    const info = ERRORS[status] ?? FALLBACK;

    return (
        <>
            <Head title={`${status} — ${info.title}`} />
            <main className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-ink-50 px-6 py-16 dark:bg-ink-900">
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-brand-100/70 to-transparent dark:from-brand-900/30"
                />

                <Link
                    href="/"
                    className="relative mb-10 inline-flex items-center gap-2.5"
                    aria-label="HS Quality — accueil"
                >
                    <span className="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-base font-bold text-white shadow-sm">
                        Q
                    </span>
                    <span className="text-lg font-semibold tracking-tight text-ink-900 dark:text-white">HS Quality</span>
                </Link>

                <div className="relative w-full max-w-md text-center">
                    <p className="font-mono text-6xl font-bold tabular-nums text-brand-600 dark:text-brand-400 sm:text-7xl">
                        {status}
                    </p>
                    <h1 className="mt-4 text-2xl font-semibold tracking-tight text-ink-900 dark:text-white">
                        {info.title}
                    </h1>
                    <p className="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-ink-600 dark:text-ink-300">
                        {info.description}
                    </p>

                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Link
                            href="/dashboard"
                            className="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-600 px-5 text-sm font-medium text-white transition-colors hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 sm:w-auto dark:bg-brand-500 dark:hover:bg-brand-400"
                        >
                            Retour au tableau de bord
                        </Link>
                        <button
                            type="button"
                            onClick={() => window.history.back()}
                            className="inline-flex h-11 w-full items-center justify-center rounded-xl border border-ink-200 bg-white px-5 text-sm font-medium text-ink-700 transition-colors hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 sm:w-auto dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:bg-ink-700"
                        >
                            Page précédente
                        </button>
                    </div>
                </div>

                <p className="relative mt-12 text-xs text-ink-400 dark:text-ink-500">
                    HS Quality · Qualité &amp; QVCT — Chiffré TLS 1.3 · RGPD · HDS
                </p>
            </main>
        </>
    );
}
