import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from './layout';

// ─── Types ────────────────────────────────────────────────────────────────────

interface UserInfo {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    initials: string;
}

interface Props {
    user: UserInfo;
    success?:          string;
    success_password?: string;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const inputCls = (err?: string) =>
    `w-full rounded-xl border px-4 py-2.5 text-sm text-[#0f172a] transition-all focus:outline-none focus:ring-2 ${
        err
            ? 'border-red-300 focus:border-red-400 focus:ring-red-50'
            : 'border-slate-200 focus:border-[#2563eb] focus:ring-blue-50'
    }`;

const labelCls = 'block text-sm font-medium text-[#0f172a] mb-1.5';

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Profile({ user, success, success_password }: Props) {
    const [showDelete, setShowDelete] = useState(false);

    // ── Formulaire infos ─────────────────────────────────────────────────────
    const infoForm = useForm({
        name:  user.name,
        email: user.email,
    });

    const handleInfoSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        infoForm.patch('/profile');
    };

    // ── Formulaire mot de passe ──────────────────────────────────────────────
    const passForm = useForm({
        current_password:      '',
        password:              '',
        password_confirmation: '',
    });

    const handlePassSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        passForm.patch('/profile/password', {
            onSuccess: () => passForm.reset(),
        });
    };

    // ── Formulaire suppression ────────────────────────────────────────────────
    const deleteForm = useForm({ password: '' });

    const handleDelete = (e: React.FormEvent) => {
        e.preventDefault();
        deleteForm.delete('/profile');
    };

    return (
        <DashboardLayout title="Mon profil" subtitle="Gérez vos informations personnelles et paramètres de sécurité">
            <Head title="Profil — STAT ENQUETE" />

            {/* ── Flash succès ────────────────────────────────────────────── */}
            {success && (
                <div className="mb-5 flex items-center gap-3 px-5 py-3.5 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-medium">
                    <svg className="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7"/>
                    </svg>
                    {success}
                </div>
            )}
            {success_password && (
                <div className="mb-5 flex items-center gap-3 px-5 py-3.5 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-medium">
                    <svg className="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7"/>
                    </svg>
                    {success_password}
                </div>
            )}

            <div className="max-w-2xl space-y-5">

                {/* ── Carte avatar + résumé ────────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-slate-100 p-6">
                    <div className="flex items-center gap-5">
                        {/* Avatar initiales */}
                        <div className="w-20 h-20 rounded-2xl bg-[#2563eb] flex items-center justify-center text-white text-2xl font-extrabold flex-shrink-0 shadow-sm">
                            {user.initials}
                        </div>
                        <div className="flex-1 min-w-0">
                            <h2 className="text-xl font-bold text-[#0f172a] truncate">{user.name}</h2>
                            <p className="text-sm text-slate-400 truncate">{user.email}</p>
                            <div className="flex items-center gap-2 mt-2">
                                <span className="text-xs font-semibold px-2.5 py-1 bg-blue-50 text-[#2563eb] border border-blue-100 rounded-full capitalize">
                                    {user.role}
                                </span>
                                <span className="text-xs text-slate-400">
                                    Membre depuis le {user.created_at}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ── Informations personnelles ────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-100">
                        <h3 className="text-sm font-bold text-[#0f172a]">Informations personnelles</h3>
                        <p className="text-xs text-slate-400 mt-0.5">Mettez à jour votre nom et votre adresse email</p>
                    </div>
                    <form onSubmit={handleInfoSubmit} className="p-6 space-y-4">
                        <div>
                            <label className={labelCls}>Nom complet</label>
                            <input
                                type="text"
                                value={infoForm.data.name}
                                onChange={e => infoForm.setData('name', e.target.value)}
                                className={inputCls(infoForm.errors.name)}
                                placeholder="Jean "
                            />
                            {infoForm.errors.name && (
                                <p className="mt-1.5 text-xs text-red-500">{infoForm.errors.name}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelCls}>Adresse email</label>
                            <input
                                type="email"
                                value={infoForm.data.email}
                                onChange={e => infoForm.setData('email', e.target.value)}
                                className={inputCls(infoForm.errors.email)}
                                placeholder="vous@exemple.com"
                            />
                            {infoForm.errors.email && (
                                <p className="mt-1.5 text-xs text-red-500">{infoForm.errors.email}</p>
                            )}
                        </div>
                        <div className="flex justify-end pt-2">
                            <button type="submit" disabled={infoForm.processing}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#2563eb] text-white text-sm font-semibold hover:bg-[#1d4ed8] disabled:opacity-50 transition-all">
                                {infoForm.processing ? <Spin /> : (
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7"/>
                                    </svg>
                                )}
                                {infoForm.processing ? 'Enregistrement…' : 'Enregistrer'}
                            </button>
                        </div>
                    </form>
                </div>

                {/* ── Sécurité / Mot de passe ──────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-100">
                        <h3 className="text-sm font-bold text-[#0f172a]">Sécurité</h3>
                        <p className="text-xs text-slate-400 mt-0.5">Changez votre mot de passe régulièrement pour protéger votre compte</p>
                    </div>
                    <form onSubmit={handlePassSubmit} className="p-6 space-y-4">
                        <div>
                            <label className={labelCls}>Mot de passe actuel</label>
                            <PasswordInput
                                value={passForm.data.current_password}
                                onChange={v => passForm.setData('current_password', v)}
                                error={passForm.errors.current_password}
                                placeholder="Votre mot de passe actuel"
                            />
                            {passForm.errors.current_password && (
                                <p className="mt-1.5 text-xs text-red-500">{passForm.errors.current_password}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelCls}>Nouveau mot de passe</label>
                            <PasswordInput
                                value={passForm.data.password}
                                onChange={v => passForm.setData('password', v)}
                                error={passForm.errors.password}
                                placeholder="Minimum 8 caractères"
                            />
                            {passForm.errors.password && (
                                <p className="mt-1.5 text-xs text-red-500">{passForm.errors.password}</p>
                            )}
                        </div>
                        <div>
                            <label className={labelCls}>Confirmer le nouveau mot de passe</label>
                            <PasswordInput
                                value={passForm.data.password_confirmation}
                                onChange={v => passForm.setData('password_confirmation', v)}
                                error={passForm.errors.password_confirmation}
                                placeholder="Répétez le nouveau mot de passe"
                            />
                            {passForm.errors.password_confirmation && (
                                <p className="mt-1.5 text-xs text-red-500">{passForm.errors.password_confirmation}</p>
                            )}
                        </div>

                        {/* Indicateur de force */}
                        {passForm.data.password.length > 0 && (
                            <PasswordStrength password={passForm.data.password} />
                        )}

                        <div className="flex justify-end pt-2">
                            <button type="submit" disabled={passForm.processing}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#2563eb] text-white text-sm font-semibold hover:bg-[#1d4ed8] disabled:opacity-50 transition-all">
                                {passForm.processing ? <Spin /> : (
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                )}
                                {passForm.processing ? 'Modification…' : 'Modifier le mot de passe'}
                            </button>
                        </div>
                    </form>
                </div>

                {/* ── Zone de danger ───────────────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-red-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-red-100 bg-red-50">
                        <h3 className="text-sm font-bold text-red-700">Zone de danger</h3>
                        <p className="text-xs text-red-500 mt-0.5">Ces actions sont irréversibles</p>
                    </div>
                    <div className="p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-sm font-semibold text-[#0f172a]">Supprimer mon compte</p>
                                <p className="text-xs text-slate-400 mt-0.5 max-w-sm">
                                    La suppression de votre compte supprimera définitivement toutes vos enquêtes, questions et réponses collectées.
                                </p>
                            </div>
                            <button onClick={() => setShowDelete(true)}
                                className="flex-shrink-0 px-4 py-2.5 rounded-xl border-2 border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50 transition-colors">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Modal confirmation suppression ──────────────────────────── */}
            {showDelete && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div className="px-6 py-5 border-b border-slate-100">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="font-bold text-[#0f172a]">Supprimer le compte</h3>
                                    <p className="text-xs text-slate-400">Cette action est irréversible</p>
                                </div>
                            </div>
                        </div>
                        <form onSubmit={handleDelete} className="p-6 space-y-4">
                            <p className="text-sm text-slate-600 leading-relaxed">
                                Toutes vos enquêtes et données seront supprimées. Confirmez avec votre mot de passe pour continuer.
                            </p>
                            <div>
                                <label className={labelCls}>Mot de passe</label>
                                <PasswordInput
                                    value={deleteForm.data.password}
                                    onChange={v => deleteForm.setData('password', v)}
                                    error={deleteForm.errors.password}
                                    placeholder="Votre mot de passe"
                                />
                                {deleteForm.errors.password && (
                                    <p className="mt-1.5 text-xs text-red-500">{deleteForm.errors.password}</p>
                                )}
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button type="button" onClick={() => setShowDelete(false)}
                                    className="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                                    Annuler
                                </button>
                                <button type="submit" disabled={deleteForm.processing}
                                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-500 text-white text-sm font-semibold hover:bg-red-600 disabled:opacity-50 transition-colors">
                                    {deleteForm.processing ? <Spin white /> : (
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    )}
                                    {deleteForm.processing ? 'Suppression…' : 'Supprimer définitivement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}

// ─── PasswordInput ────────────────────────────────────────────────────────────

function PasswordInput({ value, onChange, error, placeholder }: {
    value: string;
    onChange: (v: string) => void;
    error?: string;
    placeholder?: string;
}) {
    const [show, setShow] = useState(false);
    return (
        <div className="relative">
            <input
                type={show ? 'text' : 'password'}
                value={value}
                onChange={e => onChange(e.target.value)}
                placeholder={placeholder}
                className={inputCls(error) + ' pr-12'}
            />
            <button type="button" onClick={() => setShow(s => !s)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                {show ? (
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                ) : (
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                )}
            </button>
        </div>
    );
}

// ─── PasswordStrength ─────────────────────────────────────────────────────────

function PasswordStrength({ password }: { password: string }) {
    const checks = [
        { label: '8 caractères min.',   ok: password.length >= 8 },
        { label: 'Une lettre',          ok: /[a-zA-Z]/.test(password) },
        { label: 'Un chiffre',          ok: /[0-9]/.test(password) },
        { label: 'Caractère spécial',   ok: /[^a-zA-Z0-9]/.test(password) },
    ];
    const score = checks.filter(c => c.ok).length;
    const levels = [
        { label: 'Très faible', color: 'bg-red-400'    },
        { label: 'Faible',      color: 'bg-orange-400' },
        { label: 'Moyen',       color: 'bg-amber-400'  },
        { label: 'Fort',        color: 'bg-green-400'  },
        { label: 'Très fort',   color: 'bg-green-600'  },
    ];
    const level = levels[score] ?? levels[0];

    return (
        <div className="space-y-2.5 p-4 bg-slate-50 rounded-xl border border-slate-100">
            {/* Barre de force */}
            <div className="flex items-center gap-2">
                <div className="flex-1 grid grid-cols-4 gap-1">
                    {[0,1,2,3].map(i => (
                        <div key={i} className={`h-1.5 rounded-full transition-all ${i < score ? level.color : 'bg-slate-200'}`} />
                    ))}
                </div>
                <span className={`text-xs font-semibold ${
                    score <= 1 ? 'text-red-500' :
                    score === 2 ? 'text-amber-500' :
                    'text-green-600'
                }`}>{level.label}</span>
            </div>
            {/* Checklist */}
            <div className="grid grid-cols-2 gap-1.5">
                {checks.map(c => (
                    <div key={c.label} className="flex items-center gap-1.5">
                        <div className={`w-3.5 h-3.5 rounded-full flex items-center justify-center flex-shrink-0 ${c.ok ? 'bg-green-500' : 'bg-slate-200'}`}>
                            {c.ok && <svg className="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7"/></svg>}
                        </div>
                        <span className={`text-xs ${c.ok ? 'text-green-600 font-medium' : 'text-slate-400'}`}>{c.label}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ─── Spinner ──────────────────────────────────────────────────────────────────

function Spin({ white = false }: { white?: boolean }) {
    return (
        <svg className={`w-4 h-4 animate-spin ${white ? 'text-white' : 'text-current'}`} fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
    );
}