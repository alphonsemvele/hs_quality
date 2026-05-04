import { Button, Card, CardBody, CardFooter, CardHeader, EmptyState, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Form, Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Question {
    id: number;
    texte: string;
    type: 'echelle' | 'texte_libre' | 'choix_multiple';
    options?: string[];
}

interface Campagne {
    id: string;
    titre: string;
}

interface Props {
    questions: Question[];
    campagne: Campagne | null;
}

export default function QvctQuestionnaire({ questions = [], campagne }: Partial<Props>) {
    const defaultQuestions: Question[] = questions.length > 0 ? questions : [
        { id: 1, texte: 'Comment évaluez-vous votre charge de travail actuelle ?', type: 'echelle' },
        { id: 2, texte: 'Vous sentez-vous soutenu(e) par votre encadrement ?', type: 'echelle' },
        { id: 3, texte: 'Comment jugez-vous l\'ambiance au sein de votre équipe ?', type: 'echelle' },
        { id: 4, texte: 'Disposez-vous des moyens matériels nécessaires ?', type: 'echelle' },
        { id: 5, texte: 'Comment évaluez-vous votre équilibre vie pro / vie perso ?', type: 'echelle' },
        { id: 6, texte: 'Avez-vous des suggestions d\'amélioration ?', type: 'texte_libre' },
    ];

    return (
        <DashboardLayout title="Questionnaire QVCT" subtitle="">
            <PageHeader
                title="Questionnaire QVCT"
                subtitle="Réponses anonymes — aucune identification individuelle transmise au management."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Questionnaire' },
                ]}
            />

            <div className="mb-5 flex items-start gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                <svg className="size-5 shrink-0 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p className="text-sm text-brand-700 dark:text-brand-300">
                    Ce questionnaire est <strong>strictement anonyme</strong>. Les résultats sont agrégés par équipe (minimum 5 réponses) pour garantir la non-identification individuelle conformément au RGPD.
                </p>
            </div>

            <Form action="/qvct" method="post">
                {({ errors, processing }) => (
                    <Card>
                        <CardHeader title="Baromètre QVCT" subtitle={`${defaultQuestions.length} question(s)`} />
                        <CardBody className="space-y-6">
                            {defaultQuestions.map((q, index) => (
                                <div key={q.id} className="rounded-xl border border-ink-100 bg-ink-50/30 p-4 dark:border-ink-700/60 dark:bg-ink-800/50">
                                    <p className="mb-3 text-sm font-medium text-ink-900 dark:text-white">
                                        <span className="mr-2 font-mono text-xs text-ink-400 dark:text-ink-500">{index + 1}.</span>
                                        {q.texte}
                                    </p>
                                    {q.type === 'echelle' ? (
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs text-ink-500 dark:text-ink-400">Pas du tout</span>
                                            <div className="flex gap-1.5">
                                                {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((v) => (
                                                    <label key={v} className="group cursor-pointer">
                                                        <input type="radio" name={`q_${q.id}`} value={v} className="peer sr-only" />
                                                        <span className="flex size-9 items-center justify-center rounded-lg border border-ink-200 bg-white text-xs font-medium text-ink-600 transition-all peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-white group-hover:border-ink-300 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-300 dark:peer-checked:border-brand-400 dark:peer-checked:bg-brand-500 dark:group-hover:border-ink-500">
                                                            {v}
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                            <span className="text-xs text-ink-500 dark:text-ink-400">Totalement</span>
                                        </div>
                                    ) : (
                                        <Textarea name={`q_${q.id}`} rows={3} placeholder="Votre réponse (optionnelle)…" />
                                    )}
                                </div>
                            ))}
                        </CardBody>
                        <CardFooter>
                            <Link href="/qvct" className="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700">
                                Annuler
                            </Link>
                            <Button type="submit" loading={processing}>Soumettre (anonyme)</Button>
                        </CardFooter>
                    </Card>
                )}
            </Form>
        </DashboardLayout>
    );
}
