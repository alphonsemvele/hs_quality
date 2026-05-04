import { Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import DashboardLayout from '../layout';

interface Message {
    id: string;
    author: string;
    initials: string;
    content: string;
    channel: string | null;
    created_at: string;
}

interface Channel {
    id: string;
    name: string;
    nb_members: number;
    last_activity: string | null;
}

interface Document {
    id: string;
    title: string;
    type: string;
    uploaded_by: string;
    uploaded_at: string;
}

interface Props {
    messages: Message[];
    channels: Channel[];
    documents: Document[];
}

export default function CommunicationIndex({ messages = [], channels = [], documents = [] }: Partial<Props>) {
    return (
        <DashboardLayout title="Communication" subtitle="Messagerie, actualités et documents">
            <PageHeader
                title="Communication interne"
                subtitle="Messagerie d'équipe, fil d'actualité et bibliothèque documentaire"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Communication' }]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Messages / Fil d'actualité */}
                <Card className="lg:col-span-2">
                    <CardHeader title="Fil d'actualité" subtitle="Messages récents de votre structure" />
                    <CardBody>
                        {messages.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {messages.map((m) => (
                                    <li key={m.id} className="flex items-start gap-3 py-3">
                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                            {m.initials}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium text-ink-900 dark:text-white">{m.author}</span>
                                                <span className="font-mono text-[11px] text-ink-400 dark:text-ink-500">{m.created_at}</span>
                                            </div>
                                            <p className="mt-1 text-sm text-ink-700 dark:text-ink-300">{m.content}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                icon={<ChatIcon />}
                                title="Aucun message"
                                description="La messagerie temps-réel sera disponible prochainement avec les discussions d'équipe et le fil d'actualité."
                            />
                        )}
                    </CardBody>
                </Card>

                <div className="flex flex-col gap-5">
                    {/* Channels */}
                    <Card>
                        <CardHeader title="Groupes de discussion" subtitle={`${channels.length} groupe(s)`} />
                        <CardBody>
                            {channels.length > 0 ? (
                                <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                    {channels.map((c) => (
                                        <li key={c.id} className="flex items-center justify-between py-2.5">
                                            <div>
                                                <p className="text-sm font-medium text-ink-900 dark:text-white"># {c.name}</p>
                                                <p className="text-xs text-ink-500 dark:text-ink-400">{c.nb_members} membre(s)</p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState title="Aucun groupe" description="Les groupes de discussion seront configurés par votre coordinateur." />
                            )}
                        </CardBody>
                    </Card>

                    {/* Documents */}
                    <Card>
                        <CardHeader title="Bibliothèque documentaire" subtitle={`${documents.length} document(s)`} />
                        <CardBody>
                            {documents.length > 0 ? (
                                <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                    {documents.map((d) => (
                                        <li key={d.id} className="flex items-center gap-3 py-2.5">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-50 text-ink-500 dark:bg-ink-700 dark:text-ink-400">
                                                <FileIcon />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{d.title}</p>
                                                <p className="text-xs text-ink-500 dark:text-ink-400">{d.uploaded_by} · {d.uploaded_at}</p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState title="Aucun document" description="Partagez les protocoles, procédures et documents de référence ici." />
                            )}
                        </CardBody>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}

function ChatIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
        </svg>
    );
}

function FileIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
        </svg>
    );
}
