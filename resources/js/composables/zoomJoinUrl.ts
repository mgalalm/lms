/**
 * Append Zoom's `uname` query param so the participant's display name
 * inside the meeting matches their real name instead of a Zoom default.
 * Reliable for guest joins (join_url); the host's name on start_url is
 * tied to the shared Zoom host account's own profile and may not honor it.
 */
export function withDisplayName(url: string, name: string): string {
    const separator = url.includes('?') ? '&' : '?';

    return `${url}${separator}uname=${encodeURIComponent(name)}`;
}
