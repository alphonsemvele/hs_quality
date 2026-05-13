import { Badge, Button, Card, CardBody, CardHeader, ConfirmDialog, EmptyState, FormField, Input, Modal, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Token {
    id: string;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
}

interface NewTokenResult {
    plain_token: string;
    record: Token;
}

const AVAILABLE_ABILITIES = [
    { value: 'interventions:read', label: 'Lire les interventions' },
    { value: 'beneficiaries:read', label: 'Lire les bénéficiaires' },
    { value: 'incidents:read', label: 'Lire les incidents' },
    { value: 'incidents:write', label: 'Déclarer des incidents' },
    { value: 'audits:read', label: 'Lire les audits' },
];

export default function ApiTokens() {
    // Demo data — the real backend wiring is /sanctum/personal-access-tokens (Sanctum default).
    const [tokens, setTokens] = useState<Token[]>([
        {
            id: 't-1',
            name: 'Intégration Notion',
            abilities: ['interventions:read', 'beneficiaries:read'],
            last_used_at: '2026-05-10T08:32:00Z',
            created_at: '2026-04-15T10:00:00Z',
        },
    ]);
    const [showCreate, setShowCreate] = useState(false);
    const [newName, setNewName] = useState('');
    const [selectedAbilities, setSelectedAbilities] = useState<string[]>([]);
    const [revokeTarget, setRevokeTarget] = useState<Token | null>(null);
    const [createdToken, setCreatedToken] = useState<NewTokenResult | null>(null);
    const [copied, setCopied] = useState(false);

    const toggleAbility = (a: string) => {
        setSelectedAbilities((prev) => (prev.includes(a) ? prev.filter((x) => x !== a) : [...prev, a]));
    };

    const createToken = () => {
        if (!newName.trim()) return;
        // Mock token plain value — replace with real POST /api/tokens on backend wire-up.
        const plain = 'hsq_' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
        const record: Token = {
            id: `t-${Date.now().toString(36)}`,
            name: newName.trim(),
            abilities: selectedAbilities.length > 0 ? selectedAbilities : ['*'],
            last_used_at: null,
            created_at: new Date().toISOString(),
        };
        setTokens((prev) => [record, ...prev]);
        setCreatedToken({ plain_token: plain, record });
        setShowCreate(false);
        setNewName('');
        setSelectedAbilities([]);
    };

    const revoke = () => {
        if (!revokeTarget) return;
        setTokens((prev) => prev.filter((t) => t.id !== revokeTarget.id));
        setRevokeTarget(null);
    };

    const copyToken = async () => {
        if (!createdToken) return;
        try {
            await navigator.clipboard.writeText(createdToken.plain_token);
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        } catch {
            // ignore
        }
    };

    return (
        <DashboardLayout title="Tokens d'API" subtitle="Sanctum personal access tokens">
            <PageHeader
                title="Tokens d'API personnels"
                subtitle="Pour les intégrations tierces (Zapier, scripts, mobile compagnon). Chaque token est lié à votre compte."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Mon profil', href: '/dashboard/profile' },
                    { label: 'Tokens API' },
                ]}
                actions={<Button onClick={() => setShowCreate(true)}>+ Nouveau token</Button>}
            />

            <div className="mb-5 rounded-xl border border-warning-200 bg-warning-50/40 p-4 text-xs dark:border-warning-700/40 dark:bg-warning-900/20">
                <p className="font-semibold text-warning-900 dark:text-warning-100">⚠ Sécurité</p>
                <p className="mt-1 text-warning-900/80 dark:text-warning-200/80">
                    Un token donne accès à <strong>vos</strong> données depuis votre rôle. Ne le partagez jamais. En cas
                    de fuite, révoquez-le immédiatement. Les tokens ne sont visibles qu'à la création — gardez-les en
                    lieu sûr (gestionnaire de mots de passe).
                </p>
            </div>

            <Card>
                <CardHeader title="Tokens actifs" subtitle={`${tokens.length} token${tokens.length > 1 ? 's' : ''}`} />
                <CardBody className="px-0">
                    {tokens.length > 0 ? (
                        <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                            {tokens.map((t) => (
                                <li key={t.id} className="flex items-start gap-3 px-5 py-3">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300">
                                        <KeyIcon />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium text-ink-900 dark:text-white">{t.name}</p>
                                        <p className="font-mono text-[11px] text-ink-500 dark:text-ink-400">
                                            Créé le {new Date(t.created_at).toLocaleDateString('fr-FR')}
                                            {t.last_used_at
                                                ? ` · Dernière utilisation ${new Date(t.last_used_at).toLocaleDateString('fr-FR')}`
                                                : ' · Jamais utilisé'}
                                        </p>
                                        <div className="mt-1.5 flex flex-wrap gap-1">
                                            {t.abilities.map((a) => (
                                                <Badge key={a} tone="neutral" size="xs">
                                                    {a}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="sm" onClick={() => setRevokeTarget(t)}>
                                        Révoquer
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState icon={<KeyIcon />} title="Aucun token actif" description="Créez un token pour utiliser l'API HS Quality depuis vos scripts ou intégrations." />
                    )}
                </CardBody>
            </Card>

            {/* Create modal */}
            <Modal
                open={showCreate}
                onClose={() => setShowCreate(false)}
                title="Nouveau token d'API"
                size="md"
                footer={
                    <>
                        <Button variant="ghost" onClick={() => setShowCreate(false)}>Annuler</Button>
                        <Button onClick={createToken} disabled={!newName.trim()}>
                            Créer le token
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <FormField label="Nom" htmlFor="token-name" required help="Donnez un nom explicite (ex: « Intégration Notion », « Script export mensuel »)">
                        <Input
                            id="token-name"
                            value={newName}
                            onChange={(e) => setNewName(e.target.value)}
                            maxLength={64}
                            placeholder="Intégration X"
                        />
                    </FormField>

                    <div>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            Permissions (vide = toutes vos permissions)
                        </p>
                        <div className="space-y-1.5">
                            {AVAILABLE_ABILITIES.map((a) => (
                                <label key={a.value} className="flex cursor-pointer items-center gap-2 rounded-lg border border-ink-100 px-3 py-2 hover:bg-ink-50 dark:border-ink-700 dark:hover:bg-ink-700/40">
                                    <input
                                        type="checkbox"
                                        checked={selectedAbilities.includes(a.value)}
                                        onChange={() => toggleAbility(a.value)}
                                        className="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-400 dark:border-ink-600 dark:bg-ink-800"
                                    />
                                    <code className="font-mono text-xs text-ink-700 dark:text-ink-200">{a.value}</code>
                                    <span className="text-xs text-ink-500 dark:text-ink-400">— {a.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                </div>
            </Modal>

            {/* Show created token */}
            <Modal
                open={createdToken !== null}
                onClose={() => { setCreatedToken(null); setCopied(false); }}
                title="Token créé"
                size="md"
                iconTone="sage"
                icon={<KeyIcon />}
                footer={
                    <Button onClick={() => { setCreatedToken(null); setCopied(false); }}>
                        J'ai sauvegardé mon token
                    </Button>
                }
            >
                {createdToken && (
                    <div className="space-y-3">
                        <p className="text-sm text-ink-700 dark:text-ink-200">
                            <strong className="font-semibold">Ce token ne sera plus jamais affiché.</strong> Copiez-le et stockez-le dans
                            votre gestionnaire de mots de passe maintenant.
                        </p>
                        <div className="flex items-center gap-2 rounded-lg border border-ink-200 bg-ink-50 p-2.5 dark:border-ink-700 dark:bg-ink-900/60">
                            <code className="flex-1 select-all break-all font-mono text-xs text-ink-900 dark:text-white">
                                {createdToken.plain_token}
                            </code>
                            <Button variant="secondary" size="sm" onClick={copyToken}>
                                {copied ? '✓ Copié' : 'Copier'}
                            </Button>
                        </div>
                    </div>
                )}
            </Modal>

            <ConfirmDialog
                open={revokeTarget !== null}
                onClose={() => setRevokeTarget(null)}
                onConfirm={revoke}
                title={`Révoquer le token « ${revokeTarget?.name} » ?`}
                description="Les intégrations utilisant ce token cesseront immédiatement de fonctionner. Cette action est irréversible."
                confirmLabel="Révoquer"
                tone="danger"
            />
        </DashboardLayout>
    );
}

function KeyIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />
        </svg>
    );
}
