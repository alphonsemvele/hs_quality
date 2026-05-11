import { AnonymityBanner, MoodSelector, type MoodValue } from '@/components/qvct';
import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Form } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface JournalEntry {
    id: string;
    date: string;
    mood: 1 | 2 | 3 | 4 | 5;
    content: string;
    shared_with_rh: boolean;
    shared_at?: string;
}

interface Props {
    entries: JournalEntry[];
    shared_count: number;
}

const MOOD_EMOJI: Record<JournalEntry['mood'], string> = {
    1: '😞',
    2: '😕',
    3: '😐',
    4: '🙂',
    5: '😄',
};

const MOOD_LABEL: Record<JournalEntry['mood'], string> = {
    1: 'Très difficile',
    2: 'Difficile',
    3: 'Neutre',
    4: 'Bon',
    5: 'Très bon',
};

export default function JournalIndex({ entries = [], shared_count = 0 }: Partial<Props>) {
    const [mood, setMood] = useState<MoodValue | null>(null);
    const [share, setShare] = useState(false);

    return (
        <DashboardLayout title="Mon journal" subtitle="Espace personnel et confidentiel">
            <PageHeader
                title="Mon journal psychosocial"
                subtitle="Espace personnel et confidentiel — vous choisissez ce que vous partagez avec la RH"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Mon journal' },
                ]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* New entry form */}
                <div className="lg:col-span-2">
                    <Card>
                        <CardHeader title="Nouvelle entrée" subtitle="Comment s'est passée cette journée ?" />
                        <CardBody>
                            <Form action="/qvct/journal" method="post" resetOnSuccess onSuccess={() => { setMood(null); setShare(false); }}>
                                {({ processing }) => (
                                    <div className="space-y-5">
                                        <div>
                                            <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                                Mon humeur
                                            </label>
                                            <MoodSelector value={mood} onChange={setMood} name="mood" disabled={processing} />
                                            <input type="hidden" name="mood" value={mood ?? ''} />
                                        </div>

                                        <div>
                                            <label htmlFor="content" className="mb-2 block text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                                Notes (optionnel)
                                            </label>
                                            <textarea
                                                id="content"
                                                name="content"
                                                rows={5}
                                                maxLength={3000}
                                                disabled={processing}
                                                placeholder="Ce que vous avez vécu, ressenti, observé… Cet espace est privé sauf si vous choisissez de partager."
                                                className="w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100 dark:border-ink-700 dark:bg-ink-900/40 dark:text-white dark:placeholder:text-ink-500 dark:focus:ring-brand-900/30"
                                            />
                                        </div>

                                        <div className="flex flex-col items-stretch gap-3 rounded-xl border border-ink-100 bg-ink-50/50 p-3 sm:flex-row sm:items-center sm:justify-between dark:border-ink-700/60 dark:bg-ink-900/30">
                                            <label className="flex cursor-pointer items-start gap-3">
                                                <input
                                                    type="checkbox"
                                                    name="shared_with_rh"
                                                    value="1"
                                                    checked={share}
                                                    onChange={(e) => setShare(e.target.checked)}
                                                    className="mt-0.5 size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-400 dark:border-ink-600 dark:bg-ink-800"
                                                />
                                                <span className="text-xs text-ink-700 dark:text-ink-200">
                                                    <span className="font-medium">Partager avec la RH</span>
                                                    <span className="block text-[11px] text-ink-500 dark:text-ink-400">
                                                        Si coché, cette entrée sera lisible par votre référent·e RH. Sinon, elle reste privée.
                                                    </span>
                                                </span>
                                            </label>
                                            <Button type="submit" disabled={mood === null} loading={processing}>
                                                Enregistrer
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </Form>
                        </CardBody>
                    </Card>

                    {/* History */}
                    <Card className="mt-5">
                        <CardHeader
                            title="Mes dernières entrées"
                            subtitle={`${entries.length} entrée(s) — ${shared_count} partagée(s) avec RH`}
                        />
                        <CardBody>
                            {entries.length > 0 ? (
                                <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                    {entries.map((e) => (
                                        <li key={e.id} className="py-3.5 first:pt-0 last:pb-0">
                                            <div className="flex items-start gap-3">
                                                <span
                                                    aria-hidden
                                                    className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-ink-50 text-xl dark:bg-ink-700/50"
                                                >
                                                    {MOOD_EMOJI[e.mood]}
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-mono text-xs font-semibold text-ink-700 dark:text-ink-300">
                                                            {e.date}
                                                        </span>
                                                        <Badge tone={moodTone(e.mood)} size="xs">
                                                            {MOOD_LABEL[e.mood]}
                                                        </Badge>
                                                        {e.shared_with_rh ? (
                                                            <Badge tone="brand" size="xs" dot>
                                                                Partagée RH
                                                            </Badge>
                                                        ) : (
                                                            <Badge tone="neutral" size="xs">
                                                                Privée
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <p className="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-200">
                                                        {e.content}
                                                    </p>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState
                                    icon={<BookIcon />}
                                    title="Pas encore d'entrée"
                                    description="Tenir un journal aide à prendre du recul. Commencez par enregistrer votre humeur du jour."
                                />
                            )}
                        </CardBody>
                    </Card>
                </div>

                {/* Sidebar */}
                <aside>
                    <AnonymityBanner variant="callout" />

                    <Card className="mt-4 border-brand-200 bg-brand-50/40 dark:border-brand-700/40 dark:bg-brand-900/15">
                        <CardHeader title="Comment ça marche ?" />
                        <CardBody className="space-y-2.5 text-[12px] text-brand-900/80 dark:text-brand-100/80">
                            <p>
                                <strong className="font-semibold">Privé par défaut.</strong> Vos entrées ne sont
                                accessibles qu'à vous.
                            </p>
                            <p>
                                <strong className="font-semibold">Partage explicite.</strong> Si vous cochez « Partager
                                avec la RH », votre référent·e RH pourra lire l'entrée et vous proposer un échange.
                            </p>
                            <p>
                                <strong className="font-semibold">Pas d'utilisation analytique.</strong> Le contenu de
                                votre journal n'alimente jamais les indicateurs agrégés.
                            </p>
                        </CardBody>
                    </Card>
                </aside>
            </div>
        </DashboardLayout>
    );
}

function moodTone(mood: JournalEntry['mood']): 'danger' | 'warning' | 'neutral' | 'sage' {
    if (mood <= 2) return 'danger';
    if (mood === 3) return 'neutral';
    if (mood === 4) return 'sage';
    return 'sage';
}

function BookIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5V4.5A2.5 2.5 0 016.5 2z" />
        </svg>
    );
}
