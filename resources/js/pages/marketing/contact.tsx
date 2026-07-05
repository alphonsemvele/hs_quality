import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, useForm, usePage } from '@inertiajs/react';
import { type FormEvent } from 'react';

interface FlashProps {
    flash?: { success?: string };
    [key: string]: unknown;
}

const STRUCTURE_TYPES: { value: string; label: string }[] = [
    { value: 'saad', label: 'SAAD — Service d\'Aide et d\'Accompagnement à Domicile' },
    { value: 'ssiad', label: 'SSIAD — Service de Soins Infirmiers à Domicile' },
    { value: 'spasad', label: 'SPASAD — Service Polyvalent d\'Aide et de Soins' },
    { value: 'esad', label: 'ESAD — Équipe Spécialisée Alzheimer à Domicile' },
    { value: 'mandataire', label: 'Mandataire / prestataire indépendant' },
    { value: 'ccas', label: 'CCAS / Collectivité' },
    { value: 'autre', label: 'Autre' },
];

const TEAM_SIZES: { value: string; label: string }[] = [
    { value: '1-10', label: '1 à 10 intervenants' },
    { value: '11-50', label: '11 à 50 intervenants' },
    { value: '51-200', label: '51 à 200 intervenants' },
    { value: '200+', label: 'Plus de 200 intervenants' },
];

