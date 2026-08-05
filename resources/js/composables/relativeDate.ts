const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto', style: 'short' });

/**
 * Format an ISO date string as a short human-friendly relative time (e.g. "2h ago", "Now").
 */
export function relativeDate(value: string): string {
    const seconds = (new Date(value).getTime() - Date.now()) / 1000;

    if (Math.abs(seconds) < 60) {
        return 'Now';
    }

    for (const [unit, unitSeconds] of UNITS) {
        if (Math.abs(seconds) >= unitSeconds) {
            return formatter.format(Math.round(seconds / unitSeconds), unit);
        }
    }

    return formatter.format(Math.round(seconds / 60), 'minute');
}
