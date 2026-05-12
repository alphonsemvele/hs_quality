import { moveItem, useReorderable } from '@/lib/reorderable';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface QuestionDraft {
    /** Local-only id for React key + DnD; not sent to backend. */
    localId: string;
    key: string;
    label: string;
    scale: '1-5' | '1-10' | 'yes_no';
    category: string;
}

const SCALES: Array<{ value: QuestionDraft['scale']; label: string; description: string }> = [
    { value: '1-5', label: '1 — 5', description: 'Likert simple (5 niveaux). Idéal pour ressenti / accord.' },
    { value: '1-10', label: '1 — 10', description: 'Échelle large (10 niveaux). Idéal pour eNPS / satisfaction.' },
    { value: 'yes_no', label: 'Oui / Non', description: 'Binaire. Idéal pour conformité / pratique observée.' },
];

const FREQUENCIES = [
    { value: 'weekly', label: 'Hebdomadaire' },
    { value: 'monthly', label: 'Mensuelle' },
    { value: 'quarterly', label: 'Trimestrielle' },
    { value: 'biannual', label: 'Semestrielle' },
    { value: 'annual', label: 'Annuelle' },
];

const SUGGESTED_CATEGORIES = ['charge', 'soutien', 'sens', 'recup', 'autonomie', 'reconnaissance'];

function slug(text: string): string {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .slice(0, 64);
}

function newDraft(label = ''): QuestionDraft {
    return {
        localId: `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`,
        key: slug(label),
        label,
        scale: '1-5',
        category: '',
    };
}

