import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Reverb client setup. Mounts a single Echo instance per browser tab and
 * exposes it on `window.Echo` so the rest of the React tree can subscribe via
 * the `useEcho` hook. Reads its config from Vite env (`VITE_REVERB_*`) which
 * Laravel populates in `.env`.
 *
 * No-op if `VITE_REVERB_APP_KEY` is missing — that's the typical local-dev
 * state without Reverb running. The client never crashes the page if Reverb
 * is down; the subscribe call simply fails silently and the UI degrades.
 */
declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'> | null;
    }
}

const reverbAppKey = (import.meta.env.VITE_REVERB_APP_KEY as string | undefined) ?? '';
const reverbHost = (import.meta.env.VITE_REVERB_HOST as string | undefined) ?? window.location.hostname;
const reverbPort = (import.meta.env.VITE_REVERB_PORT as string | undefined) ?? '8080';
const reverbScheme = (import.meta.env.VITE_REVERB_SCHEME as string | undefined) ?? 'http';

window.Pusher = Pusher;
window.Echo = null;

export function bootEcho(): void {
    if (!reverbAppKey) {
        // Reverb not configured — UI degrades to "no live updates". Components
        // using useEcho will simply not subscribe.
        return;
    }

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbAppKey,
            wsHost: reverbHost,
            wsPort: Number(reverbPort),
            wssPort: Number(reverbPort),
            forceTLS: reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (e) {
        // Boot failures are non-fatal — page still renders.
        // eslint-disable-next-line no-console
        console.warn('[Echo] boot failed:', e);
        window.Echo = null;
    }
}
