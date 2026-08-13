# Teacher Dashboard

### Top Navigation
- **Teaching** — main hub for lesson/session/task management (see below)
- **LMS** — access to learning management/content area (see below)
- **Insights** — teacher's own performance/analytics dashboard (see below)
- **Requests** — submit and track HR/operational requests (see below)
- **Community** — teacher-side messaging (see below)
- **Notifications bell** — shows unread count (e.g. "317")
- **Teacher profile** — name + teacher ID shown top-right (e.g. "Mohamed Mostafa — T-10865")

### Teacher Profile Dropdown
- **Profile card** — shows teacher photo, ID (e.g. "#T-10865"), points/coins (e.g. "17550"), name, and organization + rank/level (e.g. "GenAcademy (R 3)")
- **Menu items**:
  - **Profile** — view/edit teacher profile
  - **Credentials** — linked third-party account logins (see below)
  - **Payroll** — access to pay/earnings info (see below)
  - **Policies & Agreements** — reference hub for internal rules (see below)
  - **Reports** — likely performance or session reports
  - **Logout**

### Teacher Profile Page
- **Header** — photo (uploadable), teacher ID, full name, rank badge (e.g. "R 3")
- **Physical Sessions Check In/out** — separate check-in and check-out links, each with a "Copy" action (for in-person session attendance tracking)
- **Information section** — contact and background fields: email, phone, starting date, language(s), faculty of graduation, year of graduation, employment type (e.g. "Full time")
- **Team Affiliations section** — Tutor Team ID, Team Leader name, Mentor name
- **Payroll Info section** — bank name, account number

### Teacher Credentials Page
- **Linked platform accounts** — cards for third-party platforms/tools the teacher needs for tutoring, each showing:
  - Platform icon/logo
  - Associated login email (system-provisioned, e.g. an @ischoolteams.com address)
  - Masked password field
  - "Visit" button to open the platform directly
- **Platforms observed**: Bluejeans/mail-style video tool, an internal "Platform" login, Microsoft, and Minecraft (likely for game-based coding lessons)
- Suggests the LMS centralizes credential access so teachers don't need to separately manage logins for each teaching tool

### Teacher Payroll Page
- **Monthly payroll table** — one row per month, columns:
  - **Month** (e.g. "Aug 2026")
  - **Organization** (e.g. "GenAcademy")
  - **Count** — likely number of sessions taught that month
  - **Basic Salary** — flat monthly base pay
  - **Bonus/Deduction** — separate positive (bonus) and negative (deduction) amounts, each with a "View Details" link
  - **Net Amount** — total after bonus/deduction
  - **Status** — e.g. "Pending"
  - **Bank Info** — bank name shown per row
  - **Completed at** — payout completion date (or "-" if not yet paid)
  - **Action** — "Session History" link per month (and a document/export icon on some rows)
- Currency used: **EGP** (Egyptian Pound)
- Pay appears tied to session count, with bonuses/deductions layered on top of a fixed base salary

### Teacher Policies & Agreements Page
- **Reference card grid** — each card links to rules/policies for a specific area of teacher operations:
  - **Leaves** — rules and policies of leaves
  - **Meetings** — rules and policies of meeting requests
  - **HR Requests** — rules and policies of HR requests
  - **Schedule Change** — rules and policies of schedule change requests
  - **Sessions** — rules and policies of sessions
  - **Community** — rules and policies of the community/chat space
  - **Support** — rules and policies of support requests
  - **Resignation** — rules and policies of resignations
- Each card has an icon, title, and short one-line description
- Acts as a self-serve knowledge base for teacher HR/operational policies, and pairs with the **Requests** nav item where the corresponding requests are actually submitted

#### Policy Detail View (e.g. Leaves Policies)
- **Two-pane layout** — left sidebar lists sub-categories, right pane shows the selected policy's full text
- **Leaves sub-categories observed**: Annual Leave, Umrah/Hajj, Paternity Leave, Maternity Leave, Marriage Leave, Sick Leave, Excused Time, Emergency Leave, Death/Condolence Leave, No Show, Public Holidays
- **Annual Leave Policy example** — illustrates the level of detail provided:
  - Tutors get a fixed number of annual leave days after a probation period, split into emergency leave (small per-year allotment, capped per month) and planned leave (larger allotment, capped consecutive days within a rolling window)
  - Restrictions on taking the same weekday off repeatedly within a set cooldown period
  - No leave allowed during probation except emergencies
  - Planned leave requires advance notice (measured in working days) with a defined SLA for approval response
  - **All leave requests must be submitted through the dashboard only** — no other channel is accepted
