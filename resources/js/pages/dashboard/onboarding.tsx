import { Badge, Button, Card, CardBody, PageHeader, Wizard, type WizardStep } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DashboardLayout from './layout';

interface AuthUser {
    name?: string;
    requires_mfa?: boolean;
    has_mfa_enrolled?: boolean;
}

interface PageProps {
    auth?: { user?: AuthUser | null };
    completed?: string[];
    counts?: { users: number; beneficiaries: number };
    mfa_enrolled?: boolean;
    all_critical_done?: boolean;
    [key: string]: unknown;
}

const STEPS: WizardStep[] = [
    { id: 'welcome', label: 'Bienvenue', description: 'Vue d\'ensemble de votre espace HS Quality.' },
    { id: 'team', label: 'Équipe', description: 'Invitez les premiers membres de votre structure.' },
    { id: 'mfa', label: 'Sécurité', description: 'Activez la double authentification.' },
    { id: 'beneficiary', label: 'Bénéficiaire', description: 'Créez votre premier dossier bénéficiaire.' },
    { id: 'ready', label: 'C\'est parti', description: 'Récapitulatif et accès au tableau de bord.' },
];

const PROGRESS_KEY = 'hsq.onboarding.completed-steps';

export default function Onboarding() {
    const { props } = usePage<PageProps>();
    const user = (props.auth as { user?: AuthUser | null } | undefined)?.user ?? null;
    const serverCompleted = (props.completed ?? []) as string[];
    const [step, setStep] = useState(0);
    const [completedSteps, setCompletedSteps] = useState<string[]>([]);

    useEffect(() => {
        let merged: string[] = [...serverCompleted];
        try {
            const stored = window.localStorage.getItem(PROGRESS_KEY);
            if (stored) {
                const parsed = JSON.parse(stored) as string[];
                merged = Array.from(new Set([...merged, ...parsed]));
            }
        } catch {
            // ignore
        }
        setCompletedSteps(merged);
    }, [serverCompleted]);

    const markCompleted = (id: string) => {
        const next = Array.from(new Set([...completedSteps, id]));
        setCompletedSteps(next);
        try {
            window.localStorage.setItem(PROGRESS_KEY, JSON.stringify(next));
        } catch {
            // ignore
        }
    };

    const finish = () => {
        try {
            window.localStorage.setItem(PROGRESS_KEY, JSON.stringify(STEPS.map((s) => s.id)));
        } catch {
            // ignore
        }
        router.visit('/dashboard');
    };

    const goNext = () => setStep((s) => Math.min(STEPS.length - 1, s + 1));

    return (
        <DashboardLayout title="Bienvenue sur HS Quality" subtitle="Premiers pas">
            <PageHeader
                title={user?.name ? `Bienvenue ${user.name.split(' ')[0]} !` : 'Bienvenue sur HS Quality'}
                subtitle="5 étapes pour configurer votre espace — 5 minutes top chrono"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Premiers pas' }]}
            />

            <Wizard
                steps={STEPS}
                currentStep={step}
                onStepChange={setStep}
                onCancel={() => router.visit('/dashboard')}
                onPrevious={() => setStep((s) => Math.max(0, s - 1))}
                onNext={goNext}
                onFinish={finish}
                finishLabel="Aller au tableau de bord →"
            >
                {step === 0 && <WelcomeStep onContinue={() => { markCompleted('welcome'); goNext(); }} />}
                {step === 1 && (
                    <TeamStep
                        completed={completedSteps.includes('team')}
                        onSkip={() => { markCompleted('team'); goNext(); }}
                        onDone={() => markCompleted('team')}
                    />
                )}
                {step === 2 && user && (
                    <MfaStep
                        user={user}
                        completed={completedSteps.includes('mfa') || (user.has_mfa_enrolled ?? false)}
                        onSkip={() => { markCompleted('mfa'); goNext(); }}
                    />
                )}
                {step === 3 && (
                    <BeneficiaryStep
                        completed={completedSteps.includes('beneficiary')}
                        onSkip={() => { markCompleted('beneficiary'); goNext(); }}
                        onDone={() => markCompleted('beneficiary')}
                    />
                )}
                {step === 4 && <ReadyStep completedSteps={completedSteps} totalSteps={STEPS.length - 1} />}
            </Wizard>
        </DashboardLayout>
    );
}

