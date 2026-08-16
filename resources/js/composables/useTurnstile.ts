import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

declare global {
    interface Window {
        turnstile?: {
            render: (container: HTMLElement, options: Record<string, unknown>) => string;
            remove: (widgetId: string) => void;
        };
    }
}

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

let scriptPromise: Promise<void> | null = null;

function loadScript(): Promise<void> {
    if (window.turnstile) {
        return Promise.resolve();
    }

    return (scriptPromise ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Cloudflare Turnstile'));
        document.head.appendChild(script);
    }));
}

/**
 * Mounts a Cloudflare Turnstile challenge into `container` and keeps
 * `token` in sync with the widget's current response. Renders nothing
 * (and `token` stays empty) if `siteKey` isn't configured — see
 * App\Rules\Turnstile, which skips verification the same way when
 * TURNSTILE_SECRET_KEY isn't set, so local/test environments without
 * Cloudflare keys aren't blocked from submitting the form.
 *
 * To switch captcha vendors later, this is the one file to replace —
 * callers only ever see `{ container, token }`, so swapping the
 * implementation here doesn't touch any page that uses it.
 */
export function useTurnstile(siteKey: string | undefined) {
    const container: Ref<HTMLElement | null> = ref(null);
    const token = ref('');
    let widgetId: string | null = null;

    onMounted(async () => {
        if (!siteKey || !container.value) {
            return;
        }

        await loadScript();

        widgetId =
            window.turnstile?.render(container.value, {
                sitekey: siteKey,
                callback: (value: string) => (token.value = value),
                'expired-callback': () => (token.value = ''),
            }) ?? null;
    });

    onBeforeUnmount(() => {
        if (widgetId) {
            window.turnstile?.remove(widgetId);
        }
    });

    return { container, token };
}
