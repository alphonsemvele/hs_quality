import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    total?: number;
    perPage?: number;
}

export function Pagination({ currentPage, lastPage, total, perPage }: PaginationProps) {
    if (lastPage <= 1) return null;

    const pages = getPageNumbers(currentPage, lastPage);

    const buildUrl = (page: number) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', String(page));
        return `${url.pathname}${url.search}`;
    };

    return (
        <nav
            className="flex items-center justify-between border-t border-ink-100 px-1 pt-4 dark:border-ink-700/60"
            aria-label="Pagination"
        >
            <div className="text-xs text-ink-500 dark:text-ink-400">
                {total !== undefined && perPage !== undefined && (
                    <>
                        <span className="font-medium text-ink-700 dark:text-ink-300">
                            {Math.min((currentPage - 1) * perPage + 1, total)}
                        </span>
                        {' – '}
                        <span className="font-medium text-ink-700 dark:text-ink-300">
                            {Math.min(currentPage * perPage, total)}
                        </span>
                        {' sur '}
                        <span className="font-medium text-ink-700 dark:text-ink-300">{total}</span>
                    </>
                )}
            </div>

            <div className="flex items-center gap-1">
                {/* Previous */}
                {currentPage > 1 ? (
                    <Link
                        href={buildUrl(currentPage - 1)}
                        className="flex size-8 cursor-pointer items-center justify-center rounded-lg text-ink-500 transition-colors hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
                        aria-label="Page précédente"
                        preserveState
                        preserveScroll
                    >
                        <ChevronLeftIcon />
                    </Link>
                ) : (
                    <span className="flex size-8 items-center justify-center rounded-lg text-ink-300 dark:text-ink-600" aria-disabled>
                        <ChevronLeftIcon />
                    </span>
                )}

                {/* Page numbers */}
                {pages.map((page, i) =>
                    page === '...' ? (
                        <span key={`ellipsis-${i}`} className="flex size-8 items-center justify-center text-xs text-ink-400 dark:text-ink-500">
                            ...
                        </span>
                    ) : (
                        <Link
                            key={page}
                            href={buildUrl(page as number)}
                            className={cn(
                                'flex size-8 cursor-pointer items-center justify-center rounded-lg text-xs font-medium transition-colors',
                                page === currentPage
                                    ? 'bg-brand-600 text-white dark:bg-brand-500'
                                    : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700',
                            )}
                            aria-label={`Page ${page}`}
                            aria-current={page === currentPage ? 'page' : undefined}
                            preserveState
                            preserveScroll
                        >
                            {page}
                        </Link>
                    ),
                )}

                {/* Next */}
                {currentPage < lastPage ? (
                    <Link
                        href={buildUrl(currentPage + 1)}
                        className="flex size-8 cursor-pointer items-center justify-center rounded-lg text-ink-500 transition-colors hover:bg-ink-100 dark:text-ink-400 dark:hover:bg-ink-700"
                        aria-label="Page suivante"
                        preserveState
                        preserveScroll
                    >
                        <ChevronRightIcon />
                    </Link>
                ) : (
                    <span className="flex size-8 items-center justify-center rounded-lg text-ink-300 dark:text-ink-600" aria-disabled>
                        <ChevronRightIcon />
                    </span>
                )}
            </div>
        </nav>
    );
}

function getPageNumbers(current: number, last: number): (number | '...')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const pages: (number | '...')[] = [1];

    if (current > 3) {
        pages.push('...');
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    if (current < last - 2) {
        pages.push('...');
    }

    pages.push(last);

    return pages;
}

function ChevronLeftIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    );
}

function ChevronRightIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    );
}
