import { useMemo } from 'react';

interface VerbatimCloudProps {
    /** Raw verbatim entries — one entry per response. Order is irrelevant. */
    verbatims: string[];
    /** Minimum occurrences before a word is shown (anonymisation guard). Defaults to 3. */
    minOccurrences?: number;
    /** Maximum number of words rendered. Defaults to 40. */
    maxWords?: number;
    /** Optional aria-label for the SVG. */
    ariaLabel?: string;
    className?: string;
}

/**
 * French stopwords. Deliberately compact — common articles, conjunctions,
 * pronouns, auxiliary verbs. We don't try to do morphological stemming;
 * picking up "travailler" and "travail" as two separate tokens is fine
 * for the visualisation we're going for.
 */
const STOPWORDS = new Set<string>([
    'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'd', "d'", 'au', 'aux',
    'et', 'ou', 'mais', 'donc', 'or', 'ni', 'car',
    'je', 'tu', 'il', 'elle', 'on', 'nous', 'vous', 'ils', 'elles',
    'me', 'te', 'se', 'lui', 'leur', 'leurs', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes',
    'son', 'sa', 'ses', 'notre', 'nos', 'votre', 'vos',
    'ce', 'cet', 'cette', 'ces', 'ça', 'cela', 'celui', 'celle',
    'qui', 'que', 'quoi', 'dont', 'où', 'quand', 'comment', 'pourquoi',
    'pas', 'plus', 'moins', 'très', 'trop', 'bien', 'mal', 'aussi', 'encore', 'déjà',
    'a', 'ai', 'as', 'avez', 'avons', 'ont', 'avait', 'avaient', 'avoir',
    'est', 'es', 'êtes', 'sommes', 'sont', 'était', 'étaient', 'être',
    'fait', 'faire', 'faut', 'va', 'vais', 'vas', 'aller',
    'si', 'oui', 'non', 'tout', 'tous', 'toute', 'toutes',
    'dans', 'sur', 'sous', 'avec', 'sans', 'pour', 'par', 'vers', 'chez', 'entre',
    'en', 'y', 'comme', 'depuis', 'pendant', 'avant', 'après',
    'leurs', 'son', 'sa',
    '',
]);

interface Token {
    word: string;
    count: number;
    /** Display weight 0–1 used to compute font size. */
    weight: number;
}

function tokenise(verbatim: string): string[] {
    return verbatim
        .toLowerCase()
        .normalize('NFD')
        // strip combining diacritics so "récupération" ↔ "recuperation"
        .replace(/[̀-ͯ]/g, '')
        // keep letters (ASCII after the strip above) + apostrophes
        .replace(/[^a-z' ]+/g, ' ')
        .split(/\s+/)
        .filter((w) => w.length >= 3 && !STOPWORDS.has(w));
}

/**
 * Verbatim word cloud (FE-only).
 *
 * Tokenises a list of free-text responses, strips French stopwords, and
 * lays out the top-N most frequent words on an SVG canvas. A minimum
 * occurrences threshold (default 3) acts as an anonymity guard so that
 * unique turns of phrase cannot be traced back to a single respondent.
 *
 * The layout is a deterministic "row-by-row" pack — we don't try to do
 * a spiral pack (more code, marginal aesthetics).
 */
export function VerbatimCloud({
    verbatims,
    minOccurrences = 3,
    maxWords = 40,
    ariaLabel = 'Nuage de verbatims',
    className,
}: VerbatimCloudProps) {
    const tokens = useMemo<Token[]>(() => {
        const counts = new Map<string, number>();
        for (const v of verbatims) {
            const seen = new Set<string>();
            for (const w of tokenise(v)) {
                // Count each unique word once per verbatim — limits the
                // influence of a single repetitive response.
                if (seen.has(w)) continue;
                seen.add(w);
                counts.set(w, (counts.get(w) ?? 0) + 1);
            }
        }
        const filtered = Array.from(counts.entries())
            .filter(([, c]) => c >= minOccurrences)
            .sort((a, b) => b[1] - a[1])
            .slice(0, maxWords);
        if (filtered.length === 0) return [];
        const max = filtered[0][1];
        const min = filtered[filtered.length - 1][1];
        const range = Math.max(1, max - min);
        return filtered.map(([word, count]) => ({
            word,
            count,
            weight: (count - min) / range,
        }));
    }, [verbatims, minOccurrences, maxWords]);

    if (tokens.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-ink-200 bg-ink-50/40 p-6 text-center text-xs text-ink-500 dark:border-ink-700 dark:bg-ink-800/40 dark:text-ink-400">
                Pas assez de verbatims pour afficher un nuage (seuil d'anonymat :
                {' '}
                {minOccurrences}
                {' '}
                occurrences minimum).
            </div>
        );
    }

    const FONT_MIN = 12;
    const FONT_MAX = 36;
    const sized = tokens.map((t) => ({
        ...t,
        size: Math.round(FONT_MIN + t.weight * (FONT_MAX - FONT_MIN)),
    }));

    return (
        <div className={className}>
            <ul
                aria-label={ariaLabel}
                className="flex flex-wrap items-baseline justify-center gap-x-3 gap-y-2 leading-none"
            >
                {sized.map((t) => (
                    <li
                        key={t.word}
                        className="font-medium text-ink-700 transition-colors hover:text-brand-700 dark:text-ink-200 dark:hover:text-brand-300"
                        style={{ fontSize: `${t.size}px`, lineHeight: 1 }}
                        title={`${t.word} — ${t.count} occurrences`}
                    >
                        {t.word}
                    </li>
                ))}
            </ul>
            <p className="mt-3 text-center text-[11px] text-ink-500 dark:text-ink-400">
                {sized.length} mots affichés · seuil d'anonymat ≥ {minOccurrences} occurrences
            </p>
        </div>
    );
}
