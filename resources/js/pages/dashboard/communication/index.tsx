import { Badge, Button, Card, CardBody, CardHeader, DropzoneUploader, EmptyState, PageHeader, RichTextEditor } from '@/components/ui';
import { useCan } from '@/lib/can';
import { renderSafeMarkdown } from '@/lib/safe-markdown';
import { useUrlTab } from '@/lib/use-url-tab';
import { cn } from '@/lib/utils';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Channel {
    id: string;
    name: string;
    nb_members: number;
    last_activity: string | null;
}

interface ThreadMessage {
    id: string;
    author: string;
    initials: string;
    content: string;
    created_at: string;
    is_self: boolean;
}

interface CurrentChannel {
    id: string;
    name: string;
    description: string;
    nb_members: number;
    messages: ThreadMessage[];
}

interface NewsPost {
    id: string;
    title: string;
    body: string;
    author: string;
    pinned: boolean;
    created_at: string;
}

interface Document {
    id: string;
    title: string;
    type: string;
    size_kb?: number;
    uploaded_by: string;
    uploaded_at: string;
}

interface QaQuestion {
    id: string;
    title: string;
    asker: string;
    votes: number;
    answers_count: number;
    accepted: boolean;
    created_at: string;
    preview: string;
}

interface Props {
    channels: Channel[];
    currentChannel: CurrentChannel | null;
    newsPosts: NewsPost[];
    documents: Document[];
    qaQuestions: QaQuestion[];
}

type Tab = 'messages' | 'news' | 'docs' | 'qa';

const COMMUNICATION_TABS: readonly Tab[] = ['messages', 'news', 'docs', 'qa'];

export default function CommunicationIndex({
    channels = [],
    currentChannel = null,
    newsPosts = [],
    documents = [],
    qaQuestions = [],
}: Partial<Props>) {
    const [tab, setTab] = useUrlTab<Tab>('messages', COMMUNICATION_TABS);
    const canPublish = useCan('communication.post');

    return (
        <DashboardLayout title="Communication" subtitle="Messagerie, actualités, documents, Q&A">
            <PageHeader
                title="Communication interne"
                subtitle="Messagerie temps réel, fil d'actualité, bibliothèque documentaire et forum Q&A"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Communication' }]}
            />

            <div className="mb-5 flex flex-wrap gap-1 rounded-lg border border-ink-200 bg-white p-1 dark:border-ink-700 dark:bg-ink-800">
                <TabButton active={tab === 'messages'} onClick={() => setTab('messages')} icon={<ChatIcon />}>
                    Messages
                </TabButton>
                <TabButton
                    active={tab === 'news'}
                    onClick={() => setTab('news')}
                    icon={<NewsIcon />}
                    badge={newsPosts.filter((p) => p.pinned).length}
                >
                    Actualités
                </TabButton>
                <TabButton active={tab === 'docs'} onClick={() => setTab('docs')} icon={<FileIcon />}>
                    Documents
                </TabButton>
                <TabButton active={tab === 'qa'} onClick={() => setTab('qa')} icon={<HelpIcon />}>
                    Q&A
                </TabButton>
            </div>

            {tab === 'messages' && <MessagesTab channels={channels} channel={currentChannel} />}
            {tab === 'news' && <NewsTab posts={newsPosts} canPublish={canPublish} />}
            {tab === 'docs' && <DocsTab documents={documents} />}
            {tab === 'qa' && <QaTab questions={qaQuestions} />}
        </DashboardLayout>
    );
}

function TabButton({
    active,
    onClick,
    icon,
    badge,
    children,
}: {
    active: boolean;
    onClick: () => void;
    icon?: React.ReactNode;
    badge?: number;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors sm:flex-initial sm:px-4',
                active
                    ? 'bg-brand-600 text-white shadow-sm'
                    : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700/60',
            )}
        >
            <span className="size-3.5">{icon}</span>
            {children}
            {badge !== undefined && badge > 0 && (
                <span
                    className={cn(
                        'inline-flex min-w-[18px] items-center justify-center rounded-full px-1.5 py-0.5 font-mono text-[10px] font-bold',
                        active ? 'bg-white/20 text-white' : 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300',
                    )}
                >
                    {badge}
                </span>
            )}
        </button>
    );
}

