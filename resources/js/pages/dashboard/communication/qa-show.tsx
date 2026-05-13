import { Badge, Button, Card, CardBody, EmptyState, PageHeader } from '@/components/ui';
import { renderSafeMarkdown } from '@/lib/safe-markdown';
import { Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Answer {
    id: string;
    body: string;
    author: string;
    upvotes: number;
    is_accepted: boolean;
    created_at: string;
}

interface Question {
    id: string;
    title: string;
    body: string;
    author: string;
    created_at: string;
    accepted_answer_id: string | null;
    answers: Answer[];
}

interface Props {
    question: Question;
    can_accept: boolean;
    can_answer: boolean;
}

export default function QuestionShow({ question, can_accept, can_answer }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({ body: '' });

    const submitAnswer = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/communication/qa/${question.id}/answers`, {
            preserveScroll: true,
            onSuccess: () => reset('body'),
        });
    };

    const vote = (answerId: string) => {
        router.post(`/communication/qa/answers/${answerId}/vote`, undefined, { preserveScroll: true });
    };

    const accept = (answerId: string) => {
        router.post(
            `/communication/qa/${question.id}/accept-answer`,
            { answer_id: answerId },
            { preserveScroll: true },
        );
    };

    return (
        <DashboardLayout title={`Q&A · ${question.title}`} subtitle="Forum interne — entraide et expertise">
            <PageHeader
                title={question.title}
                subtitle={`Posée par ${question.author} · ${question.created_at}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Communication', href: '/communication' },
                    { label: 'Q&A' },
                    { label: question.title },
                ]}
                actions={
                    <Link
                        href="/communication"
                        className="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        ← Retour au forum
                    </Link>
                }
            />

            {/* Question body */}
            <Card>
                <CardBody>
                    <div className="prose-sm max-w-none">{renderSafeMarkdown(question.body)}</div>
                </CardBody>
            </Card>

            {/* Answers */}
            <section className="mt-8">
                <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    {question.answers.length} réponse{question.answers.length > 1 ? 's' : ''}
                </h2>

                {question.answers.length === 0 ? (
                    <Card>
                        <EmptyState
                            title="Aucune réponse encore"
                            description="Soyez le premier à partager votre expérience sur cette question."
                        />
                    </Card>
                ) : (
                    <ul className="space-y-3">
                        {question.answers.map((answer) => (
                            <li key={answer.id}>
                                <Card
                                    className={
                                        answer.is_accepted
                                            ? 'border-sage-300 ring-2 ring-sage-100 dark:border-sage-600/60 dark:ring-sage-900/40'
                                            : undefined
                                    }
                                >
                                    <CardBody>
                                        <div className="flex gap-4">
                                            {/* Vote column */}
                                            <div className="flex w-12 shrink-0 flex-col items-center gap-1.5 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() => vote(answer.id)}
                                                    className="flex size-7 items-center justify-center rounded-md border border-ink-200 bg-white text-ink-500 transition-colors hover:border-brand-400 hover:bg-brand-50 hover:text-brand-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-400 dark:hover:border-brand-500 dark:hover:bg-brand-900/30"
                                                    aria-label="Voter pour cette réponse"
                                                >
                                                    <ChevronUpIcon />
                                                </button>
                                                <span className="font-mono text-sm font-bold tabular-nums text-ink-900 dark:text-white">
                                                    {answer.upvotes}
                                                </span>
                                                {answer.is_accepted && (
                                                    <span
                                                        className="flex size-7 items-center justify-center rounded-full bg-sage-100 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300"
                                                        aria-label="Réponse acceptée"
                                                    >
                                                        <CheckIcon />
                                                    </span>
                                                )}
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    {answer.is_accepted && (
                                                        <Badge tone="sage" size="xs" dot>
                                                            Réponse acceptée
                                                        </Badge>
                                                    )}
                                                    <span className="text-xs text-ink-500 dark:text-ink-400">
                                                        <span className="font-medium text-ink-700 dark:text-ink-200">{answer.author}</span> ·{' '}
                                                        {answer.created_at}
                                                    </span>
                                                </div>
                                                <div className="mt-3">{renderSafeMarkdown(answer.body)}</div>

                                                {can_accept && !answer.is_accepted && (
                                                    <div className="mt-4">
                                                        <button
                                                            type="button"
                                                            onClick={() => accept(answer.id)}
                                                            className="inline-flex items-center gap-1.5 rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-medium text-ink-700 transition-colors hover:border-sage-400 hover:bg-sage-50 hover:text-sage-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:border-sage-500 dark:hover:bg-sage-900/30"
                                                        >
                                                            <CheckIcon />
                                                            Marquer comme acceptée
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </CardBody>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            {/* Answer composer */}
            {can_answer && (
                <Card className="mt-8">
                    <CardBody>
                        <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Votre réponse</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Markdown supporté : <code className="font-mono text-[11px]">**gras**</code>, listes, liens https.
                        </p>
                        <form onSubmit={submitAnswer} className="mt-3">
                            <textarea
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                                rows={5}
                                required
                                maxLength={20000}
                                placeholder="Partagez votre expérience ou la procédure officielle…"
                                className="block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                            />
                            {errors.body && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.body}</p>}
                            <div className="mt-3 flex justify-end">
                                <Button type="submit" disabled={processing || data.body.trim() === ''}>
                                    {processing ? 'Envoi…' : 'Publier la réponse'}
                                </Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            )}
        </DashboardLayout>
    );
}

function ChevronUpIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
            <path d="M18 15l-6-6-6 6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function CheckIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5}>
            <path d="M5 12l5 5L20 7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
