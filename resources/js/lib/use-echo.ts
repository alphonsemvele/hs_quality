import { useEffect } from 'react';

/**
 * Subscribe to a Reverb channel for the lifetime of a React component.
 *
 * Usage:
 *   useEcho('private', 'discussion-group.123', 'MessagePosted', (e) => {
 *       setMessages((prev) => [...prev, e.message]);
 *   });
 *
 * No-ops cleanly if Echo isn't initialised (e.g. Reverb not configured in
 * the env, or boot failed). Detaches the listener and leaves the channel
 * on unmount. Private/presence channels expect an auth endpoint exposed by
 * Laravel — the Echo client handles auth via session cookies.
 */
export type ChannelKind = 'public' | 'private' | 'presence';

export function useEcho<TPayload = unknown>(
    kind: ChannelKind,
    name: string,
    event: string,
    handler: (payload: TPayload) => void,
    deps: ReadonlyArray<unknown> = [],
): void {
    useEffect(() => {
        const echo = typeof window !== 'undefined' ? window.Echo : null;
        if (!echo) return;

        let channel;
        try {
            switch (kind) {
                case 'private':
                    channel = echo.private(name);
                    break;
                case 'presence':
                    channel = echo.join(name);
                    break;
                default:
                    channel = echo.channel(name);
            }
            channel.listen(event, handler);
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn('[useEcho] subscribe failed:', name, e);
            return;
        }

        return () => {
            try {
                channel.stopListening(event);
                echo.leave(name);
            } catch {
                // ignore — channel may already be gone if Echo socket died.
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [kind, name, event, ...deps]);
}
