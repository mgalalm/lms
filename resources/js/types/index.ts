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
    impersonator: { name: string } | null;
    flash: {
        lessonQuizResult: LessonQuizResult | null;
        moduleQuizResult: ModuleQuizResult | null;
        success: string | null;
        error: string | null;
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
    grade_band: string;
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
    recap_url?: string | null;
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
    rating: number | null;
    comment: string | null;
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
    grade_band: string;
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
    has_video: boolean;
    slideshow: { google_slides_id: string } | null;
}

export interface LmsModuleQuiz {
    /** Whether any lesson in the module has published questions to draw from. */
    is_ready: boolean;
    bank_size: number;
    certified: boolean;
    lessons_complete: boolean;
    can_attempt: boolean;
/** True only on the render right after Start; leaving the page clears it. */
    is_active: boolean;
    attempts_remaining: number;
    passing_score: number;
    /** Only populated while is_active — drawn fresh on each Start. */
    questions: { id: number; question_text: string; options: string[] }[];
}

export interface ModuleQuizResult {
    module_id: number;
    score: number;
    passed: boolean;
    correct: number;
    total: number;
    attempts_remaining: number;
}

export interface LmsModule {
    id: number;
    title: string;
    quiz: LmsModuleQuiz;
    lessons: LmsLesson[];
}

export interface LmsCourseDetail {
    id: number;
    grade_band: string;
    thumbnail_url: string | null;
    progress_percentage: number;
    is_certified: boolean;
    modules: LmsModule[];
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
    video: { duration_seconds: number | null } | null;
    project: LessonProjectSummary | null;
    quiz: LessonQuizSummary | null;
}

export interface LessonProjectSummary {
    id: number;
    title: string;
    brief: string | null;
    is_required: boolean;
}

export interface LessonQuizSummary {
    id: number;
    questions: LessonQuizQuestionSummary[];
}

export interface LessonQuizQuestionSummary {
    id: number;
    question_text: string;
    type: 'multiple_choice' | 'true_false';
    options: string[];
}

export interface LessonQuizResult {
    score: number;
    correct: number;
    total: number;
    results: {
        id: number;
        submitted_answer: string | null;
        correct_answer: string;
        explanation: string | null;
        is_correct: boolean;
    }[];
}

export type ProjectSubmissionStatus = 'submitted' | 'reviewed';

export type FeedbackSentiment = 'positive' | 'neutral' | 'needs_improvement';

export interface ProjectScreenshot {
    id: number;
    url: string;
}

export interface ProjectFeedback {
    id: number;
    rating: number;
    comment: string;
    is_featured: boolean;
    reviewed_at: string;
    teacher?: { id: number; name: string };
}

export interface ProjectSubmission {
    id: number;
    title: string;
    description: string | null;
    live_url: string | null;
    status: ProjectSubmissionStatus;
    submitted_at: string;
    screenshots: ProjectScreenshot[];
    feedback: ProjectFeedback | null;
    lesson_project?: { id: number; title: string; brief: string | null; is_required: boolean; lesson?: Lesson };
    student?: SessionParticipant;
}

export interface FeedbackTemplate {
    id: number;
    label: string;
    body: string;
    sentiment: FeedbackSentiment;
}

export type TeacherTaskType = 'session_prep' | 'session_review' | 'manual';

export interface TeacherTask {
    id: number;
    title: string;
    description: string | null;
    type: TeacherTaskType;
    due_at: string;
    completed_at: string | null;
    is_overdue: boolean;
    class_session_id: number | null;
    lesson_id: number | null;
    lesson?: Lesson | null;
}

export type TeachingFeedItemType = 'session' | 'project' | 'task' | 'ticket';

export interface TeachingFeedItem {
    id: string;
    type: TeachingFeedItemType;
    is_overdue: boolean;
    at: string;
    session: ClassSession | null;
    project: ProjectSubmission | null;
    task: TeacherTask | null;
    ticket: SupportTicket | null;
}

export interface TeachingFeedGroup {
    key: 'needs_attention' | 'today' | 'awaiting_review' | 'this_week';
    label: string;
    items: TeachingFeedItem[];
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
