// Inline helpers — avoids the jslib.k6.io CDN fetch that fails in airgapped
// Docker environments (no outbound DNS from the k6 container).
function randomString(length) {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let s = '';
    for (let i = 0; i < length; i++) s += chars[Math.floor(Math.random() * chars.length)];
    return s;
}
function randomIntBetween(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

/**
 * Realistic-ish payload factories. The k6 test isn't a fixture — these
 * shapes match what the React Native app actually posts.
 */

export function checkInPayload() {
    return {
        // Random Paris-area lat/lng so geofencing logic gets exercised.
        latitude: 48.85 + (Math.random() - 0.5) * 0.1,
        longitude: 2.35 + (Math.random() - 0.5) * 0.1,
    };
}

export function checkOutPayload() {
    return {
        report_text: `Visite réalisée. Bénéficiaire en bon état général. Aide à la toilette + repas. ${randomString(40)}`,
    };
}

export function incidentPayload() {
    const categories = ['chute', 'erreur_medicamenteuse', 'agression', 'maltraitance_suspectee'];
    return {
        categorie: categories[randomIntBetween(0, categories.length - 1)],
        description: `Bénéficiaire signalé. Détails: ${randomString(80)}.`,
        occurred_at: new Date(Date.now() - randomIntBetween(60_000, 3_600_000)).toISOString(),
        avec_deces: false,
        avec_hospitalisation: false,
        avec_blessure_physique: Math.random() > 0.7,
    };
}

/**
 * Build a realistic mobile sync batch that an intervenant would flush
 * after a morning offline tour: a few check-ins + check-outs + one
 * incident. Each op gets a unique client_op_id (UUIDv4-ish for k6).
 */
export function syncBatchPayload(interventionIds) {
    const ops = [];

    interventionIds.slice(0, 3).forEach((id) => {
        ops.push({
            client_op_id: clientUuid(),
            kind: 'intervention.check_in',
            resource_id: id,
            payload: checkInPayload(),
        });
        ops.push({
            client_op_id: clientUuid(),
            kind: 'intervention.check_out',
            resource_id: id,
            payload: checkOutPayload(),
        });
    });

    if (Math.random() > 0.7) {
        ops.push({
            client_op_id: clientUuid(),
            kind: 'incident.create',
            payload: incidentPayload(),
        });
    }

    return { operations: ops };
}

/** Lightweight UUIDv4 generator — k6 has no native crypto.randomUUID. */
export function clientUuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}
