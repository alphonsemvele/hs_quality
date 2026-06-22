import { Button, Card, CardBody, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../layout';

export default function AuditGridCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/audits/grids');
    };

    return (
        <DashboardLayout title="Nouveau référentiel" subtitle="Audit grid creation">
            <PageHeader
                title="Créer un référentiel interne"
                subtitle="Définissez un référentiel propre à votre structure, en complément des grilles HAS, ISO 9001 et AFNOR."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Référentiels', href: '/audits/grids' },
                    { label: 'Nouveau' },
                ]}
            />

            <form onSubmit={submit} className="max-w-2xl">
                <Card>
                    <CardBody className="space-y-5">
                        <FormField label="Nom du référentiel" required error={errors.title}>
                            <Input
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder="Ex. Référentiel interne — accompagnement bénéficiaire"
                                maxLength={255}
                                required
                                autoFocus
                            />
                        </FormField>

                        <FormField
                            label="Description"
                            help="Précisez le périmètre couvert, la source méthodologique, la version."
                            error={errors.description}
                        >
                            <Textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={5}
                                maxLength={5000}
                            />
                        </FormField>

                        <div className="rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                            <p className="font-semibold text-brand-900 dark:text-brand-200">Prochaine étape</p>
                            <p className="mt-1 text-brand-900/80 dark:text-brand-200/80">
                                Après création, vous pourrez consulter la fiche du référentiel. L'ajout des axes et des
                                critères d'évaluation est conduit par l'équipe qualité — contactez le support pour un
                                import en lot ou une configuration assistée.
                            </p>
                        </div>
                    </CardBody>
                </Card>

                <div className="mt-5 flex items-center justify-end gap-2">
                    <Link href="/audits/grids">
                        <Button type="button" variant="ghost">
                            Annuler
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing || data.title.trim().length === 0}>
                        {processing ? 'Création…' : 'Créer le référentiel'}
                    </Button>
                </div>
            </form>
        </DashboardLayout>
    );
}