function WelcomeStep({ onContinue }: { onContinue: () => void }) {
    return (
        <div className="space-y-5">
            <div className="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50 to-white p-5 dark:border-brand-700/40 dark:from-brand-900/30 dark:to-ink-800">
                <p className="text-sm leading-relaxed text-ink-800 dark:text-ink-100">
                    HS Quality structure votre démarche qualité et QVCT — du terrain à la direction, en conformité HAS et
                    RGPD. Voici les <strong className="font-semibold">3 piliers</strong> de votre nouvelle plateforme :
                </p>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Pillar
                    icon="🏥"
                    title="Terrain tracé"
                    desc="Chaque intervention, chaque incident est consigné — preuve auditable en visite HAS."
                />
                <Pillar
                    icon="💚"
                    title="QVCT mesurée"
                    desc="Baromètres anonymes, signaux faibles RPS détectés automatiquement, plans d'action ciblés."
                />
                <Pillar
                    icon="📊"
                    title="Conformité prouvée"
                    desc="Registre d'audit RGPD Art. 30, données chiffrées, hébergement HDS France."
                />
            </div>

            <div className="text-center">
                <Button onClick={onContinue} size="lg">
                    Commencer la configuration →
                </Button>
                <p className="mt-2 text-[11px] text-ink-500 dark:text-ink-400">
                    Vous pourrez interrompre à tout moment et reprendre plus tard.
                </p>
            </div>
        </div>
    );
}

function TeamStep({
    completed,
    onSkip,
    onDone,
}: {
    completed: boolean;
    onSkip: () => void;
    onDone: () => void;
}) {
    return (
        <div className="space-y-5">
            <p className="text-sm text-ink-700 dark:text-ink-200">
                Invitez les premiers membres de votre structure pour qu'ils accèdent à leur tableau de bord :
            </p>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <RoleHint label="Coordinateur·rice" desc="Pilote les tournées, assigne les intervenants, suit les incidents." />
                <RoleHint label="Référent·e qualité" desc="Mène les audits HAS/ISO et pilote les plans d'amélioration." />
                <RoleHint label="Responsable RH" desc="Lance les campagnes QVCT, gère formations et habilitations." />
                <RoleHint label="Intervenant·e" desc="Saisit les visites depuis le mobile (sync offline)." />
            </div>

            <StepCtaBlock
                completed={completed}
                completedLabel="Membres invités ✓"
                action={
                    <>
                        <Link href="/users" onClick={onDone}>
                            <Button>Inviter mon équipe →</Button>
                        </Link>
                        <Button variant="ghost" onClick={onSkip}>
                            Passer pour l'instant
                        </Button>
                    </>
                }
            />
        </div>
    );
}

function MfaStep({ user, completed, onSkip }: { user: AuthUser; completed: boolean; onSkip: () => void }) {
    if (completed) {
        return (
            <div className="space-y-4">
                <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 dark:border-sage-700/40 dark:bg-sage-900/15">
                    <p className="text-sm font-semibold text-sage-900 dark:text-sage-100">✓ Double authentification active</p>
                    <p className="mt-1 text-xs text-sage-900/80 dark:text-sage-200/80">
                        Votre compte est protégé par TOTP. Vous pouvez passer à l'étape suivante.
                    </p>
                </div>
            </div>
        );
    }
    return (
        <div className="space-y-5">
            <div className={cn(
                'rounded-xl border p-4',
                user.requires_mfa
                    ? 'border-warning-200 bg-warning-50/40 dark:border-warning-700/40 dark:bg-warning-900/15'
                    : 'border-brand-200 bg-brand-50/40 dark:border-brand-700/40 dark:bg-brand-900/15',
            )}>
                <p className={cn(
                    'text-sm font-semibold',
                    user.requires_mfa ? 'text-warning-900 dark:text-warning-100' : 'text-brand-900 dark:text-brand-200',
                )}>
                    {user.requires_mfa ? '⚠ MFA obligatoire pour votre rôle' : 'MFA recommandé'}
                </p>
                <p className={cn(
                    'mt-1 text-xs leading-relaxed',
                    user.requires_mfa ? 'text-warning-900/80 dark:text-warning-200/80' : 'text-brand-900/80 dark:text-brand-200/80',
                )}>
                    Votre rôle donne accès à des données de santé sensibles (RGPD Art. 9). La double authentification
                    protège votre compte si votre mot de passe est compromis (phishing, fuite).
                </p>
            </div>

            <ul className="space-y-2 text-xs text-ink-700 dark:text-ink-200">
                <li className="flex items-start gap-2">
                    <CheckMini /> Compatible Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden
                </li>
                <li className="flex items-start gap-2">
                    <CheckMini /> Codes de secours téléchargeables si vous perdez votre téléphone
                </li>
                <li className="flex items-start gap-2">
                    <CheckMini /> 3 minutes à configurer, une seule fois
                </li>
            </ul>

            <StepCtaBlock
                completed={false}
                action={
                    <>
                        <Link href="/dashboard/profile/mfa-setup">
                            <Button>Configurer le MFA →</Button>
                        </Link>
                        {!user.requires_mfa && (
                            <Button variant="ghost" onClick={onSkip}>
                                Plus tard
                            </Button>
                        )}
                    </>
                }
            />
        </div>
    );
}

