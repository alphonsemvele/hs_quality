import { Badge } from './Badge';

type InterventionStatut = 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';
type IncidentStatut = 'declare' | 'en_analyse' | 'plan_actions' | 'clos';
type IncidentGravite = 'mineur' | 'significatif' | 'grave' | 'critique';
type CarePlanStatut = 'draft' | 'active' | 'archived';

const INTERVENTION_STATUT: Record<InterventionStatut, { label: string; tone: 'brand' | 'warning' | 'sage' | 'danger' | 'neutral' }> = {
    planifiee: { label: 'Planifiée', tone: 'brand' },
    en_cours: { label: 'En cours', tone: 'warning' },
    realisee: { label: 'Réalisée', tone: 'sage' },
    annulee: { label: 'Annulée', tone: 'danger' },
    non_realisee: { label: 'Non réalisée', tone: 'neutral' },
};

const INCIDENT_STATUT: Record<IncidentStatut, { label: string; tone: 'danger' | 'warning' | 'brand' | 'neutral' }> = {
    declare: { label: 'Déclaré', tone: 'danger' },
    en_analyse: { label: 'En analyse', tone: 'warning' },
    plan_actions: { label: "Plan d'actions", tone: 'brand' },
    clos: { label: 'Clos', tone: 'neutral' },
};

const INCIDENT_GRAVITE: Record<IncidentGravite, { label: string; tone: 'neutral' | 'warning' | 'danger' }> = {
    mineur: { label: 'Mineur', tone: 'neutral' },
    significatif: { label: 'Significatif', tone: 'warning' },
    grave: { label: 'Grave', tone: 'warning' },
    critique: { label: 'Critique', tone: 'danger' },
};

const CARE_PLAN_STATUT: Record<CarePlanStatut, { label: string; tone: 'neutral' | 'sage' | 'brand' }> = {
    draft: { label: 'Brouillon', tone: 'neutral' },
    active: { label: 'Actif', tone: 'sage' },
    archived: { label: 'Archivé', tone: 'neutral' },
};

export function InterventionStatusBadge({ statut }: { statut: InterventionStatut | string }) {
    const s = INTERVENTION_STATUT[statut as InterventionStatut] ?? INTERVENTION_STATUT.planifiee;
    return (
        <Badge tone={s.tone} dot>
            {s.label}
        </Badge>
    );
}

export function IncidentStatusBadge({ statut }: { statut: IncidentStatut | string }) {
    const s = INCIDENT_STATUT[statut as IncidentStatut] ?? INCIDENT_STATUT.declare;
    return <Badge tone={s.tone}>{s.label}</Badge>;
}

export function IncidentGraviteBadge({ gravite }: { gravite: IncidentGravite | string }) {
    const g = INCIDENT_GRAVITE[gravite as IncidentGravite] ?? INCIDENT_GRAVITE.mineur;
    return (
        <Badge tone={g.tone} dot>
            {g.label}
        </Badge>
    );
}

export function CarePlanStatusBadge({ statut }: { statut: CarePlanStatut | string }) {
    const s = CARE_PLAN_STATUT[statut as CarePlanStatut] ?? CARE_PLAN_STATUT.draft;
    return <Badge tone={s.tone}>{s.label}</Badge>;
}
