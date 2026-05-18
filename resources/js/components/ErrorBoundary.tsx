import { Component, type ErrorInfo, type ReactNode } from 'react';

interface ErrorBoundaryProps {
    children: ReactNode;
}

interface ErrorBoundaryState {
    error: Error | null;
}

/**
 * Top-level error boundary.
 *
 * Wraps the whole Inertia app so an uncaught render error renders a
 * friendly French fallback instead of a blank page. The full error +
 * componentStack are logged to the console so devs can still diagnose
 * what happened.
 *
 * Why not Sentry's withErrorBoundary? Sentry isn't initialised on the
 * frontend yet (DSN is only used by the PHP runtime). This keeps the
 * UX guarantee without coupling to Sentry's client SDK.
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    state: ErrorBoundaryState = { error: null };

    static getDerivedStateFromError(error: Error): ErrorBoundaryState {
        return { error };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        if (typeof window !== 'undefined' && typeof console !== 'undefined') {
            // eslint-disable-next-line no-console
            console.error('[ErrorBoundary] uncaught render error', error, info);
        }
    }

    private reset = (): void => {
        this.setState({ error: null });
    };

    private reload = (): void => {
        if (typeof window !== 'undefined') {
            window.location.reload();
        }
    };

    render(): ReactNode {
        if (this.state.error !== null) {
            const error = this.state.error;
            return (
                <div className="flex min-h-dvh items-center justify-center bg-ink-50 px-4 py-12 dark:bg-ink-900">
                    <div className="w-full max-w-md rounded-2xl border border-ink-200 bg-white p-8 shadow-lg dark:border-ink-700 dark:bg-ink-800">
                        <div className="flex items-center gap-3">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-danger-100 text-danger-600 dark:bg-danger-900/40 dark:text-danger-300">
                                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                    <line x1="12" y1="9" x2="12" y2="13" strokeLinecap="round" />
                                    <line x1="12" y1="17" x2="12.01" y2="17" strokeLinecap="round" />
                                </svg>
                            </span>
                            <div className="min-w-0">
                                <h1 className="text-lg font-bold tracking-tight text-ink-900 dark:text-white">
                                    Une erreur est survenue
                                </h1>
                                <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                    L'écran n'a pas pu s'afficher correctement.
                                </p>
                            </div>
                        </div>

                        <p className="mt-5 text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                            Essayez de rafraîchir la page. Si le problème persiste, contactez votre
                            référent qualité ou écrivez à{' '}
                            <a
                                href="mailto:support@hsquality.fr"
                                className="font-medium text-brand-600 underline-offset-2 hover:underline dark:text-brand-400"
                            >
                                support@hsquality.fr
                            </a>
                            .
                        </p>

                        {import.meta.env?.DEV && (
                            <details className="mt-5 rounded-lg border border-ink-200 bg-ink-50 p-3 text-xs dark:border-ink-700 dark:bg-ink-900/60">
                                <summary className="cursor-pointer font-mono font-semibold text-ink-700 dark:text-ink-200">
                                    Détails techniques
                                </summary>
                                <pre className="mt-2 max-h-48 overflow-auto whitespace-pre-wrap break-words font-mono text-[11px] text-ink-600 dark:text-ink-400">
                                    {error.name}: {error.message}
                                    {error.stack && '\n\n' + error.stack}
                                </pre>
                            </details>
                        )}

                        <div className="mt-6 flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                onClick={this.reload}
                                className="inline-flex items-center justify-center rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40"
                            >
                                Rafraîchir la page
                            </button>
                            <button
                                type="button"
                                onClick={this.reset}
                                className="rounded-full border border-ink-200 bg-white px-5 py-2.5 text-sm font-medium text-ink-700 transition-colors hover:border-ink-300 hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                            >
                                Réessayer
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
