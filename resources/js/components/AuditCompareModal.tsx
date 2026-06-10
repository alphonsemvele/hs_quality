import { Badge, Modal, Select } from '@/components/ui';
import { useEffect, useMemo, useState } from 'react';

export interface ComparableAudit {
    id: string;
    titre: string;
    referentiel_label: string;
    statut_label: string;
    date_audit: string | null;
    score: number | null;
    nb_ecarts: number;
}

interface Props {
    open: boolean;
    onClose: () => void;
    audits: ComparableAudit[];
}

/**
 * Pick two audits from a dropdown and show their headline numbers
 * (score, écarts, statut) side-by-side. Pure FE: it reuses the audit
 * list already loaded on the audits index — no extra fetch.
 *
 * Designed for the common "did we improve since last quarter?" question
 * a coordinateur asks before a HAS visit.
 */
export function AuditCompareModal({ open, onClose, audits }: Props) {
    const [leftId, setLeftId] = useState<string>('');
    const [rightId, setRightId] = useState<string>('');

    // Default the pickers to the two most recently dated audits when the
    // modal opens — picks the freshest "before / after" pair.
    useEffect(() => {
        if (!open) return;
        const sorted = [...audits].sort((a, b) => (b.date_audit ?? '').localeCompare(a.date_audit ?? ''));
        if (sorted[0] && !leftId) setLeftId(sorted[0].id);
        if (sorted[1] && !rightId) setRightId(sorted[1].id);
    }, [open, audits, leftId, rightId]);

    const left = useMemo(() => audits.find((a) => a.id === leftId), [audits, leftId]);
    const right = useMemo(() => audits.find((a) => a.id === rightId), [audits, rightId]);

    const scoreDelta = left?.score !== null && left?.score !== undefined && right?.score !== null && right?.score !== undefined
        ? left.score - right.score
        : null;
    const ecartDelta = left && right ? left.nb_ecarts - right.nb_ecarts : null;

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Comparer deux audits"
            description="Sélectionnez deux audits pour visualiser l'évolution score / écarts."
            size="lg"
            iconTone="brand"
            icon={
                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                    <path d="M9 3v18M15 3v18M3 9h18M3 15h18" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            }
        >
            <div className="space-y-5">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <AuditPicker label="Audit A" value={leftId} onChange={setLeftId} audits={audits} />
                    <AuditPicker label="Audit B" value={rightId} onChange={setRightId} audits={audits} excludeId={leftId} />
                </div>

                {left && right ? (
                    <div className="overflow-hidden rounded-xl border border-ink-200 dark:border-ink-700/60">
                        <table className="w-full table-fixed text-sm">
                            <thead className="bg-ink-50/40 dark:bg-ink-900/30">
                                <tr>
                                    <th className="w-1/3 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                        Critère
                                    </th>
                                    <th className="px-3 py-2 text-left">
                                        <Badge tone="brand" size="xs">A</Badge>
                                    </th>
                                    <th className="px-3 py-2 text-left">
                                        <Badge tone="sage" size="xs">B</Badge>
                                    </th>
                                    <th className="w-20 px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                        Δ
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                <Row label="Référentiel" a={left.referentiel_label} b={right.referentiel_label} />
                                <Row label="Statut" a={left.statut_label} b={right.statut_label} />
                                <Row label="Date" a={left.date_audit ?? '—'} b={right.date_audit ?? '—'} />
                                <Row
                                    label="Score"
                                    a={left.score !== null ? `${left.score} %` : '—'}
                                    b={right.score !== null ? `${right.score} %` : '—'}
                                    delta={scoreDelta !== null ? formatDelta(scoreDelta, '%', 'higher-better') : null}
                                />
                                <Row
                                    label="Écarts"
                                    a={String(left.nb_ecarts)}
                                    b={String(right.nb_ecarts)}
                                    delta={ecartDelta !== null ? formatDelta(ecartDelta, '', 'lower-better') : null}
                                />
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <p className="rounded-xl border border-dashed border-ink-200 p-6 text-center text-xs text-ink-500 dark:border-ink-700 dark:text-ink-400">
                        Sélectionnez deux audits différents pour afficher la comparaison.
                    </p>
                )}
            </div>
        </Modal>
    );
}

function AuditPicker({
    label,
    value,
    onChange,
    audits,
    excludeId,
}: {
    label: string;
    value: string;
    onChange: (id: string) => void;
    audits: ComparableAudit[];
    excludeId?: string;
}) {
    const options = audits.filter((a) => a.id !== excludeId);
    return (
        <label className="block">
            <span className="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                {label}
            </span>
            <Select value={value} onChange={(e) => onChange(e.target.value)}>
                <option value="">— Choisir un audit —</option>
                {options.map((a) => (
                    <option key={a.id} value={a.id}>
                        {a.titre} {a.date_audit ? `· ${a.date_audit}` : ''}
                    </option>
                ))}
            </Select>
        </label>
    );
}

function Row({
    label,
    a,
    b,
    delta,
}: {
    label: string;
    a: string;
    b: string;
    delta?: string | null;
}) {
    return (
        <tr>
            <td className="px-3 py-2 text-xs font-medium text-ink-500 dark:text-ink-400">{label}</td>
            <td className="px-3 py-2 text-sm text-ink-900 dark:text-white">{a}</td>
            <td className="px-3 py-2 text-sm text-ink-900 dark:text-white">{b}</td>
            <td className="px-3 py-2 text-right font-mono text-xs tabular-nums">
                {delta ?? <span className="text-ink-300 dark:text-ink-600">—</span>}
            </td>
        </tr>
    );
}

function formatDelta(value: number, unit: string, intent: 'higher-better' | 'lower-better'): string {
    if (value === 0) return '0';
    const positive = value > 0;
    const goodNews = (intent === 'higher-better' && positive) || (intent === 'lower-better' && !positive);
    const arrow = positive ? '↑' : '↓';
    const sign = positive ? '+' : '';
    // Strings are rendered server-side too — we keep the colour application
    // local to the caller (table) so this stays a pure formatter.
    return `${arrow} ${sign}${value}${unit}${goodNews ? '' : ''}`;
}