function BeneficiaryStep({
    completed,
    onSkip,
    onDone,
}: {
    completed: boolean;
    onSkip: () => void;
    onDone: () => void;
}) {
    return (
        <div className="space-y-5">
            <p className="text-sm text-ink-700 dark:text-ink-200">
                Créez votre premier bénéficiaire pour démarrer la démonstration. Vous pourrez ensuite planifier des
                interventions et créer un plan de soins.
            </p>

            <div className="rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/15">
                <p className="font-semibold text-brand-900 dark:text-brand-200">💡 Astuce</p>
                <p className="mt-1 text-brand-900/80 dark:text-brand-200/80">
                    Le formulaire « Ajout rapide » prend 30 secondes (prénom + nom). Vous pourrez compléter l'adresse,
                    le médecin référent et les contacts d'urgence ensuite depuis la fiche.
                </p>
            </div>

            <StepCtaBlock
                completed={completed}
                completedLabel="Premier bénéficiaire créé ✓"
                action={
                    <>
                        <Link href="/beneficiaries" onClick={onDone}>
                            <Button>Créer un bénéficiaire →</Button>
                        </Link>
                        <Button variant="ghost" onClick={onSkip}>
                            Plus tard
                        </Button>
                    </>
                }
            />
        </div>
    );
}

function ReadyStep({ completedSteps, totalSteps }: { completedSteps: string[]; totalSteps: number }) {
    const done = completedSteps.filter((s) => s !== 'welcome' && s !== 'ready').length;
    const allDone = done >= totalSteps - 1; // exclude welcome

    return (
        <div className="space-y-5">
            <div className="text-center">
                <div className="inline-flex size-16 items-center justify-center rounded-full bg-gradient-to-br from-sage-400 to-sage-600 text-3xl text-white shadow-lg">
                    {allDone ? '🎉' : '🚀'}
                </div>
                <h3 className="mt-4 text-lg font-semibold text-ink-900 dark:text-white">
                    {allDone ? 'Tout est prêt !' : 'Vous pouvez démarrer'}
                </h3>
                <p className="mt-1 text-sm text-ink-500 dark:text-ink-400">
                    {done}/{totalSteps - 1} étape{done > 1 ? 's' : ''} configurée{done > 1 ? 's' : ''}.
                    {!allDone && ' Vous pourrez revenir aux étapes restantes plus tard.'}
                </p>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <QuickAction
                    href="/interventions"
                    icon="📋"
                    title="Interventions"
                    desc="Planifier les premières visites"
                />
                <QuickAction
                    href="/audits"
                    icon="✅"
                    title="Audits"
                    desc="Lancer un audit HAS / ISO"
                />
                <QuickAction
                    href="/qvct/campaigns/create"
                    icon="💚"
                    title="Campagne QVCT"
                    desc="Premier baromètre équipe"
                />
            </div>

            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 text-xs dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="font-semibold text-sage-900 dark:text-sage-100">💡 Besoin d'aide ?</p>
                <p className="mt-1 text-sage-900/80 dark:text-sage-200/80">
                    La barre de recherche (<kbd className="rounded bg-white px-1 font-mono text-[10px] dark:bg-ink-800">⌘K</kbd>) trouve
                    instantanément n'importe quel bénéficiaire, intervention ou audit. La cloche en haut à droite
                    centralise toutes vos notifications.
                </p>
            </div>
        </div>
    );
}

function Pillar({ icon, title, desc }: { icon: string; title: string; desc: string }) {
    return (
        <div className="rounded-xl border border-ink-100 bg-white p-4 text-center dark:border-ink-700/60 dark:bg-ink-800">
            <span aria-hidden className="block text-2xl">{icon}</span>
            <h4 className="mt-2 text-sm font-semibold text-ink-900 dark:text-white">{title}</h4>
            <p className="mt-1 text-xs leading-relaxed text-ink-500 dark:text-ink-400">{desc}</p>
        </div>
    );
}

function RoleHint({ label, desc }: { label: string; desc: string }) {
    return (
        <Card>
            <CardBody className="space-y-1">
                <p className="text-sm font-medium text-ink-900 dark:text-white">{label}</p>
                <p className="text-[11px] text-ink-500 dark:text-ink-400">{desc}</p>
            </CardBody>
        </Card>
    );
}

function StepCtaBlock({
    completed,
    completedLabel,
    action,
}: {
    completed: boolean;
    completedLabel?: string;
    action: React.ReactNode;
}) {
    if (completed && completedLabel) {
        return (
            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="text-sm font-semibold text-sage-900 dark:text-sage-100">{completedLabel}</p>
            </div>
        );
    }
    return <div className="flex flex-wrap items-center gap-3">{action}</div>;
}

function QuickAction({ href, icon, title, desc }: { href: string; icon: string; title: string; desc: string }) {
    return (
        <Link
            href={href}
            className="group flex flex-col items-center gap-2 rounded-xl border border-ink-200 bg-white p-4 text-center transition-all hover:border-brand-400 hover:shadow-md dark:border-ink-700 dark:bg-ink-800 dark:hover:border-brand-500"
        >
            <span aria-hidden className="text-2xl transition-transform group-hover:scale-110">{icon}</span>
            <Badge tone="brand" size="xs">{title}</Badge>
            <p className="text-[11px] text-ink-500 dark:text-ink-400">{desc}</p>
        </Link>
    );
}

function CheckMini() {
    return (
        <span className="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300">
            <svg className="size-2.5" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        </span>
    );
}
