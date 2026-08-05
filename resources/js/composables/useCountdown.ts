import { computed, onBeforeUnmount, ref } from 'vue';

interface Countdown {
    days: number;
    hours: number;
    minutes: number;
    totalSeconds: number;
    isPast: boolean;
}

export function useCountdown(target: () => string | undefined | null) {
    const now = ref(Date.now());

    const interval = setInterval(() => {
        now.value = Date.now();
    }, 30_000);

    onBeforeUnmount(() => clearInterval(interval));

    const countdown = computed<Countdown | null>(() => {
        const value = target();

        if (!value) {
            return null;
        }

        const diffMs = new Date(value).getTime() - now.value;
        const totalSeconds = Math.max(0, Math.floor(diffMs / 1000));

        return {
            days: Math.floor(totalSeconds / 86_400),
            hours: Math.floor((totalSeconds % 86_400) / 3_600),
            minutes: Math.floor((totalSeconds % 3_600) / 60),
            totalSeconds,
            isPast: diffMs <= 0,
        };
    });

    return { countdown };
}
