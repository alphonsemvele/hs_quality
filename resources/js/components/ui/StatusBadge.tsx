import { Badge } from './Badge';
import { Tooltip } from './Tooltip';

type InterventionStatut = 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';
type IncidentStatut = 'declare' | 'en_analyse' | 'plan_actions' | 'clos';
type IncidentGravite = 'mineur' | 'significatif' | 'grave' | 'critique';
type CarePlanStatut = 'draft' | 'active' | 'archived';

const INTERVENTION_STATUT: Record<InterventionStatut, { label: string; tone: 'brand' | 'warning' | 'sage' | 'danger' | 'neutral'; hint: string }> = {
    planifiee: {
        label: 'Planifiée',
        tone: 'brand',
        hint: "Intervention inscrite au planning, en attente de check-in par l'intervenant.",
    },
    en_cours: {
        label: 'En cours',
        tone: 'warning',
        hint: "L'intervenant a effectué son check-in (position GPS enregistrée si disponible). Le check-out clôturera la visite.",
    },
    realisee: {
        label: 'Réalisée',
        tone: 'sage',
        hint: 'Check-out effectué et compte-rendu transmis. La durée réelle est calculée à partir des horodatages.',
    },
    annulee: {
        label: 'Annulée',
        tone: 'danger',
        hint: "Visite annulée avec motif obligatoire (hospitalisation, refus, indisponibilité…). Trace conservée pour l'audit.",
    },
    non_realisee: {
        label: 'Non réalisée',
        tone: 'neutral',
        hint: "Aucun check-in n'a été effectué à l'heure prévue. Le bénéficiaire et le coordinateur sont notifiés.",
    },
};

const INCIDENT_STATUT: Record<IncidentStatut, { label: string; tone: 'danger' | 'warning' | 'brand' | 'neutral'; hint: string }> = {
    declare: {
        label: 'Déclaré',
        tone: 'danger',
        hint: "Incident enregistré. Le responsable de secteur reçoit une notification immédiate. Si gravité « grave » ou « critique », l'ARS est saisie sous 48 h.",
    },
    en_analyse: {
        label: 'En analyse',
        tone: 'warning',
        hint: "Analyse en cours (méthode 5-pourquoi). L'objectif est d'identifier les causes racines avant d'élaborer un plan d'actions.",
    },
    plan_actions: {
        label: "Plan d'actions",
        tone: 'brand',
        hint: "Actions correctives définies (CAPA). Chaque action est suivie jusqu'à son échéance.",
    },
    clos: {
        label: 'Clos',
        tone: 'neutral',
        hint: 'Toutes les actions correctives sont validées et leur efficacité contrôlée. Le dossier est archivé.',
    },
};

const INCIDENT_GRAVITE: Record<IncidentGravite, { label: string; tone: 'neutral' | 'warning' | 'danger'; hint: string }> = {
    mineur: {
        label: 'Mineur',
        tone: 'neutral',
        hint: "Aucun préjudice physique constaté. Géré en interne, sans saisine de l'ARS.",
    },
    significatif: {
        label: 'Significatif',
        tone: 'warning',
        hint: "Blessure physique légère ou agression. Plan d'actions correctives obligatoire ; ARS non notifiée.",
    },
    grave: {
        label: 'Grave',
        tone: 'warning',
        hint: 'Hospitalisation ou erreur médicamenteuse. Notification ARS automatique sous 48 h (Article L1413-14 CSP).',
    },
    critique: {
        label: 'Critique',
        tone: 'danger',
        hint: 'Décès, maltraitance suspectée ou situation de danger. Notification ARS immédiate + déclaration CRIP si nécessaire.',
    },
};

const CARE_PLAN_STATUT: Record<CarePlanStatut, { label: string; tone: 'neutral' | 'sage' | 'brand'; hint: string }> = {
    draft: {
        label: 'Brouillon',
        tone: 'neutral',
        hint: "Plan de soins en cours de rédaction. Aucune intervention ne peut être planifiée tant que le plan n'est pas activé.",
    },
    active: {
        label: 'Actif',
        tone: 'sage',
        hint: 'Plan en vigueur. Les tâches planifiées générées à partir de ce plan apparaissent dans le planning des intervenants.',
    },
    archived: {
        label: 'Archivé',
        tone: 'neutral',
        hint: 'Plan retiré du cycle actif. Lecture seule — toute modification doit passer par une copie en brouillon.',
    },
};

