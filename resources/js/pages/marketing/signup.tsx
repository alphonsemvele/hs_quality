import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const STRUCTURE_TYPES = [
    { value: 'SAAD', label: 'SAAD — Service d\'Aide et d\'Accompagnement à Domicile' },
    { value: 'SSIAD', label: 'SSIAD — Service de Soins Infirmiers à Domicile' },
    { value: 'SPASAD', label: 'SPASAD — Service Polyvalent d\'Aide et de Soins à Domicile' },
    { value: 'ESAD', label: 'ESAD — Équipe Spécialisée Alzheimer à Domicile' },
    { value: 'CCAS', label: 'CCAS — Centre Communal d\'Action Sociale' },
];

export default function Signup() {
    const form = useForm({
        structure_name: '',
        structure_type: '',
        siret: '',
        address: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        accept_cgu: false as boolean,
        // Honeypot
        website: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post('/inscription', {
            preserveScroll: true,
        });
    };

    return (
        <MarketingPage>
            <Head title="Créer mon espace — HS Quality" />

            <section className="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-20">
                <header className="text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Essai gratuit 30 jours · Aucun engagement
                    </span>
                    <h1 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                        Créer mon espace QualitéDomicile
                    </h1>
                    <p className="mt-3 text-sm text-ink-600">
                        Renseignez les informations de votre structure et de son dirigeant. Vous recevrez par
                        email un lien pour choisir votre mot de passe et accéder à votre espace.
                    </p>
                </header>

                <form
                    onSubmit={submit}
                    className="mt-10 space-y-8 rounded-2xl border border-ink-100 bg-white p-6 shadow-sm sm:p-8"
                    noValidate
                >
                    {/* Honeypot — visually hidden, must stay empty. */}
                    <div className="hidden" aria-hidden="true">
                        <label>
                            Ne remplissez pas ce champ
                            <input
                                type="text"
                                tabIndex={-1}
                                autoComplete="off"
                                value={form.data.website}
                                onChange={(e) => form.setData('website', e.target.value)}
                            />
                        </label>
                    </div>

                    <Section title="Votre structure">
                        <Field
                            label="Nom de la structure"
                            name="structure_name"
                            required
                            error={form.errors.structure_name}
                        >
                            <input
                                id="structure_name"
                                type="text"
                                autoComplete="organization"
                                required
                                value={form.data.structure_name}
                                onChange={(e) => form.setData('structure_name', e.target.value)}
                                className={inputClasses}
                            />
                        </Field>

                        <Field
                            label="Type de structure"
                            name="structure_type"
                            required
                            error={form.errors.structure_type}
                        >
                            <select
                                id="structure_type"
                                required
                                value={form.data.structure_type}
                                onChange={(e) => form.setData('structure_type', e.target.value)}
                                className={inputClasses}
                            >
                                <option value="">— Sélectionner —</option>
                                {STRUCTURE_TYPES.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field
                                label="SIRET"
                                name="siret"
                                hint="14 chiffres"
                                required
                                error={form.errors.siret}
                            >
                                <input
                                    id="siret"
                                    inputMode="numeric"
                                    pattern="\d{14}"
                                    required
                                    value={form.data.siret}
                                    onChange={(e) => form.setData('siret', e.target.value.replace(/\D/g, '').slice(0, 14))}
                                    className={`${inputClasses} font-mono`}
                                />
                            </Field>

                            <Field
                                label="Adresse"
                                name="address"
                                hint="Optionnel — vous pourrez la compléter plus tard"
                                error={form.errors.address}
                            >
                                <input
                                    id="address"
                                    type="text"
                                    autoComplete="street-address"
                                    value={form.data.address}
                                    onChange={(e) => form.setData('address', e.target.value)}
                                    className={inputClasses}
                                />
                            </Field>
                        </div>
                    </Section>

                    <Section title="Votre profil dirigeant">
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field
                                label="Prénom"
                                name="first_name"
                                required
                                error={form.errors.first_name}
                            >
                                <input
                                    id="first_name"
                                    type="text"
                                    autoComplete="given-name"
                                    required
                                    value={form.data.first_name}
                                    onChange={(e) => form.setData('first_name', e.target.value)}
                                    className={inputClasses}
                                />
                            </Field>

                            <Field
                                label="Nom"
                                name="last_name"
                                required
                                error={form.errors.last_name}
                            >
                                <input
                                    id="last_name"
                                    type="text"
                                    autoComplete="family-name"
                                    required
                                    value={form.data.last_name}
                                    onChange={(e) => form.setData('last_name', e.target.value)}
                                    className={inputClasses}
                                />
                            </Field>
                        </div>

                        <Field
                            label="Email professionnel"
                            name="email"
                            required
                            error={form.errors.email}
                        >
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                required
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                className={inputClasses}
                            />
                        </Field>

                        <Field
                            label="Téléphone"
                            name="phone"
                            hint="Optionnel"
                            error={form.errors.phone}
                        >
                            <input
                                id="phone"
                                type="tel"
                                autoComplete="tel"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                                className={inputClasses}
                            />
                        </Field>
                    </Section>

                    <div className="rounded-xl border border-ink-100 bg-ink-50/40 p-4">
                        <label className="flex items-start gap-3 text-sm leading-relaxed text-ink-700">
                            <input
                                type="checkbox"
                                checked={form.data.accept_cgu}
                                onChange={(e) => form.setData('accept_cgu', e.target.checked)}
                                className="mt-0.5 size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                required
                            />
                            <span>
                                J'ai lu et j'accepte les{' '}
                                <Link href="/cgu" className="font-medium text-brand-700 underline">
                                    conditions générales d'utilisation
                                </Link>{' '}
                                et la{' '}
                                <Link href="/confidentialite" className="font-medium text-brand-700 underline">
                                    politique de confidentialité
                                </Link>
                                .
                            </span>
                        </label>
                        {form.errors.accept_cgu && (
                            <p className="mt-2 text-xs font-medium text-danger-600">{form.errors.accept_cgu}</p>
                        )}
                    </div>

                    <div className="flex flex-col items-center gap-3 text-center">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex h-12 items-center justify-center rounded-full bg-brand-600 px-8 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {form.processing ? 'Création en cours…' : 'Créer mon espace gratuit'}
                        </button>
                        <p className="text-[11px] text-ink-500">
                            Vous recevrez un email avec un lien pour choisir votre mot de passe.
                        </p>
                    </div>
                </form>

                <p className="mt-6 text-center text-xs text-ink-500">
                    Vous êtes déjà client ?{' '}
                    <Link href="/login" className="font-medium text-brand-700 underline">
                        Connectez-vous
                    </Link>
                </p>
            </section>
        </MarketingPage>
    );
}

const inputClasses =
    'mt-1.5 w-full rounded-lg border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20';

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <fieldset className="space-y-5">
            <legend className="text-xs font-semibold uppercase tracking-wider text-ink-500">{title}</legend>
            {children}
        </fieldset>
    );
}

function Field({
    label,
    name,
    hint,
    required,
    error,
    children,
}: {
    label: string;
    name: string;
    hint?: string;
    required?: boolean;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <label htmlFor={name} className="block text-xs font-semibold uppercase tracking-wider text-ink-600">
                {label}
                {required && <span className="ml-1 text-danger-600">*</span>}
            </label>
            {children}
            {hint && !error && <p className="mt-1 text-[11px] text-ink-500">{hint}</p>}
            {error && <p className="mt-1 text-xs font-medium text-danger-600">{error}</p>}
        </div>
    );
}