function MessagesTab({ channels, channel }: { channels: Channel[]; channel: CurrentChannel | null }) {
    const [messageQuery, setMessageQuery] = useState('');
    const filteredMessages = channel
        ? channel.messages.filter((m) => {
              const needle = messageQuery.trim().toLowerCase();
              if (!needle) return true;
              return (
                  m.content.toLowerCase().includes(needle) ||
                  m.author.toLowerCase().includes(needle)
              );
          })
        : [];
    return (
        <div className="grid grid-cols-1 gap-5 lg:grid-cols-4">
            {/* Channels sidebar */}
            <Card className="lg:col-span-1">
                <CardHeader title="Groupes" subtitle={`${channels.length} canal(aux)`} />
                <CardBody className="px-2 py-2">
                    {channels.length > 0 ? (
                        <ul className="flex flex-col gap-0.5">
                            {channels.map((c) => {
                                const active = channel?.id === c.id;
                                return (
                                    <li key={c.id}>
                                        <button
                                            type="button"
                                            className={cn(
                                                'flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors',
                                                active
                                                    ? 'bg-brand-50 text-brand-900 dark:bg-brand-900/30 dark:text-brand-100'
                                                    : 'text-ink-700 hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/40',
                                            )}
                                        >
                                            <span className="min-w-0">
                                                <span className="block truncate font-medium"># {c.name}</span>
                                                <span className="block truncate text-[11px] text-ink-500 dark:text-ink-400">
                                                    {c.nb_members} membres · {c.last_activity ?? '—'}
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    ) : (
                        <EmptyState title="Aucun groupe" description="Les groupes seront configurés par votre coordinateur." />
                    )}
                </CardBody>
            </Card>

            {/* Thread */}
            <Card className="lg:col-span-3">
                {channel ? (
                    <>
                        <CardHeader
                            title={`# ${channel.name}`}
                            subtitle={`${channel.description} · ${channel.nb_members} membres`}
                            action={
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-sage-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sage-700 dark:bg-sage-900/30 dark:text-sage-300">
                                    <span className="size-1.5 animate-pulse rounded-full bg-sage-500" />
                                    Temps réel
                                </span>
                            }
                        />
                        <div className="border-b border-ink-100 px-4 py-2 dark:border-ink-700/60">
                            <div className="relative">
                                <input
                                    type="search"
                                    value={messageQuery}
                                    onChange={(e) => setMessageQuery(e.target.value)}
                                    placeholder="Filtrer les messages du salon…"
                                    className="block w-full rounded-lg border border-ink-200 bg-white pl-9 pr-3 py-1.5 text-xs text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                                />
                                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400">
                                    <svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="M21 21l-4.35-4.35" strokeLinecap="round" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <CardBody className="flex h-[28rem] flex-col gap-3 overflow-y-auto p-4">
                            {filteredMessages.length === 0 ? (
                                <p className="my-auto text-center text-xs text-ink-400 dark:text-ink-500">
                                    {messageQuery
                                        ? 'Aucun message ne correspond.'
                                        : 'Aucun message dans ce salon pour le moment.'}
                                </p>
                            ) : (
                                filteredMessages.map((m) => <MessageBubble key={m.id} m={m} />)
                            )}
                        </CardBody>
                        <div className="border-t border-ink-100 p-3 dark:border-ink-700/60">
                            <div className="flex items-center gap-2">
                                <input
                                    type="text"
                                    placeholder={`Message dans #${channel.name}…`}
                                    className="h-10 flex-1 rounded-lg border border-ink-200 bg-white px-3 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100 dark:border-ink-700 dark:bg-ink-900/40 dark:text-white dark:placeholder:text-ink-500 dark:focus:ring-brand-900/30"
                                />
                                <Button size="sm">Envoyer</Button>
                            </div>
                            <p className="mt-1.5 text-[11px] text-ink-400 dark:text-ink-500">
                                Reverb WebSocket prêt côté backend — l'envoi se branche au prochain incrément.
                            </p>
                        </div>
                    </>
                ) : (
                    <CardBody>
                        <EmptyState
                            icon={<ChatIcon />}
                            title="Aucun canal sélectionné"
                            description="Choisissez un groupe dans la colonne de gauche pour afficher la conversation."
                        />
                    </CardBody>
                )}
            </Card>
        </div>
    );
}

function MessageBubble({ m }: { m: ThreadMessage }) {
    return (
        <div className={cn('flex items-start gap-3', m.is_self && 'flex-row-reverse')}>
            <div className={cn('flex size-9 shrink-0 items-center justify-center rounded-lg font-semibold text-white', m.is_self ? 'bg-brand-600' : 'bg-gradient-to-br from-ink-500 to-ink-700')}>
                <span className="text-[11px]">{m.initials}</span>
            </div>
            <div className={cn('min-w-0 max-w-[80%] rounded-2xl px-3.5 py-2', m.is_self ? 'bg-brand-600 text-white' : 'bg-ink-50 text-ink-900 dark:bg-ink-700/60 dark:text-ink-100')}>
                <div className={cn('flex items-baseline gap-2 text-[11px] font-medium', m.is_self ? 'text-white/80' : 'text-ink-500 dark:text-ink-400')}>
                    <span>{m.author}</span>
                    <span className="font-mono">{m.created_at}</span>
                </div>
                <p className="mt-0.5 whitespace-pre-line text-sm leading-relaxed">{m.content}</p>
            </div>
        </div>
    );
}

type NewsSort = 'recent' | 'pinned' | 'all';

function NewsTab({ posts, canPublish }: { posts: NewsPost[]; canPublish: boolean }) {
    const [composing, setComposing] = useState(false);
    const [sort, setSort] = useState<NewsSort>('pinned');

    const pinned = posts.filter((p) => p.pinned);
    const others = posts.filter((p) => !p.pinned);

    return (
        <div className="space-y-5">
            {canPublish && (
                <Card>
                    {composing ? (
                        <NewsComposer onClose={() => setComposing(false)} />
                    ) : (
                        <CardBody className="flex items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-ink-900 dark:text-white">Publier une actualité</p>
                                <p className="text-xs text-ink-500 dark:text-ink-400">
                                    Communiquez à toute la structure en Markdown — gras, listes, liens et titres.
                                </p>
                            </div>
                            <Button size="sm" leadingIcon={<PlusIcon />} onClick={() => setComposing(true)}>
                                Nouvelle actu
                            </Button>
                        </CardBody>
                    )}
                </Card>
            )}

            {posts.length > 0 && (
                <div className="flex flex-wrap items-center gap-1.5">
                    <span className="mr-1 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        Trier
                    </span>
                    {(
                        [
                            { key: 'pinned', label: `Épinglées d'abord (${pinned.length})` },
                            { key: 'recent', label: `Récentes (${posts.length})` },
                            { key: 'all', label: 'Tout afficher' },
                        ] as const
                    ).map((opt) => (
                        <button
                            key={opt.key}
                            type="button"
                            onClick={() => setSort(opt.key)}
                            aria-pressed={sort === opt.key}
                            className={
                                sort === opt.key
                                    ? 'inline-flex items-center rounded-full bg-ink-900 px-3 py-1 text-[11px] font-semibold text-white dark:bg-white dark:text-ink-900'
                                    : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-[11px] font-medium text-ink-600 transition-colors hover:border-ink-400 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300'
                            }
                        >
                            {opt.label}
                        </button>
                    ))}
                </div>
            )}

            {sort === 'pinned' && pinned.length > 0 && (
                <section>
                    <h3 className="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        <PinIcon /> Épinglées
                    </h3>
                    <ul className="space-y-3">
                        {pinned.map((p) => (
                            <li key={p.id}>
                                <NewsCard post={p} />
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {sort === 'pinned' && others.length > 0 && (
                <section>
                    {pinned.length > 0 && (
                        <h3 className="mb-3 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            Récentes
                        </h3>
                    )}
                    <ul className="space-y-3">
                        {others.map((p) => (
                            <li key={p.id}>
                                <NewsCard post={p} />
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {sort === 'recent' && (
                <section>
                    <ul className="space-y-3">
                        {posts.map((p) => (
                            <li key={p.id}>
                                <NewsCard post={p} />
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {sort === 'all' && (
                <section>
                    <ul className="space-y-3">
                        {[...pinned, ...others].map((p) => (
                            <li key={p.id}>
                                <NewsCard post={p} />
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {posts.length === 0 && (
                <Card>
                    <EmptyState icon={<NewsIcon />} title="Aucune actualité" description="Le fil d'actualité affichera les publications de votre structure." />
                </Card>
            )}
        </div>
    );
}

function NewsCard({ post }: { post: NewsPost }) {
    return (
        <article className={cn('rounded-2xl border bg-white p-4 transition-shadow hover:shadow-md dark:bg-ink-800', post.pinned ? 'border-warning-200 dark:border-warning-700/40' : 'border-ink-100 dark:border-ink-700/60')}>
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        {post.pinned && (
                            <Badge tone="warning" size="xs">
                                <PinIcon /> Épinglée
                            </Badge>
                        )}
                        <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{post.title}</h3>
                    </div>
                    <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                        <span className="font-medium">{post.author}</span> · {post.created_at}
                    </p>
                </div>
            </div>
            <div className="mt-3">{renderSafeMarkdown(post.body)}</div>
        </article>
    );
}

function NewsComposer({ onClose }: { onClose: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        body: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/communication/news', {
            preserveScroll: true,
            onSuccess: () => {
                reset('title', 'body');
                onClose();
            },
        });
    };

    return (
        <form onSubmit={submit} className="p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Nouvelle actualité</h3>
                    <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                        Markdown autorisé : <code className="font-mono text-[11px]">**gras**</code>,{' '}
                        <code className="font-mono text-[11px]">_italique_</code>, listes, liens https.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-full p-1.5 text-ink-400 transition-colors hover:bg-ink-100 hover:text-ink-700 dark:text-ink-500 dark:hover:bg-ink-700"
                    aria-label="Fermer"
                >
                    <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                        <path d="M18 6L6 18M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </button>
            </div>

            <label className="mt-4 block">
                <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                    Titre <span className="text-danger-500">*</span>
                </span>
                <input
                    type="text"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    required
                    maxLength={200}
                    placeholder="Ex. « Visite HAS — préparation »"
                    className="mt-1 block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                />
                {errors.title && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.title}</p>}
            </label>

            <div className="mt-4">
                <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                    Contenu <span className="text-danger-500">*</span>
                </span>
                <div className="mt-1">
                    <RichTextEditor
                        value={data.body}
                        onChange={(v) => setData('body', v)}
                        rows={10}
                        placeholder="## Rappel important&#10;&#10;La visite HAS aura lieu les **18-19 juin**.&#10;&#10;- Réunion préparatoire vendredi&#10;- Classeur de preuves à jour pour mardi"
                        disabled={processing}
                    />
                </div>
                {errors.body && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.body}</p>}
            </div>

            <div className="mt-5 flex flex-wrap items-center justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onClose} disabled={processing}>
                    Annuler
                </Button>
                <Button type="submit" disabled={processing || data.title.trim() === '' || data.body.trim() === ''}>
                    {processing ? 'Publication…' : 'Publier'}
                </Button>
            </div>
        </form>
    );
}

function DocsTab({ documents }: { documents: Document[] }) {
    const [showUploader, setShowUploader] = useState(false);

    const uploader = (
        <Card className={documents.length === 0 ? '' : 'mb-4'}>
            <CardHeader
                title="Téléverser un document"
                subtitle="Protocole, procédure, fiche pratique — visible par toute la structure."
                action={
                    showUploader ? (
                        <Button size="sm" variant="secondary" onClick={() => setShowUploader(false)}>
                            Fermer
                        </Button>
                    ) : undefined
                }
            />
            <CardBody>
                <DropzoneUploader
                    endpoint="/communication/documents"
                    accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png,image/webp"
                    acceptLabel="PDF, Word, Excel, JPG/PNG"
                    maxBytes={25 * 1024 * 1024}
                    multiple={false}
                    fields={[
                        {
                            name: 'title',
                            label: 'Titre du document',
                            help: 'Ex. « Protocole transmission v2 ». Une nouvelle version est créée automatiquement si le titre existe déjà.',
                            required: true,
                        },
                        {
                            name: 'description',
                            label: 'Description (optionnelle)',
                            help: 'Quelques mots pour aider vos collègues à comprendre quand consulter ce document.',
                        },
                    ]}
                    onAllSettled={() => {
                        // Refresh the documents tab so the new file appears.
                        router.reload({ only: ['documents'] });
                        setShowUploader(false);
                    }}
                />
            </CardBody>
        </Card>
    );

    if (documents.length === 0) {
        return (
            <>
                {showUploader ? (
                    uploader
                ) : (
                    <Card>
                        <EmptyState
                            icon={<FileIcon />}
                            title="Aucun document"
                            description="Partagez les protocoles, procédures et documents de référence ici."
                            action={<Button onClick={() => setShowUploader(true)}>Téléverser un document</Button>}
                        />
                    </Card>
                )}
            </>
        );
    }

    return (
        <>
            {showUploader && uploader}
            <Card>
                <CardHeader
                    title="Bibliothèque documentaire"
                    subtitle={`${documents.length} document(s)`}
                    action={
                        showUploader ? undefined : (
                            <Button size="sm" leadingIcon={<UploadIcon />} onClick={() => setShowUploader(true)}>
                                Téléverser
                            </Button>
                        )
                    }
                />
                <CardBody className="px-2 py-2">
                    <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                        {documents.map((d) => (
                            <li key={d.id} className="flex items-center gap-3 px-3 py-3">
                                <FileTypeBadge type={d.type} />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{d.title}</p>
                                    <p className="text-[11px] text-ink-500 dark:text-ink-400">
                                        {d.uploaded_by} · {d.uploaded_at}
                                        {d.size_kb !== undefined && ` · ${formatSize(d.size_kb)}`}
                                    </p>
                                </div>
                                <a
                                    href={`/communication/documents/${d.id}/download`}
                                    className="inline-flex items-center gap-1.5 rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-semibold text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                                >
                                    Télécharger
                                </a>
                            </li>
                        ))}
                    </ul>
                </CardBody>
            </Card>
        </>
    );
}

function FileTypeBadge({ type }: { type: string }) {
    const colors: Record<string, string> = {
        PDF: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300',
        XLSX: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300',
        DOCX: 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300',
        PNG: 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300',
        JPG: 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300',
    };
    return (
        <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-lg font-mono text-[10px] font-bold', colors[type] ?? 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300')}>
            {type}
        </span>
    );
}

function formatSize(kb: number): string {
    if (kb < 1024) return `${kb} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
}

function QaTab({ questions }: { questions: QaQuestion[] }) {
    const [composing, setComposing] = useState(false);

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <p className="text-xs text-ink-500 dark:text-ink-400">
                    {questions.length} question{questions.length > 1 ? 's' : ''}
                </p>
                {!composing && (
                    <Button size="sm" leadingIcon={<PlusIcon />} onClick={() => setComposing(true)}>
                        Poser une question
                    </Button>
                )}
            </div>

            {composing && <QuestionComposer onClose={() => setComposing(false)} />}

            {questions.length === 0 ? (
                <Card>
                    <EmptyState
                        icon={<HelpIcon />}
                        title="Aucune question"
                        description="Posez vos questions à toute l'équipe et bénéficiez de leur expertise."
                    />
                </Card>
            ) : (
                <ul className="space-y-3">
                    {questions.map((q) => (
                        <li key={q.id}>
                            <Link href={`/communication/qa/${q.id}`} className="block">
                                <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                    <CardBody>
                                        <div className="flex gap-4">
                                            <div className="flex w-12 shrink-0 flex-col items-center gap-1 text-center">
                                                <span className="font-mono text-lg font-bold tabular-nums text-ink-900 dark:text-white">
                                                    {q.votes}
                                                </span>
                                                <span className="text-[10px] uppercase tracking-wider text-ink-400 dark:text-ink-500">
                                                    votes
                                                </span>
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{q.title}</h3>
                                                    {q.accepted && (
                                                        <Badge tone="sage" size="xs">
                                                            ✓ Réponse acceptée
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="mt-1 line-clamp-2 text-xs text-ink-600 dark:text-ink-300">{q.preview}</p>
                                                <div className="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-ink-500 dark:text-ink-400">
                                                    <span>
                                                        <span className="font-medium text-ink-700 dark:text-ink-200">{q.asker}</span> ·{' '}
                                                        {q.created_at}
                                                    </span>
                                                    <span className="font-mono">
                                                        {q.answers_count} réponse{q.answers_count > 1 ? 's' : ''}
                                                    </span>
                                                </div>
                                            </div>

                                            <span className="self-center text-sm font-medium text-brand-600 dark:text-brand-400">
                                                Voir →
                                            </span>
                                        </div>
                                    </CardBody>
                                </Card>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function QuestionComposer({ onClose }: { onClose: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        body: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/communication/qa', {
            preserveScroll: true,
            onSuccess: () => {
                reset('title', 'body');
                onClose();
            },
        });
    };

    return (
        <Card>
            <CardBody>
                <form onSubmit={submit}>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Poser une question</h3>
                            <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                Visible par toute la structure. Markdown supporté dans le corps.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-full p-1.5 text-ink-400 transition-colors hover:bg-ink-100 hover:text-ink-700 dark:text-ink-500 dark:hover:bg-ink-700"
                            aria-label="Fermer"
                        >
                            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                                <path d="M18 6L6 18M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>
                    </div>

                    <label className="mt-4 block">
                        <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                            Titre <span className="text-danger-500">*</span>
                        </span>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                            maxLength={200}
                            placeholder="Ex. « Que faire en cas de refus médicamenteux ? »"
                            className="mt-1 block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                        />
                        {errors.title && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.title}</p>}
                    </label>

                    <label className="mt-4 block">
                        <span className="block text-xs font-medium text-ink-700 dark:text-ink-300">
                            Détail <span className="text-danger-500">*</span>
                        </span>
                        <textarea
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={6}
                            required
                            maxLength={20000}
                            placeholder="Décrivez le contexte et précisez votre question…"
                            className="mt-1 block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                        />
                        {errors.body && <p className="mt-1 text-xs text-danger-600 dark:text-danger-400">{errors.body}</p>}
                    </label>

                    <div className="mt-4 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={onClose} disabled={processing}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={processing || data.title.trim() === '' || data.body.trim() === ''}>
                            {processing ? 'Publication…' : 'Poser la question'}
                        </Button>
                    </div>
                </form>
            </CardBody>
        </Card>
    );
}

function ChatIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
        </svg>
    );
}
function NewsIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 01-2 2zm0 0a2 2 0 01-2-2v-9c0-1.1.9-2 2-2h2" />
            <path d="M18 14h-8M15 18h-5M10 6h8v4h-8z" />
        </svg>
    );
}
function FileIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
        </svg>
    );
}
function HelpIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    );
}
function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
function PinIcon() {
    return (
        <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <line x1="12" y1="17" x2="12" y2="22" />
            <path d="M5 17h14V8L12 3 5 8v9z" />
        </svg>
    );
}
function UploadIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
    );
}