export function InterventionStatusBadge({ statut, hideHint = false }: { statut: InterventionStatut | string; hideHint?: boolean }) {
    const s = INTERVENTION_STATUT[statut as InterventionStatut] ?? INTERVENTION_STATUT.planifiee;
    const badge = (
        <Badge tone={s.tone} dot>
            {s.label}
        </Badge>
    );
    if (hideHint) {
        return badge;
    }
    return <Tooltip content={s.hint}>{badge}</Tooltip>;
}

export function IncidentStatusBadge({ statut, hideHint = false }: { statut: IncidentStatut | string; hideHint?: boolean }) {
    const s = INCIDENT_STATUT[statut as IncidentStatut] ?? INCIDENT_STATUT.declare;
    const badge = <Badge tone={s.tone}>{s.label}</Badge>;
    if (hideHint) {
        return badge;
    }
    return <Tooltip content={s.hint}>{badge}</Tooltip>;
}

export function IncidentGraviteBadge({ gravite, hideHint = false }: { gravite: IncidentGravite | string; hideHint?: boolean }) {
    const g = INCIDENT_GRAVITE[gravite as IncidentGravite] ?? INCIDENT_GRAVITE.mineur;
    const badge = (
        <Badge tone={g.tone} dot>
            {g.label}
        </Badge>
    );
    if (hideHint) {
        return badge;
    }
    return <Tooltip content={g.hint}>{badge}</Tooltip>;
}

export function CarePlanStatusBadge({ statut, hideHint = false }: { statut: CarePlanStatut | string; hideHint?: boolean }) {
    const s = CARE_PLAN_STATUT[statut as CarePlanStatut] ?? CARE_PLAN_STATUT.draft;
    const badge = <Badge tone={s.tone}>{s.label}</Badge>;
    if (hideHint) {
        return badge;
    }
    return <Tooltip content={s.hint}>{badge}</Tooltip>;
}

const GIR_HINTS: Record<number, { tone: 'danger' | 'warning' | 'sage'; hint: string }> = {
    1: {
        tone: 'danger',
        hint: "GIR 1 — Personne confinée au lit ou au fauteuil, fonctions mentales gravement altérées. Présence continue d'intervenants requise.",
    },
    2: {
        tone: 'danger',
        hint: 'GIR 2 — Confinée au lit/fauteuil avec fonctions mentales conservées, OU détérioration mentale avec capacités motrices préservées.',
    },
    3: {
        tone: 'warning',
        hint: "GIR 3 — Autonomie mentale partielle, mais aide pluriquotidienne nécessaire pour la toilette, l'habillage et les déplacements.",
    },
    4: {
        tone: 'warning',
        hint: "GIR 4 — Aide ponctuelle pour la toilette et l'habillage. Peut se déplacer seule à l'intérieur du domicile.",
    },
    5: {
        tone: 'sage',
        hint: 'GIR 5 — Faible dépendance. Aide ponctuelle pour les repas, le ménage, la toilette. Non éligible APA.',
    },
    6: {
        tone: 'sage',
        hint: "GIR 6 — Autonome pour les actes essentiels. Pas d'éligibilité APA, mais possibilité d'aides via caisse de retraite.",
    },
};

/**
 * GIR (Groupe Iso-Ressources) badge with built-in tooltip. Centralised so every
 * page shares the same colour coding and definition.
 */
export function GirBadge({ gir, hideHint = false }: { gir: number; hideHint?: boolean }) {
    const meta = GIR_HINTS[gir] ?? { tone: 'sage' as const, hint: 'Niveau GIR non standard.' };
    const badge = (
        <Badge tone={meta.tone} size="sm">
            GIR {gir}
        </Badge>
    );
    if (hideHint) {
        return badge;
    }
    return <Tooltip content={meta.hint}>{badge}</Tooltip>;
}
