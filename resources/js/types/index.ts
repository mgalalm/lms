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
    flash: {
        quizResult: { score: number; passed: boolean } | null;
        success: string | null;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
    unreadNotificationsCount: number;
    notifications?: AppNotification[];
}

export interface AppNotification {
    id: string;
    read_at: string | null;
    created_at: string;
    title: string;
    message: string;
    url: string | null;
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
    latest_support_ticket?: { id: number; status: 'open' | 'resolved' } | null;
}

export interface SupportTicket {
    id: number;
    code: string;
    subject: string;
    description: string;
    status: 'open' | 'resolved';
    channel_id: number;
    created_at: string;
    resolved_at: string | null;
    class_session: { id: number; lesson_title: string | null } | null;
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

export type ChannelType = 'direct' | 'group';

export interface Channel {
    id: number;
    type: ChannelType;
    name: string | null;
    code: string | null;
    member_count: number;
    last_message: { preview: string; created_at: string } | null;
    unread_count: number;
}

export type MessageType = 'text' | 'task_card';

export interface MessageAttachment {
    path: string;
    url: string;
    filename: string;
    mime: string;
    size: number;
}

export interface MessageTaskMeta {
    title: string;
    accent_color?: string;
    icon?: string;
}

export interface MessageUser {
    id: number;
    name: string;
    role: string | null;
    code: string | null;
}

export type MessageModerationStatus = 'pending' | 'approved' | 'flagged';

export interface Message {
    id: number;
    channel_id: number;
    type: MessageType;
    body: string | null;
    meta: MessageTaskMeta | null;
    attachments: MessageAttachment[] | null;
    moderation_status: MessageModerationStatus;
    created_at: string;
    user: MessageUser;
}

export type CourseProgressStatus = 'not_started' | 'in_progress' | 'quiz_pending' | 'completed';

export type LmsFilter = 'all' | 'not_started' | 'in_progress' | 'completed';

export interface LmsCourseCard {
    id: number;
    title: string;
    grade_band: string | null;
    thumbnail_url: string | null;
    lessons_count: number;
    completed_lessons_count: number;
    progress_percentage: number;
    status: CourseProgressStatus;
    status_label: string;
}

export interface LmsGradeBand {
    grade_band: string;
    courses: LmsCourseCard[];
}

export interface LmsLesson {
    id: number;
    title: string;
    reading_content: string | null;
    completed: boolean;
}

export interface LmsModule {
    id: number;
    title: string;
    lessons: LmsLesson[];
}

export interface LmsQuizQuestion {
    id: number;
    question: string;
    options: string[];
}

export interface LmsQuiz {
    passing_score: number;
    questions: LmsQuizQuestion[];
}

export interface LmsCourseDetail {
    id: number;
    title: string;
    grade_band: string | null;
    thumbnail_url: string | null;
    progress_percentage: number;
    is_certified: boolean;
    can_take_quiz: boolean;
    modules: LmsModule[];
    quiz: LmsQuiz | null;
}

export type FlagSeverity = 'green' | 'yellow' | 'red';

export interface InsightsProfile {
    sessions_taught: number;
    certificates_earned: number;
    points: number;
}

export interface InsightsMetrics {
    objections: number;
    excuses: number;
    study_level: number;
}

export interface AttendanceBucket {
    on_time: number;
    late: number;
}

export interface InsightsAttendance {
    check_in: AttendanceBucket;
    join: AttendanceBucket;
}

export interface InsightsPerformance {
    categories: Record<string, number>;
    total_score: number;
}

export interface InsightsFlagsBreakdown {
    green: number;
    yellow: number;
    red: number;
}

export interface ReviewedSession {
    id: number;
    lesson_title: string | null;
    scheduled_at: string;
    student: SessionParticipant | null;
    flag_severity: FlagSeverity;
    star_rating: number;
    total_score: number;
    notes: string | null;
}

export interface Badge {
    id: number;
    name: string;
    description: string;
    icon: string;
    awarded_at: string | null;
}

export interface LeaderboardEntry {
    rank: number;
    id: number;
    name: string;
    points: number;
    is_current_user: boolean;
}

export type SessionSentiment = 'negative' | 'neutral' | 'positive';

export interface LatestSessionReview {
    star_rating: number;
    sentiment: SessionSentiment;
    lesson_title: string | null;
    teacher_name: string | null;
    submitted_at: string;
}

export interface CurrentLesson {
    id: number;
    title: string;
    reading_content: string | null;
    module_title: string | null;
    course_title: string | null;
}

export interface LessonProgress {
    module_title: string | null;
    completed: number;
    total: number;
}

export type PayrollAdjustmentType = 'bonus' | 'deduction';

export interface PayrollAdjustment {
    type: PayrollAdjustmentType;
    amount: number;
    reason: string;
}

export type PayrollStatus = 'pending' | 'paid';

export interface PayrollEntry {
    id: number;
    month: string;
    organization: string;
    sessions_count: number;
    basic_salary: number;
    bonus_total: number;
    deduction_total: number;
    net_amount: number;
    status: PayrollStatus;
    completed_at: string | null;
    adjustments: PayrollAdjustment[];
}

export type TeacherRequestType = 'leave' | 'excused_time' | 'hr_request' | 'support' | 'resignation';

export type TeacherRequestStatus = 'pending' | 'approved' | 'rejected';

export interface TeacherRequestPayload {
    leave_type?: 'planned' | 'emergency' | 'sick';
    sub_reason?: string;
    start_date?: string;
    end_date?: string;
    notes?: string;
}

export interface TeacherRequestHistoryItem {
    id: number;
    type: TeacherRequestType;
    payload: TeacherRequestPayload;
    has_attachment: boolean;
    status: TeacherRequestStatus;
    review_notes: string | null;
    submitted_at: string;
    reviewed_at: string | null;
}

export interface LeaveCreditBalance {
    monthly_limit: number;
    monthly_used: number;
    annual_limit: number;
    annual_used: number;
}

export interface HelpArticleSummary {
    slug: string;
    title: string;
    excerpt: string | null;
}

export interface HelpArticleDetail {
    slug: string;
    title: string;
    category: string;
    body_html: string;
}

export interface SessionPackage {
    id: number;
    name: string;
    description: string | null;
    session_count: number;
    price: number;
    currency: string;
}

export type SessionPackagePurchaseStatus = 'pending' | 'paid' | 'cancelled' | 'failed';

export interface SessionPackagePurchase {
    id: number;
    package_name: string;
    status: SessionPackagePurchaseStatus;
    session_count: number;
    price: number;
    currency: string;
    paid_at: string | null;
    created_at: string;
}
