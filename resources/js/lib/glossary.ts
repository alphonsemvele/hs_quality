/**
 * Domain glossary — single source of truth for French home-care acronyms
 * and platform jargon. Used by the `<G>` inline component, the
 * `/dashboard/aide/glossaire` page, and tooltips on stat cards.
 *
 * Keep entries terse (≤ 3 sentences). Long definitions belong in a doc page.
 */

export interface GlossaryEntry {
    /** Short acronym or term, as it appears in the UI. */
    term: string;
    /** Full French expansion, when the term is an acronym. */
    full?: string;
    /** Plain-language definition, max ~3 sentences. */
    definition: string;
    /** Category, used for filtering on the glossary page. */
    category: 'autonomie' | 'structure' | 'metier' | 'qualite' | 'rh' | 'tech' | 'compliance';
}

export const glossary: Record<string, GlossaryEntry> = {
    // ─── Autonomie & bénéficiaires ────────────────────────────────────
    GIR: {
        term: 'GIR',
        full: 'Groupe Iso-Ressources',
        definition:
            "Classification de 1 à 6 du niveau d'autonomie d'une personne âgée (grille AGGIR). GIR 1 = très dépendant (assistance permanente), GIR 6 = autonome. Détermine l'éligibilité à l'APA et oriente le plan d'accompagnement.",
        category: 'autonomie',
    },
    AGGIR: {
        term: 'AGGIR',
        full: 'Autonomie Gérontologique Groupes Iso-Ressources',
        definition:
            "Grille d'évaluation officielle qui produit le niveau GIR à partir de 10 activités corporelles et mentales (cohérence, orientation, toilette, habillage, alimentation, élimination, transferts, déplacements, communication, prise de médicaments).",
        category: 'autonomie',
    },
    APA: {
        term: 'APA',
        full: "Allocation Personnalisée d'Autonomie",
        definition:
            "Aide financière versée par le Département aux personnes de 60 ans et plus classées GIR 1 à 4. Finance tout ou partie des heures d'aide à domicile selon le plan d'aide notifié.",
        category: 'autonomie',
    },

    // ─── Structures médico-sociales ───────────────────────────────────
    SAAD: {
        term: 'SAAD',
        full: "Service d'Aide et d'Accompagnement à Domicile",
        definition:
            "Établissement autorisé qui réalise des prestations d'aide à la personne (toilette, repas, ménage, courses) au domicile de personnes âgées, handicapées ou en situation de fragilité.",
        category: 'structure',
    },
    SSIAD: {
        term: 'SSIAD',
        full: 'Service de Soins Infirmiers À Domicile',
        definition:
            "Service qui dispense des soins infirmiers et d'hygiène à domicile sur prescription médicale, financé par l'Assurance Maladie.",
        category: 'structure',
    },
    SPASAD: {
        term: 'SPASAD',
        full: "Service Polyvalent d'Aide et de Soins à Domicile",
        definition:
            "Structure mixte combinant les missions d'un SAAD et d'un SSIAD, pour un parcours coordonné aide + soins chez le même bénéficiaire.",
        category: 'structure',
    },
    ESAD: {
        term: 'ESAD',
        full: 'Équipe Spécialisée Alzheimer à Domicile',
        definition:
            "Équipe pluridisciplinaire (ergothérapeute, psychomotricien, ASG) qui intervient à domicile auprès de personnes atteintes de la maladie d'Alzheimer ou apparentée, sur prescription.",
        category: 'structure',
    },
    CCAS: {
        term: 'CCAS',
        full: "Centre Communal d'Action Sociale",
        definition:
            "Établissement public communal qui anime l'action sociale locale : aide à domicile, portage de repas, demandes d'aide légale, etc.",
        category: 'structure',
    },

    // ─── Métier intervenants ──────────────────────────────────────────
    DEAVS: {
        term: 'DEAVS',
        full: "Diplôme d'État d'Auxiliaire de Vie Sociale",
        definition:
            "Diplôme historique (niveau V) remplacé en 2016 par le DEAES, qui reste valide à vie. Permet d'exercer comme auxiliaire de vie sociale en SAAD/SPASAD.",
        category: 'metier',
    },
    AES: {
        term: 'AES',
        full: 'Accompagnant Éducatif et Social',
        definition:
            "Diplôme d'État (niveau V) issu de la fusion du DEAVS et du DEAMP en 2016. Trois spécialités : accompagnement de la vie à domicile, en structure collective, et de l'éducation inclusive.",
        category: 'metier',
    },
    ADVF: {
        term: 'ADVF',
        full: 'Assistant De Vie aux Familles',
        definition:
            "Titre professionnel délivré par le ministère du Travail. Permet d'accompagner les personnes dans les actes essentiels de la vie quotidienne au domicile privé.",
        category: 'metier',
    },
    CQP: {
        term: 'CQP',
        full: 'Certificat de Qualification Professionnelle',
        definition:
            "Certificat délivré par une branche professionnelle qui atteste de compétences spécifiques. Dans l'aide à domicile : CQP Assistant de vie, CQP Auxiliaire de vie, etc.",
        category: 'metier',
    },
    ASG: {
        term: 'ASG',
        full: 'Assistant de Soins en Gérontologie',
        definition:
            "Spécialisation post-diplôme (140 h) destinée aux aides-soignants, AES et AMP qui interviennent auprès de personnes atteintes de troubles cognitifs (Alzheimer, démences).",
        category: 'metier',
    },

    // ─── Qualité & contrôle ───────────────────────────────────────────
    HAS: {
        term: 'HAS',
        full: 'Haute Autorité de Santé',
        definition:
            "Autorité publique indépendante qui définit le référentiel d'évaluation des établissements et services sociaux et médico-sociaux (ESSMS) — visite obligatoire tous les 5 ans depuis 2022.",
        category: 'qualite',
    },
    ARS: {
        term: 'ARS',
        full: 'Agence Régionale de Santé',
        definition:
            "Établissement public régional qui autorise, contrôle et finance les SSIAD/SPASAD/ESAD. Toute déclaration d'événement indésirable grave (EIG) lui est transmise sous 48 h.",
        category: 'qualite',
    },
    EIG: {
        term: 'EIG',
        full: 'Événement Indésirable Grave',
        definition:
            "Incident ayant causé ou pouvant causer un préjudice physique, psychologique ou social à un bénéficiaire. Obligation de déclaration à l'ARS sous 48 h (Article L1413-14 CSP).",
        category: 'qualite',
    },
    QVCT: {
        term: 'QVCT',
        full: 'Qualité de Vie et des Conditions de Travail',
        definition:
            "Démarche obligatoire (ANI 2020) qui vise à améliorer simultanément la performance de l'organisation et le bien-être des salariés. Pilotée via baromètres, signaux faibles et plans d'actions.",
        category: 'qualite',
    },
    PAC: {
        term: 'PAC',
        full: "Plan d'Amélioration Continue",
        definition:
            "Liste structurée d'actions correctives ou préventives, chacune assignée à un responsable avec une échéance et un indicateur de réussite. Suivi obligatoire pour la certification HAS.",
        category: 'qualite',
    },
    CR: {
        term: 'CR',
        full: "Compte-Rendu d'intervention",
        definition:
            "Rapport rédigé par l'intervenant en fin de visite : tâches réalisées, observations, photos éventuelles, signature du bénéficiaire. Pièce juridique en cas de réclamation.",
        category: 'qualite',
    },

    // ─── RH & formation ───────────────────────────────────────────────
    DPC: {
        term: 'DPC',
        full: 'Développement Professionnel Continu',
        definition:
            "Obligation triennale de formation des professionnels de santé. Validable via des actions cognitives, des analyses de pratiques et des programmes intégrés.",
        category: 'rh',
    },

    // ─── Tech & sécurité ──────────────────────────────────────────────
    MFA: {
        term: 'MFA',
        full: 'Multi-Factor Authentication',
        definition:
            "Authentification à plusieurs facteurs. Sur la plateforme : mot de passe + code temporaire généré par une application authenticator (TOTP, RFC 6238). Obligatoire pour les rôles bureau.",
        category: 'tech',
    },
    TOTP: {
        term: 'TOTP',
        full: 'Time-based One-Time Password',
        definition:
            "Code à 6 chiffres généré toutes les 30 secondes par une application (Google Authenticator, 1Password, Authy). Aucune dépendance réseau, immunisé au SIM-swap.",
        category: 'tech',
    },
    SSE: {
        term: 'SSE-KMS',
        full: 'Server-Side Encryption with KMS',
        definition:
            "Chiffrement automatique des fichiers stockés sur S3, avec une clé gérée par AWS KMS. Garantit qu'un accès direct au bucket ne permet pas de lire les photos d'interventions.",
        category: 'tech',
    },

    // ─── Compliance ───────────────────────────────────────────────────
    RGPD: {
        term: 'RGPD',
        full: 'Règlement Général sur la Protection des Données',
        definition:
            "Règlement européen (UE 2016/679) qui encadre le traitement des données personnelles. Impose notamment le droit à l'effacement (Art 17) et la traçabilité (Art 30).",
        category: 'compliance',
    },
    HDS: {
        term: 'HDS',
        full: 'Hébergeur de Données de Santé',
        definition:
            "Certification ASIP Santé / Agence du Numérique en Santé exigée pour héberger des données de santé à caractère personnel en France. Couvre intégrité, disponibilité, confidentialité et traçabilité.",
        category: 'compliance',
    },
    ANSSI: {
        term: 'ANSSI',
        full: "Agence Nationale de la Sécurité des Systèmes d'Information",
        definition:
            "Autorité française de cybersécurité. Publie des référentiels (PSSI-MCAS, PA-022) et déconseille l'usage du SMS comme second facteur d'authentification.",
        category: 'compliance',
    },
    CNIL: {
        term: 'CNIL',
        full: "Commission Nationale de l'Informatique et des Libertés",
        definition:
            "Autorité française de protection des données personnelles. Contrôle l'application du RGPD ; peut infliger des sanctions jusqu'à 4 % du chiffre d'affaires mondial.",
        category: 'compliance',
    },
    DPO: {
        term: 'DPO',
        full: 'Data Protection Officer',
        definition:
            "Délégué à la Protection des Données. Obligatoire pour tout traitement de données de santé à grande échelle. Veille à la conformité RGPD et est point de contact de la CNIL.",
        category: 'compliance',
    },
    ESSMS: {
        term: 'ESSMS',
        full: 'Établissement ou Service Social et Médico-Social',
        definition:
            "Périmètre réglementaire couvrant les SAAD, SSIAD, SPASAD, ESAD, EHPAD, foyers de vie, CCAS, etc. Tous soumis au référentiel d'évaluation HAS depuis 2022.",
        category: 'qualite',
    },
};

/**
 * Look up an entry by term (case-insensitive). Returns `undefined` if missing
 * — callers should fall back to rendering raw children.
 */
export function lookupTerm(term: string): GlossaryEntry | undefined {
    const upper = term.toUpperCase();
    return glossary[upper];
}

/**
 * All entries grouped by category, useful for the `/glossaire` page.
 */
export function glossaryByCategory(): Record<GlossaryEntry['category'], GlossaryEntry[]> {
    const buckets: Record<GlossaryEntry['category'], GlossaryEntry[]> = {
        autonomie: [],
        structure: [],
        metier: [],
        qualite: [],
        rh: [],
        tech: [],
        compliance: [],
    };
    for (const entry of Object.values(glossary)) {
        buckets[entry.category].push(entry);
    }
    for (const list of Object.values(buckets)) {
        list.sort((a, b) => a.term.localeCompare(b.term, 'fr'));
    }
    return buckets;
}
