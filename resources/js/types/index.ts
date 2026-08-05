import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: string[];
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Course {
    id: number;
    title: string;
    grade_band: string | null;
}

export interface Module {
    id: number;
    title: string;
    course: Course | null;
}

export interface Lesson {
    id: number;
    title: string;
    module: Module | null;
}

export type SessionType = 'online' | 'physical';

export type SessionStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled';

export interface SessionParticipant {
    id: number;
    name: string;
    code: string | null;
    language?: string | null;
}

export interface ClassSession {
    id: number;
    type: SessionType;
    status: SessionStatus;
    scheduled_at: string;
    join_url?: string;
    start_url?: string;
    lesson: Lesson;
    teacher?: SessionParticipant;
    student?: SessionParticipant;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        links: PaginationLink[];
        total: number;
    };
}
