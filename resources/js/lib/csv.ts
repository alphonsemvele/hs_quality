/**
 * Tiny CSV utility — no library, no backend.
 *
 * Use case: let a coordinator export the currently visible (and already
 * tenant-filtered) data to a spreadsheet, without round-tripping through
 * the server. Each list page wires this through an "Exporter CSV" button.
 *
 * Format: RFC 4180-compliant. Excel-friendly because we prefix with a
 * UTF-8 BOM so non-ASCII characters render correctly in `Excel`-fr.
 */

/**
 * Quote a cell value so commas, quotes and newlines survive a round-trip
 * through a spreadsheet. Numbers and booleans are stringified naturally;
 * `null` and `undefined` become empty strings.
 */
function quote(value: unknown): string {
    if (value === null || value === undefined) return '';
    const stringified = typeof value === 'string' ? value : String(value);
    if (/[",\n\r;]/.test(stringified)) {
        return '"' + stringified.replace(/"/g, '""') + '"';
    }
    return stringified;
}

export interface CsvColumn<Row> {
    /** Column header in the first row of the file. */
    header: string;
    /** Selector: either an object key or a (row) → value function. */
    accessor: keyof Row | ((row: Row) => unknown);
}

/**
 * Build a CSV string from rows + columns.
 *
 * @example
 *   const csv = toCsv(beneficiaries, [
 *     { header: 'Nom', accessor: 'full_name' },
 *     { header: 'Ville', accessor: 'city' },
 *     { header: 'Âge', accessor: (b) => b.age ?? '' },
 *   ]);
 */
export function toCsv<Row>(rows: Row[], columns: CsvColumn<Row>[]): string {
    const headerRow = columns.map((c) => quote(c.header)).join(',');
    const bodyRows = rows.map((row) =>
        columns
            .map((c) => {
                const raw =
                    typeof c.accessor === 'function'
                        ? c.accessor(row)
                        : (row as Record<string, unknown>)[c.accessor as string];
                return quote(raw);
            })
            .join(','),
    );
    return [headerRow, ...bodyRows].join('\r\n');
}

/**
 * Trigger a CSV file download in the user's browser. Prepends a UTF-8
 * BOM so French accents render correctly when the file is opened in a
 * default-locale Excel.
 */
export function downloadCsv<Row>(filename: string, rows: Row[], columns: CsvColumn<Row>[]): void {
    if (typeof window === 'undefined') return;
    const csv = toCsv(rows, columns);
    const bom = '﻿';
    const blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename.endsWith('.csv') ? filename : `${filename}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 100);
}
