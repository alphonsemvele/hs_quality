import { AnonymityBanner, LikertScale, MoodSelector, type LikertValue, type MoodValue } from '@/components/qvct';
import { Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../layout';

interface Campagne {
    id: string;
    titre: string;
    date_fin: string | null;
    description: string | null;
}

interface Question {
    id: string;
    type: 'mood' | 'likert' | 'open';
    label: string;
    help?: string | null;
    min_label?: string;
    max_label?: string;
    required: boolean;
}

interface Props {
    campagne: Campagne | null;
    questions: Question[];
    threshold: number;
}

export default function QvctQuestionnaire({ campagne, questions = [], threshold = 5 }: Partial<Props>) {
    const [answers, setAnswers] = useState<Record<string, MoodValue | LikertValue | string | null>>(
        () => Object.fromEntries(questions.map((q) => [q.id, null])),
    );

    const setAnswer = (id: string, v: MoodValue | LikertValue | string | null) => {
        setAnswers((prev) => ({ ...prev, [id]: v }));
    };

    const requiredCount = questions.filter((q) => q.required).length;
    const requiredAnswered = useMemo(
        () => questions.filter((q) => q.required && answers[q.id] !== null && answers[q.id] !== '').length,
        [answers, questions],
    );
    const progress = requiredCount > 0 ? (requiredAnswered / requiredCount) * 100 : 0;
    const canSubmit = requiredAnswered === requiredCount && requiredCount > 0;

    if (!campagne) {
        return (
            <DashboardLayout title="Questionnaire QVCT" subtitle="">
                <PageHeader
                    title="Questionnaire QVCT"
                    subtitle="Aucune campagne ouverte actuellement"
                    breadcrumb={[
                        { label: 'Tableau de bord', href: '/dashboard' },
                        { label: 'QVCT', href: '/qvct' },
                        { label: 'Questionnaire' },
                    ]}
                />
                <Card>
                    <EmptyState
                        icon={<HeartIcon />}
                        title="Pas de campagne en cours"
                        description="Aucun baromètre n'est ouvert pour le moment. Revenez plus tard ou contactez votre RH."
                    />
                </Card>
            </DashboardLayout>
        );
    }

    return (
        <DashboardLayout title={campagne.titre} subtitle="Réponses anonymes">
            <PageHeader
                title={campagne.titre}
                subtitle={campagne.date_fin ? `Ouvert jusqu'au ${campagne.date_fin}` : 'Ouvert'}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Questionnaire' },
                ]}
            />

            <div className="mx-auto grid max-w-3xl gap-5">
                <AnonymityBanner variant="callout" threshold={threshold} />

                {campagne.description && (
                    <p className="text-sm leading-relaxed text-ink-600 dark:text-ink-300">{campagne.description}</p>
                )}

                {/* Progress sticky bar */}
                <div className="sticky top-16 z-20 -mx-2 rounded-xl border border-ink-100 bg-white/90 px-4 py-2.5 backdrop-blur sm:mx-0 dark:border-ink-700/60 dark:bg-ink-800/90">
                    <div className="flex items-center justify-between gap-3 text-xs">
                        <span className="font-medium text-ink-700 dark:text-ink-200">
                            {requiredAnswered}/{requiredCount} questions répondues
                        </span>
                        <span className="font-mono text-ink-500 dark:text-ink-400">{progress.toFixed(0)}%</span>
                    </div>
                    <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                        <div
                            className={cn(
                                'h-full rounded-full transition-all duration-500',
                                progress === 100 ? 'bg-sage-500' : 'bg-brand-500',
                            )}
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>

                <QuestionnaireForm
                    campaignId={campagne.id}
                    questions={questions}
                    answers={answers}
                    onChange={setAnswer}
                    canSubmit={canSubmit}
                />
            </div>
        </DashboardLayout>
    );
}

function QuestionnaireForm({
    campaignId,
    questions,
    answers,
    onChange,
    canSubmit,
}: {
    campaignId: string;
    questions: Question[];
    answers: Record<string, MoodValue | LikertValue | string | null>;
    onChange: (id: string, v: MoodValue | LikertValue | string | null) => void;
    canSubmit: boolean;
}) {
    const [processing, setProcessing] = useState(false);

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!canSubmit) return;
        setProcessing(true);
        // Strip unanswered (null/empty) entries so the `answers.*` required
        // rule only sees the questions the user actually filled.
        const cleanedAnswers = Object.fromEntries(Object.entries(answers).filter(([, v]) => v !== null && v !== ''));
        router.post(
            `/qvct/campaigns/${campaignId}/respond`,
            { answers: cleanedAnswers },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <form onSubmit={submit}>
            <ul className="space-y-4">
                {questions.map((q, i) => (
                    <li key={q.id}>
                        <Card>
                            <CardHeader
                                title={`Q${i + 1}. ${q.label}`}
                                subtitle={q.help ?? undefined}
                                action={
                                    q.required && (
                                        <span className="rounded-full bg-danger-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-danger-700 dark:bg-danger-900/30 dark:text-danger-300">
                                            Requis
                                        </span>
                                    )
                                }
                            />
                            <CardBody>
                                <QuestionField q={q} value={answers[q.id]} onChange={(v) => onChange(q.id, v)} disabled={processing} />
                            </CardBody>
                        </Card>
                    </li>
                ))}
            </ul>

            <div className="mt-4 flex flex-col items-stretch gap-3 rounded-2xl border border-ink-100 bg-ink-50/40 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-ink-700/60 dark:bg-ink-900/30">
                <p className="text-xs text-ink-600 dark:text-ink-300">
                    En envoyant ce questionnaire, vous confirmez que vos réponses sont anonymes et qu'aucune donnée identifiante n'est conservée.
                </p>
                <Button type="submit" disabled={!canSubmit || processing} loading={processing}>
                    Envoyer mes réponses
                </Button>
            </div>
        </form>);
}

function QuestionField({
    q,
    value,
    onChange,
    disabled,
}: {
    q: Question;
    value: MoodValue | LikertValue | string | null;
    onChange: (v: MoodValue | LikertValue | string | null) => void;
    disabled?: boolean;
}) {
    if (q.type === 'mood') {
        return (
            <MoodSelector
                name={q.id}
                value={(value as MoodValue) ?? null}
                onChange={(v) => onChange(v)}
                disabled={disabled}
            />
        );
    }
    if (q.type === 'likert') {
        return (
            <LikertScale
                name={q.id}
                value={(value as LikertValue) ?? null}
                onChange={(v) => onChange(v)}
                minLabel={q.min_label}
                maxLabel={q.max_label}
                disabled={disabled}
            />
        );
    }
    return (
        <textarea
            name={q.id}
            value={(value as string) ?? ''}
            onChange={(e) => onChange(e.target.value)}
            rows={4}
            maxLength={2000}
            disabled={disabled}
            placeholder="Votre réponse (optionnelle, anonyme)…"
            className="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100 dark:border-ink-700 dark:bg-ink-900/40 dark:text-white dark:placeholder:text-ink-500 dark:focus:ring-brand-900/30"
        />
    );
}

function HeartIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
        </svg>
    );
}