- Confirms the platform is also a lightweight **HR/workforce management tool** for tutors, not just a teaching tool

### Teacher Insights Page
- **Date range selector** — e.g. "26 Jun 2026 - 25 Jul 2026" with a preset dropdown (e.g. "Last Month")
- **Left profile panel** — teacher photo, points badge, name, ID, and two stat tiles: total **Sessions** taught and **Certificates** earned
- **Metric tiles** (top row, per selected period):
  - **Objections** — count of formal objections raised (e.g. by students/parents)
  - **Flags** — count of flags raised on the teacher's sessions
  - **Excuses** — count of excused absences/late check-ins
  - **Study Level** — unclear metric, shown as a count
- **Attendance metric tiles**:
  - **Check-in on Time** vs **Check-in Late** — counts of on-time vs late session check-ins
  - **Join Sessions on Time** vs **Join Sessions Late** — counts of on-time vs late session joins
- **Performance Insights chart** — bar chart scoring the teacher (0–100%) across categories: **Feedback, Attitude, Preparation, Curriculum, Teaching, Setup**
- **Flags Breakdown** — counts by severity: **Green, Yellow, Red**
- **Total Score** — an overall percentage score (e.g. "95%") with a celebratory clapping-hands graphic when performance is strong
- **Reviewed Sessions list** — filterable by All / Green Flags / Yellow Flags / Red Flags, with a search bar (search by Student ID/Session Name); each entry shows lesson name, date/time, student name, and organization
- This page functions as the teacher's **performance scorecard**, likely feeding into their rank (e.g. "R 3") and payroll bonuses/deductions

### Teaching — Home
- **Sub-nav tabs** — Sessions Schedule, Projects, Tasks (with a pending count badge, e.g. "1 Pending")
- **Today's Sessions panel** — toggle between One-to-one and Groups; shows an empty state ("Your sessions list is empty") when there's nothing scheduled for today
- **Today's Tasks panel** — lists tasks due for the teacher, e.g. "Review Project – turtle: Please review [Student]'s project for turtle," with a **Due Date** and status badge (e.g. "Overdue")
- Confirms teachers have their own task list separate from the tasks/homework they assign to students

### Teaching — Sessions Schedule
- **Toggle** — One-to-one vs Groups sessions
- **Filter tabs** — Today / Upcoming / Past
- **Date picker** and **search bar** — search by Student Name/ID
- **Support History** link — top-right, likely a log of past support interactions
- **Sessions grouped by day** (e.g. "Saturday," "Sunday")
- **Each session row shows**:
  - Lesson title (e.g. "L5: UX Process")
  - Student name + ID, language
  - Countdown or scheduled time (e.g. "2 Days 19 Hrs 3 Mins" for the very next one, or a fixed date/time for others)
  - A **Join** button (enabled only when the session is imminent)
  - Overflow menu (⋮) for more actions
- **Expanded row detail** (seen on the next upcoming session) includes:
  - Grade/Module/Lesson path
  - Session **Type** (e.g. "Normal")
  - **Session ID**
  - **Assigned on** date/time
  - **Tags** — e.g. "Instructor Change," "Shift Complain" (flags context about the session, shown as colored pills)
  - **Student History** link
- Shows the teacher can see a full rolling schedule across multiple students/grades, with tagging used to flag anomalies (like an instructor change or a complaint) directly on the session row

### LMS (Nav Tab)
- **Status filter tabs** — In Progress / Not Started / Completed
- **Content organized by grade band** — e.g. "Grade 1 - 2," with course cards underneath each heading
- **Course card** — thumbnail image, title (e.g. "M4: Space Exploration with Minecraft"), completion percentage (e.g. "75% Completed"), lesson count (e.g. "12 Lessons"), a progress bar, and a status pill (e.g. "Quiz Pending")
- **Resources** — top-right link/button, likely a shared library of teaching materials
- Confirms course content is built around **modules with a Minecraft/game-based theme** for younger grades (mirrors the "M4: Space Exploration" and earlier-seen Minecraft credential)
- This is distinct from **Teaching → Materials** (session-specific) — the LMS tab appears to be the teacher's view of the full curriculum library across their assigned grades/modules, tracked by completion status
- **Certification requirement**: teachers must complete a module/curriculum themselves (as a learner, including its quiz) before they're allowed to teach it to students. Completing it awards a **certificate**, which is what the **Certificates** stat tile on the Insights page counts (e.g. "19"). This turns the LMS tab into a prerequisite gate for teaching eligibility, not just a reference library — a teacher's course-completion status here likely determines which sessions/grades they can be assigned to in Teaching → Sessions Schedule

