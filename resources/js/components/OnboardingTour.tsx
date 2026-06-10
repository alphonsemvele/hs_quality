import { Modal } from '@/components/ui';
import { useEffect, useState } from 'react';

interface TourStep {
    title: string;
    body: string;
    /** Hint shown as a chip ("1/4"). Filled in automatically. */
}

const STORAGE_KEY = 'hsq.tour.dashboard.v1';

const STEPS: TourStep[] = [
    {
        title: 'Bienvenue sur QualitéDomicile',
        body:
            'Cette visite rapide vous présente les zones clés de votre tableau de bord. Vous pouvez la rouvrir à tout moment depuis le menu profil.',
    },
    {
        title: 'Vos indicateurs en un coup d\'œil',
        body:
            'Les cartes du haut résument le pilotage qualité : interventions, incidents, QVCT et conformité. Cliquez sur une carte pour entrer dans le détail.',
    },
    {
        title: 'Recherche universelle (⌘K)',
        body:
            'Appuyez sur ⌘K (ou /) pour ouvrir la palette : bénéficiaires, intervenants, plans d\'amélioration, audits — tout est accessible en quelques frappes.',
    },
    {
        title: 'Raccourcis clavier (?)',
        body:
            'La touche ? affiche la liste complète des raccourcis. Vous y trouverez aussi les actions disponibles pour gagner du temps au quotidien.',
    },
];

/**
 * First-time-only product tour for the dashboard.
 *
 * Shows a 4-step modal on the user's first dashboard visit. The "seen"
 * flag lives in localStorage; clearing the key (or visiting from a fresh
 * browser/profile) will replay the tour. The tour skips itself if the
 * user navigates away or presses Esc.
 *
 * Designed to be mounted once in the dashboard layout next to the other
 * global affordances (FlashToasts, ShortcutsCheatsheet, ...).
 */
export function OnboardingTour() {
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState(0);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        try {
            const seen = window.localStorage.getItem(STORAGE_KEY);
            if (!seen) {
                const t = window.setTimeout(() => setOpen(true), 600);
                return () => window.clearTimeout(t);
            }
        } catch {
            // localStorage blocked → fail open (don't show)
        }
    }, []);

    const finish = () => {
        try {
            window.localStorage.setItem(STORAGE_KEY, '1');
        } catch {
            // ignore
        }
        setOpen(false);
        setStep(0);
    };

    const current = STEPS[step];
    const isLast = step === STEPS.length - 1;

    return (
        <Modal
            open={open}
            onClose={finish}
            title={current.title}
            description={`Étape ${step + 1} sur ${STEPS.length}`}
            iconTone="brand"
            icon={
                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                    <path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1" strokeLinecap="round" />
                </svg>
            }
            size="md"
        >
            <div className="space-y-4">
                <p className="text-sm leading-relaxed text-ink-700 dark:text-ink-200">{current.body}</p>

                <div className="flex items-center justify-center gap-1.5">
                    {STEPS.map((_, i) => (
                        <span
                            key={i}
                            className={
                                i === step
                                    ? 'h-1.5 w-6 rounded-full bg-brand-500 dark:bg-brand-400'
                                    : 'h-1.5 w-1.5 rounded-full bg-ink-200 dark:bg-ink-600'
                            }
                            aria-hidden
                        />
                    ))}
                </div>

                <div className="flex items-center justify-between pt-2">
                    <button
                        type="button"
                        onClick={finish}
                        className="text-xs font-medium text-ink-500 underline-offset-2 hover:underline dark:text-ink-400"
                    >
                        Passer la visite
                    </button>
                    <div className="flex items-center gap-2">
                        {step > 0 && (
                            <button
                                type="button"
                                onClick={() => setStep((s) => Math.max(0, s - 1))}
                                className="rounded-lg border border-ink-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-700 hover:bg-ink-50 dark:border-ink-600 dark:bg-ink-700 dark:text-ink-200 dark:hover:bg-ink-600"
                            >
                                Précédent
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => (isLast ? finish() : setStep((s) => s + 1))}
                            className="rounded-lg bg-brand-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-400"
                        >
                            {isLast ? 'Terminer' : 'Suivant →'}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