export default function QvctQuestionnaireCreate() {
    const [drafts, setDrafts] = useState<QuestionDraft[]>([newDraft('Comment évaluez-vous votre charge de travail cette semaine ?')]);

    const { data, setData, post, processing, errors, transform } = useForm({
        title: '',
        frequency: 'monthly',
    });

    // Strip localId from drafts before sending — backend expects
    // {key, label, scale, category} only.
    transform((current) => ({
        ...current,
        questions: drafts.map(({ localId: _drop, ...q }) => q),
    }));

    const updateQuestion = (localId: string, patch: Partial<QuestionDraft>) => {
        setDrafts((prev) =>
            prev.map((q) => {
                if (q.localId !== localId) return q;
                const next = { ...q, ...patch };
                // Auto-slug the key when label changes IF user hasn't customised it.
                if (patch.label !== undefined && (q.key === slug(q.label) || q.key === '')) {
                    next.key = slug(patch.label);
                }
                return next;
            }),
        );
    };

    const removeQuestion = (localId: string) => setDrafts((prev) => prev.filter((q) => q.localId !== localId));
    const addQuestion = () => setDrafts((prev) => [...prev, newDraft()]);

    const reorderQuestions = (fromId: string, toId: string) => {
        const fromIdx = drafts.findIndex((q) => q.localId === fromId);
        const toIdx = drafts.findIndex((q) => q.localId === toId);
        if (fromIdx === -1 || toIdx === -1) return;
        setDrafts(moveItem(drafts, fromIdx, toIdx));
    };

    const moveQuestion = (localId: string, direction: -1 | 1) => {
        const idx = drafts.findIndex((q) => q.localId === localId);
        if (idx === -1) return;
        const target = idx + direction;
        if (target < 0 || target >= drafts.length) return;
        setDrafts(moveItem(drafts, idx, target));
    };

    const { draggedId, overId, bindItem } = useReorderable(reorderQuestions);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/qvct/questionnaires');
    };

    const questions = drafts;

    const validQuestions = questions.filter((q) => q.label.trim().length > 0 && q.key.length > 0);
    const hasDuplicateKeys = new Set(validQuestions.map((q) => q.key)).size !== validQuestions.length;
    const canSubmit = data.title.trim().length > 2 && validQuestions.length > 0 && !hasDuplicateKeys;

    return (
        <DashboardLayout title="Nouveau questionnaire QVCT" subtitle="Builder de questionnaire">
            <PageHeader
                title="Nouveau questionnaire QVCT"
                subtitle="Composez les questions et la cadence avant de lancer une campagne."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Questionnaires', href: '/qvct/questionnaires' },
                    { label: 'Nouveau' },
                ]}
            />

            <form onSubmit={submit} className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Métadonnées */}
                <Card className="lg:col-span-1 lg:sticky lg:top-20 lg:self-start">
                    <CardHeader title="Métadonnées" subtitle="Titre, cadence et résumé" />
                    <CardBody className="space-y-4">
                        <FormField label="Titre" htmlFor="qst-title" required error={errors.title} help="Ex: « Baromètre mensuel — Bien-être au travail »">
                            <Input
                                id="qst-title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                required
                                maxLength={255}
                                invalid={!!errors.title}
                            />
                        </FormField>

                        <FormField label="Cadence" htmlFor="qst-freq" required error={errors.frequency} help="Fréquence à laquelle les campagnes basées sur ce questionnaire seront lancées.">
                            <Select
                                id="qst-freq"
                                value={data.frequency}
                                onChange={(e) => setData('frequency', e.target.value)}
                                required
                            >
                                {FREQUENCIES.map((f) => (
                                    <option key={f.value} value={f.value}>
                                        {f.label}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <div className="rounded-xl border border-brand-200 bg-brand-50/40 p-3 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                            <p className="font-semibold text-brand-900 dark:text-brand-200">Récapitulatif</p>
                            <ul className="mt-2 space-y-1 text-brand-900/80 dark:text-brand-200/80">
                                <li>{validQuestions.length} question{validQuestions.length > 1 ? 's' : ''} valide{validQuestions.length > 1 ? 's' : ''}</li>
                                {hasDuplicateKeys && <li className="font-semibold text-danger-600 dark:text-danger-400">⚠ Clés en doublon — corrigez avant publication</li>}
                            </ul>
                        </div>
                    </CardBody>
                    <CardFooter>
                        <Button type="submit" loading={processing} disabled={!canSubmit}>
                            Créer le questionnaire
                        </Button>
                    </CardFooter>
                </Card>

                {/* Questions builder */}
                <div className="lg:col-span-2">
                    <Card>
                        <CardHeader
                            title="Questions"
                            subtitle={`${questions.length} question${questions.length > 1 ? 's' : ''} — glissez pour réordonner`}
                            action={
                                <Button size="sm" variant="secondary" onClick={addQuestion} type="button">
                                    + Ajouter une question
                                </Button>
                            }
                        />
                        <CardBody className="space-y-3">
                            {questions.map((q, idx) => {
                                const reorderProps = bindItem(q.localId);
                                const isDragged = q.localId === draggedId;
                                const isDropTarget = q.localId === overId && !isDragged;
                                const keyError = validQuestions.filter((vq) => vq.key === q.key && vq.localId !== q.localId).length > 0;
                                return (
                                    <article
                                        key={q.localId}
                                        {...reorderProps}
                                        className={cn(
                                            'rounded-xl border-2 bg-white p-4 transition-all dark:bg-ink-800',
                                            isDragged && 'opacity-40',
                                            isDropTarget && 'border-brand-500 shadow-md',
                                            !isDropTarget && 'border-ink-100 dark:border-ink-700/60',
                                        )}
                                    >
                                        <div className="flex items-start gap-3">
                                            <div className="flex shrink-0 flex-col items-center gap-1 self-stretch">
                                                <button
                                                    type="button"
                                                    onClick={() => moveQuestion(q.localId, -1)}
                                                    disabled={idx === 0}
                                                    aria-label="Monter cette question"
                                                    className="rounded p-0.5 text-ink-300 hover:bg-ink-100 hover:text-ink-600 disabled:opacity-30 dark:text-ink-600 dark:hover:bg-ink-700 dark:hover:text-ink-200"
                                                >
                                                    <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                        <polyline points="18 15 12 9 6 15" strokeLinecap="round" strokeLinejoin="round" />
                                                    </svg>
                                                </button>
                                                <span aria-hidden className="cursor-move text-ink-400 dark:text-ink-600">
                                                    <svg className="size-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                                        <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                                        <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                                                    </svg>
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => moveQuestion(q.localId, 1)}
                                                    disabled={idx === questions.length - 1}
                                                    aria-label="Descendre cette question"
                                                    className="rounded p-0.5 text-ink-300 hover:bg-ink-100 hover:text-ink-600 disabled:opacity-30 dark:text-ink-600 dark:hover:bg-ink-700 dark:hover:text-ink-200"
                                                >
                                                    <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                        <polyline points="6 9 12 15 18 9" strokeLinecap="round" strokeLinejoin="round" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {idx + 1}
                                            </div>

                                            <div className="min-w-0 flex-1 space-y-3">
                                                <FormField label="Libellé de la question" htmlFor={`q-${q.localId}-label`} required>
                                                    <Input
                                                        id={`q-${q.localId}-label`}
                                                        value={q.label}
                                                        onChange={(e) => updateQuestion(q.localId, { label: e.target.value })}
                                                        maxLength={500}
                                                        placeholder="Ex: Je me sens soutenu·e par mes collègues et ma hiérarchie."
                                                    />
                                                </FormField>

                                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    <FormField label="Clé technique" htmlFor={`q-${q.localId}-key`} help="lettres minuscules, chiffres, underscore" error={keyError ? 'Cette clé est déjà utilisée par une autre question.' : undefined}>
                                                        <Input
                                                            id={`q-${q.localId}-key`}
                                                            value={q.key}
                                                            onChange={(e) => updateQuestion(q.localId, { key: slug(e.target.value) })}
                                                            maxLength={64}
                                                            invalid={keyError}
                                                            className="font-mono text-xs"
                                                        />
                                                    </FormField>

                                                    <FormField label="Catégorie (optionnel)" htmlFor={`q-${q.localId}-cat`} help="Groupe d'analyse (ex: charge, soutien)">
                                                        <Input
                                                            id={`q-${q.localId}-cat`}
                                                            list={`cat-suggestions-${q.localId}`}
                                                            value={q.category}
                                                            onChange={(e) => updateQuestion(q.localId, { category: e.target.value })}
                                                            maxLength={64}
                                                            placeholder="charge"
                                                        />
                                                        <datalist id={`cat-suggestions-${q.localId}`}>
                                                            {SUGGESTED_CATEGORIES.map((c) => (
                                                                <option key={c} value={c} />
                                                            ))}
                                                        </datalist>
                                                    </FormField>
                                                </div>

                                                <div>
                                                    <p className="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                                        Échelle de réponse
                                                    </p>
                                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                                        {SCALES.map((s) => {
                                                            const active = q.scale === s.value;
                                                            return (
                                                                <button
                                                                    key={s.value}
                                                                    type="button"
                                                                    onClick={() => updateQuestion(q.localId, { scale: s.value })}
                                                                    className={cn(
                                                                        'rounded-lg border-2 px-2.5 py-2 text-left transition-colors',
                                                                        active
                                                                            ? 'border-brand-500 bg-brand-50/40 dark:border-brand-400 dark:bg-brand-900/20'
                                                                            : 'border-ink-200 hover:border-ink-300 dark:border-ink-700 dark:hover:border-ink-600',
                                                                    )}
                                                                >
                                                                    <p className="text-xs font-semibold text-ink-900 dark:text-white">{s.label}</p>
                                                                    <p className="mt-0.5 text-[10px] text-ink-500 dark:text-ink-400">{s.description}</p>
                                                                </button>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() => removeQuestion(q.localId)}
                                                aria-label="Supprimer cette question"
                                                className="shrink-0 rounded-md p-1.5 text-ink-400 transition-colors hover:bg-danger-50 hover:text-danger-600 dark:text-ink-500 dark:hover:bg-danger-900/30 dark:hover:text-danger-400"
                                            >
                                                <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                                    <polyline points="3 6 5 6 21 6" />
                                                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                                </svg>
                                            </button>
                                        </div>
                                    </article>
                                );
                            })}

                            {questions.length === 0 && (
                                <div className="rounded-xl border-2 border-dashed border-ink-200 px-6 py-10 text-center dark:border-ink-700">
                                    <p className="text-sm text-ink-500 dark:text-ink-400">
                                        Aucune question — ajoutez-en pour démarrer.
                                    </p>
                                    <Button type="button" variant="secondary" className="mt-3" onClick={addQuestion}>
                                        Ajouter ma première question
                                    </Button>
                                </div>
                            )}

                            <div className="flex justify-center pt-2">
                                <Button type="button" variant="secondary" onClick={addQuestion} leadingIcon={<PlusIcon />}>
                                    Ajouter une question
                                </Button>
                            </div>
                        </CardBody>
                    </Card>

                    <div className="mt-4 rounded-xl border border-sage-200 bg-sage-50/40 p-3 text-xs dark:border-sage-700/40 dark:bg-sage-900/15">
                        <p className="font-semibold text-sage-900 dark:text-sage-100">💡 Bonnes pratiques</p>
                        <ul className="mt-1.5 space-y-1 text-sage-900/80 dark:text-sage-200/80">
                            <li>• <strong className="font-semibold">5-10 questions</strong> maximum pour un bon taux de participation</li>
                            <li>• Utilisez la même <strong className="font-semibold">échelle</strong> pour permettre comparaison entre questions</li>
                            <li>• Les <strong className="font-semibold">catégories</strong> servent à l'agrégation dans la cartographie RPS</li>
                            <li>• Le questionnaire est <strong className="font-semibold">versionné automatiquement</strong> — modifiable après création</li>
                        </ul>
                    </div>
                </div>
            </form>
        </DashboardLayout>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