### Requests (Nav Tab)
- **Request type cards** — teacher can initiate a new request from a set of cards, each with icon, title, and short description:
  - **Leave Request** — planned or emergency leave request
  - **Excused Time** — absence for valid reasons
  - **HR Request** — raise and manage HR tickets
  - **Support** — submit a ticket, inquiry, or recommendation
  - **Resignation** — stepping down from position
- **History section** — below the request cards, tabbed by type (Leave Request, Excused Time, HR Request, Support, Resignation)
- **History list (Leave Request example)** — each entry shows:
  - Request type/subtype and duration (e.g. "Planned Leaves · 1 Day(S)" or "Sick · 1 Day(S)")
  - The date the leave covers (in parentheses)
  - **Submitted** timestamp
  - **Status** badge (e.g. "Approved")
- **Pagination** — numbered pages for browsing older history
- Confirms **Requests** is the action/submission counterpart to the read-only **Policies & Agreements** reference pages — each policy category (Leaves, HR Requests, Support, Resignation, etc.) has a matching request type here
- Notably, **Meetings** and **Schedule Change** appear as policy categories but were not seen as request card types in this view — may be nested elsewhere or missing from the card grid

#### Leave Request Form
- **Leave type toggle** — Planned Leaves / Emergency / Sick (radio-style selector)
- **Type dropdown** — sub-reason within the selected leave type (e.g. "Vacation")
- **Start Date** — date picker (calendar widget, e.g. showing August 2026 with the current day highlighted)
- **Additional fields below** (partially visible) — likely End Date and a reason/notes textarea
- **File attachment** — "Choose File, or drag and drop" uploader (e.g. for supporting documents like a sick note)
- **Submit button**
- **Request Info side panel**:
  - **Credits** — live leave balance tracker, e.g. "Monthly Limit: 1/5 Days" and "Annual: 7/15 Days"
  - **Policy summary** — short description of the selected leave type with a "Check [X] Policy" link back to the full Policies & Agreements detail page
  - **Processing Time** — SLA shown inline (e.g. "3 Working Days")
- Shows the request form dynamically pulls in the relevant policy rules and the teacher's real-time leave balance, tying the Requests, Policies, and (likely) Payroll/HR systems together

### Next Session Bar
- **Countdown timer** — days/hrs/mins to next session, with date and time (e.g. "8 Aug, 3:00 PM GMT+3")
- **Student info inline** — shows student name + ID (e.g. "Selim Elkhergawy (S-271873)"), language (e.g. "English"), and current lesson path (e.g. "Grade 5-6/Module 3/Lesson 5: L5 UX Process")
- **Join Now** button — opens the session directly in **Zoom**
- **Ping Parent** button — quick action to notify the student's parent
- **Materials** button — likely opens lesson materials/resources for the session

### Community (Teacher side)
- **Channel tabs** — All / Unread (count) / Late (count) — helps teacher prioritize which student chats need a response
- **Channel list** — includes group channels (e.g. "Birthdays," "Sub team education") each showing last activity time
- **Direct Messages** — list of individual student channels (e.g. "S-85503 Channel," "S-365862 Channel"), each with last activity time, allowing the teacher to message students directly (e.g. to send homework/tasks)
- **Search** — search bar within Direct Messages
- **Load More** — pagination for the channel list
- **Empty state** — "Select a channel" placeholder shown before a channel is opened
- **Profile button** — shown in the open channel header, likely opens the student's profile

### Community — Conversation View (shared by both roles)
- **Role tags on messages** — each sender shows their name, role (e.g. "Student" or "Tutor"), and ID (e.g. "Selim Elkhergawy — Student — S-271873"; "Mohamed Mostafa — Tutor — T-10865")
- **Timestamps** — shown per message (e.g. "3:57 PM")
- **Date separators** — conversation is grouped by date (e.g. "Saturday, July 4th, 2026")
- **Students can message freely** — e.g. sharing general knowledge/notes unrelated to a task (seen: notes about messenger apps and Twitter's rebrand)
- **Rich task/reminder cards** — teacher can send a styled card (not just plain text) with a title (e.g. "Reminder: for next time"), a colored accent bar, an icon, and a thumbnail/screenshot attached (e.g. "Apply Low Fidelity Wireframe to YouTube website screenshot above")
- **Students can ask questions in-channel** — e.g. reporting a tooling issue ("when I try to open Figma it tells me I need to upgrade, what should I do?")
- **Message hover actions** — react (emoji), reply, and share/forward icons appear on hover
- **Channel member icon** — small icon top-right of the open channel (likely shows member list/count)

See also: [Overview](./01-overview.md), [Features](./02-features.md), [Student Dashboard](./04-student-dashboard.md)