export default function ContactPage() {
    const { props } = usePage<FlashProps>();
    const flash = props.flash?.success;

    const form = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        structure_name: '',
        structure_type: '',
        team_size: '',
        message: '',
        consent: false as boolean,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/contact', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <MarketingPage>
            <Head title="Contact — HS Quality" />

            <section className="border-b border-ink-100 bg-gradient-to-b from-ink-50/40 to-white px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-7xl">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Contact
                    </span>
                    <h1 className="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl">
                        Démarrons votre <span className="gradient-text">pilote 3 mois</span>.
                    </h1>
                    <p className="mt-5 max-w-2xl text-base font-light leading-relaxed text-ink-600">
                        Échangeons sur vos enjeux qualité et configurons votre environnement pilote en 48 h. Sans engagement,
                        sans carte bancaire, avec un référent qualité dédié pendant toute la durée du pilote.
                    </p>
                </div>
            </section>

            <section className="bg-white px-5 py-16 sm:px-8 sm:py-20">
                <div className="mx-auto grid max-w-7xl grid-cols-1 gap-10 lg:grid-cols-5">
                    {/* Form */}
                    <div className="lg:col-span-3">
                        {flash && (
                            <div
                                role="status"
                                className="mb-8 rounded-2xl border border-sage-200 bg-sage-50 px-5 py-4 text-sm font-medium text-sage-800"
                            >
                                {flash}
                            </div>
                        )}

                        <form onSubmit={submit} className="rounded-2xl border border-ink-100 bg-white p-6 sm:p-8">
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <Field label="Prénom" required error={form.errors.first_name}>
                                    <input
                                        type="text"
                                        autoComplete="given-name"
                                        value={form.data.first_name}
                                        onChange={(e) => form.setData('first_name', e.target.value)}
                                        className="input"
                                    />
                                </Field>
                                <Field label="Nom" required error={form.errors.last_name}>
                                    <input
                                        type="text"
                                        autoComplete="family-name"
                                        value={form.data.last_name}
                                        onChange={(e) => form.setData('last_name', e.target.value)}
                                        className="input"
                                    />
                                </Field>
                                <Field label="Email professionnel" required error={form.errors.email}>
                                    <input
                                        type="email"
                                        autoComplete="email"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        className="input"
                                    />
                                </Field>
                                <Field label="Téléphone (optionnel)" error={form.errors.phone}>
                                    <input
                                        type="tel"
                                        autoComplete="tel"
                                        value={form.data.phone}
                                        onChange={(e) => form.setData('phone', e.target.value)}
                                        className="input"
                                    />
                                </Field>
                            </div>

                            <hr className="my-7 border-ink-100" />

                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <Field label="Nom de la structure" required error={form.errors.structure_name}>
                                    <input
                                        type="text"
                                        value={form.data.structure_name}
                                        onChange={(e) => form.setData('structure_name', e.target.value)}
                                        className="input"
                                    />
                                </Field>
                                <Field label="Type de structure" required error={form.errors.structure_type}>
                                    <select
                                        value={form.data.structure_type}
                                        onChange={(e) => form.setData('structure_type', e.target.value)}
                                        className="input"
                                    >
                                        <option value="">Sélectionner…</option>
                                        {STRUCTURE_TYPES.map((s) => (
                                            <option key={s.value} value={s.value}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label="Taille de l'équipe" required error={form.errors.team_size}>
                                    <select
                                        value={form.data.team_size}
                                        onChange={(e) => form.setData('team_size', e.target.value)}
                                        className="input"
                                    >
                                        <option value="">Sélectionner…</option>
                                        {TEAM_SIZES.map((s) => (
                                            <option key={s.value} value={s.value}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                            </div>

                            <hr className="my-7 border-ink-100" />

                            <Field label="Votre message (optionnel)" error={form.errors.message}>
                                <textarea
                                    rows={5}
                                    value={form.data.message}
                                    onChange={(e) => form.setData('message', e.target.value)}
                                    placeholder="Vos enjeux qualité actuels, vos questions sur le pilote, etc."
                                    className="input"
                                />
                            </Field>

                            <div className="mt-7">
                                <label className="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="checkbox"
                                        checked={form.data.consent}
                                        onChange={(e) => form.setData('consent', e.target.checked)}
                                        className="mt-0.5 size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                    />
                                    <span className="text-xs leading-relaxed text-ink-600">
                                        J'accepte que mes données soient traitées pour me recontacter dans le cadre de ma demande.
                                        Conformément au RGPD, je peux exercer mes droits d'accès, de rectification et de suppression
                                        en consultant la <a href="/confidentialite" className="font-medium text-brand-600 hover:underline">politique de confidentialité</a>.
                                    </span>
                                </label>
                                {form.errors.consent && (
                                    <p className="mt-1 text-xs font-medium text-danger-600">{form.errors.consent}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="mt-8 w-full rounded-full bg-brand-600 px-7 py-3.5 text-sm font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {form.processing ? 'Envoi en cours…' : 'Envoyer ma demande'}
                            </button>

                            <style>{`
                                .input {
                                    width: 100%;
                                    border-radius: 10px;
                                    border: 1px solid rgb(226 232 240);
                                    background: white;
                                    padding: 10px 14px;
                                    font-size: 14px;
                                    color: rgb(15 23 42);
                                    transition: border-color .15s, box-shadow .15s;
                                }
                                .input:focus {
                                    outline: none;
                                    border-color: rgb(21 101 172);
                                    box-shadow: 0 0 0 3px rgba(21,101,172,0.12);
                                }
                            `}</style>
                        </form>
                    </div>

                    {/* Side info */}
                    <aside className="lg:col-span-2">
                        <div className="space-y-5">
                            <SideCard
                                title="Pourquoi un pilote 3 mois ?"
                                body="Trois mois suffisent pour que vos coordinateurs prennent en main la plateforme et constatent l'impact sur la traçabilité, le délai de traitement des incidents et le baromètre QVCT. Aucun engagement à l'issue."
                            />
                            <SideCard
                                title="48 h pour démarrer"
                                body="Provisioning de votre tenant HDS, import de vos bénéficiaires et intervenants, formation des coordinateurs (2 h en visio). Un référent qualité dédié vous accompagne jusqu'à la fin du pilote."
                            />
                            <SideCard
                                title="Données 100 % vôtres"
                                body="Si la solution ne correspond pas à vos besoins, vos données sont restituées au format CSV ou supprimées sur simple demande, conformément à l'article 17 du RGPD."
                            />
                            <div className="rounded-2xl bg-ink-900 p-6 text-white">
                                <h3 className="text-sm font-semibold">Préférez nous écrire ?</h3>
                                <p className="mt-2 text-xs leading-relaxed text-white/70">
                                    <a href="mailto:contact@hsquality.fr" className="text-white hover:underline">contact@hsquality.fr</a>
                                    <br />Réponse sous 24 h ouvrées.
                                </p>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </MarketingPage>
    );
}

function Field({
    label,
    required,
    error,
    children,
}: {
    label: string;
    required?: boolean;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-xs font-semibold text-ink-700">
                {label}
                {required && <span className="ml-0.5 text-danger-600">*</span>}
            </span>
            {children}
            {error && <p className="mt-1 text-xs font-medium text-danger-600">{error}</p>}
        </label>
    );
}

function SideCard({ title, body }: { title: string; body: string }) {
    return (
        <div className="rounded-2xl border border-ink-100 bg-white p-6">
            <h3 className="text-sm font-semibold text-ink-900">{title}</h3>
            <p className="mt-2 text-xs leading-relaxed text-ink-600">{body}</p>
        </div>
    );
}
