import { Fragment, ReactNode } from 'react';

/**
 * Tiny Markdown subset parser → React tree.
 *
 * Deliberately limited so we can keep all rendering through React's
 * normal escaping (no `dangerouslySetInnerHTML`). Supports:
 *   - Headings:    `# H1`, `## H2`, `### H3`
 *   - Lists:       `- item` (unordered), `1. item` (ordered)
 *   - Bold:        `**text**`
 *   - Italic:      `_text_` or `*text*`
 *   - Inline code: `` `text` ``
 *   - Link:        `[label](https://…)` — http(s) only, anything else is rendered as plain text
 *   - Paragraphs separated by blank lines, hard wraps with two trailing spaces
 *
 * Everything else is rendered as text — including raw HTML, which is treated
 * as a string and escaped by React. This is a feature, not a limitation:
 * we are NOT a CommonMark engine, we are an XSS-resistant subset for an
 * internal RH/QVCT news feed.
 */
export function renderSafeMarkdown(input: string): ReactNode {
    if (!input || input.trim() === '') {
        return null;
    }

    const lines = input.replace(/\r\n/g, '\n').split('\n');
    const blocks: ReactNode[] = [];
    let buffer: string[] = [];
    let listKind: 'ul' | 'ol' | null = null;
    let listItems: string[] = [];

    const flushParagraph = () => {
        if (buffer.length === 0) return;
        const text = buffer.join('\n').trim();
        if (text !== '') {
            blocks.push(
                <p key={blocks.length} className="text-sm leading-relaxed text-ink-700 dark:text-ink-200">
                    {renderInline(text)}
                </p>,
            );
        }
        buffer = [];
    };

    const flushList = () => {
        if (listKind === null || listItems.length === 0) {
            listKind = null;
            listItems = [];
            return;
        }
        const items = listItems.map((item, i) => (
            <li key={i} className="text-sm leading-relaxed text-ink-700 dark:text-ink-200">
                {renderInline(item)}
            </li>
        ));
        blocks.push(
            listKind === 'ul' ? (
                <ul key={blocks.length} className="ml-5 list-disc space-y-1">
                    {items}
                </ul>
            ) : (
                <ol key={blocks.length} className="ml-5 list-decimal space-y-1">
                    {items}
                </ol>
            ),
        );
        listKind = null;
        listItems = [];
    };

    for (const raw of lines) {
        const line = raw.replace(/\s+$/, ''); // trim trailing whitespace

        if (line === '') {
            flushParagraph();
            flushList();
            continue;
        }

        const h1 = /^# (.+)$/.exec(line);
        const h2 = /^## (.+)$/.exec(line);
        const h3 = /^### (.+)$/.exec(line);
        const ul = /^[-*] (.+)$/.exec(line);
        const ol = /^\d+\.\s+(.+)$/.exec(line);

        if (h1 || h2 || h3) {
            flushParagraph();
            flushList();
            if (h1) {
                blocks.push(
                    <h1 key={blocks.length} className="text-2xl font-bold tracking-tight text-ink-900 dark:text-white">
                        {renderInline(h1[1])}
                    </h1>,
                );
            } else if (h2) {
                blocks.push(
                    <h2 key={blocks.length} className="text-xl font-semibold tracking-tight text-ink-900 dark:text-white">
                        {renderInline(h2[1])}
                    </h2>,
                );
            } else if (h3) {
                blocks.push(
                    <h3 key={blocks.length} className="text-base font-semibold text-ink-900 dark:text-white">
                        {renderInline(h3[1])}
                    </h3>,
                );
            }
            continue;
        }

        if (ul) {
            flushParagraph();
            if (listKind !== 'ul') {
                flushList();
                listKind = 'ul';
            }
            listItems.push(ul[1]);
            continue;
        }

        if (ol) {
            flushParagraph();
            if (listKind !== 'ol') {
                flushList();
                listKind = 'ol';
            }
            listItems.push(ol[1]);
            continue;
        }

        // Plain text line — accumulate into current paragraph.
        flushList();
        buffer.push(line);
    }

    flushParagraph();
    flushList();

    return <div className="space-y-3">{blocks}</div>;
}

/**
 * Inline span renderer. Handles bold/italic/code/link inside a single
 * paragraph or list item. Returns an array of React nodes — never a string
 * with HTML in it.
 */
function renderInline(text: string): ReactNode[] {
    const tokens: ReactNode[] = [];
    let cursor = 0;

    // Patterns tried in priority order. The first match at the earliest
    // position wins. This keeps `**_bold italic_**` working (bold first,
    // italic processed when we recurse into the bold child).
    const patterns: Array<{
        regex: RegExp;
        wrap: (inner: string, href?: string) => ReactNode;
    }> = [
        {
            regex: /\*\*(.+?)\*\*/, // bold
            wrap: (inner) => <strong className="font-semibold">{renderInline(inner)}</strong>,
        },
        {
            regex: /(?<![A-Za-z0-9])_(.+?)_(?![A-Za-z0-9])/, // italic via underscores
            wrap: (inner) => <em className="italic">{renderInline(inner)}</em>,
        },
        {
            regex: /(?<![A-Za-z0-9*])\*(.+?)\*(?![A-Za-z0-9*])/, // italic via single asterisk
            wrap: (inner) => <em className="italic">{renderInline(inner)}</em>,
        },
        {
            regex: /`([^`]+)`/, // inline code
            wrap: (inner) => (
                <code className="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-[12px] text-ink-700 dark:bg-ink-700 dark:text-ink-200">
                    {inner}
                </code>
            ),
        },
    ];

    const linkRegex = /\[([^\]]+)\]\(([^)\s]+)\)/;

    while (cursor < text.length) {
        // Try link first because its pattern is the most specific.
        const linkMatch = linkRegex.exec(text.slice(cursor));
        let nextMatchAt = linkMatch ? cursor + linkMatch.index : Infinity;
        let chosen: { idx: number; len: number; node: ReactNode } | null = null;

        if (linkMatch && linkMatch.index === 0) {
            const [, label, href] = linkMatch;
            if (/^https?:\/\//i.test(href)) {
                chosen = {
                    idx: cursor,
                    len: linkMatch[0].length,
                    node: (
                        <a
                            key={tokens.length}
                            href={href}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-brand-600 underline-offset-2 hover:underline dark:text-brand-400"
                        >
                            {renderInline(label)}
                        </a>
                    ),
                };
            }
        }

        for (const { regex, wrap } of patterns) {
            const m = regex.exec(text.slice(cursor));
            if (!m) continue;
            const absoluteAt = cursor + m.index;
            if (!chosen || absoluteAt < chosen.idx) {
                chosen = {
                    idx: absoluteAt,
                    len: m[0].length,
                    node: <Fragment key={tokens.length}>{wrap(m[1])}</Fragment>,
                };
            }
            if (chosen && chosen.idx === absoluteAt) {
                nextMatchAt = absoluteAt;
            }
        }

        if (!chosen) {
            tokens.push(text.slice(cursor));
            break;
        }

        if (chosen.idx > cursor) {
            tokens.push(text.slice(cursor, chosen.idx));
        }
        tokens.push(chosen.node);
        cursor = chosen.idx + chosen.len;

        if (nextMatchAt === Infinity) {
            // unreachable: chosen exists, so a match was found
        }
    }

    return tokens;
}
