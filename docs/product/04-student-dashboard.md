# Student Dashboard

### Home Page
- **Top navigation** — Home, Journey, Achievements, Portfolio, Community
- **Notifications bell** and **language toggle** (English/Arabic)
- **Points/rewards badge** — visible score shown near profile icon
- **User profile** — name + student ID shown top-right
- **Toast confirmations** — e.g. "Session Feedback Submitted" success banner
- **Next Session widget** — live countdown timer (days/hrs/mins) to next session, with date/time and a "Join" button
- **Current lesson card** — shows current module/lesson (e.g. "L11: Tkinter in Python") and topic (e.g. "M2: Chatbot Development with Data Encryption - Grade 5-6"), with tabs for:
  - **Recap** — includes a "Watch Recap" video player for the session
  - **Reading**
  - **Quiz**
- **Missed content reminder** — banner nudging students to review missed content, with a "My Progress" button

### Earnings & Gamification
- **Coins/Earnings widget** — shows total coins earned (e.g. "3315 Coins") with a "How to Earn?" link
- **Badges** — achievement badges displayed with icon, name, and short description (e.g. "Quiz Quester," "Python Chatbot Pioneer," "Time Legend"), with a "View All" option
- **Leaderboard** — ranks students by points (e.g. "#51 Me — 2890"), shows profile picture, rank, name, and score, with a "View All" option
- **My Progress widget** — shows current module (e.g. "M2: Chatbot Development with Data Encryption - Grade 5-6") with an icon, a progress bar, and completion count (e.g. "11/12")
- **Latest Session Review** — shows most recent teacher/session review submitted by the student, or a "No review yet!" empty state with a star icon

### Post-Session Feedback
- **Rating popup** — appears after a session asking "What is your Feedback for the session?"
- **Default rating** — pre-filled at 3 stars / "Good" with a smiley emoji before the student adjusts it
- **Emoji sentiment indicator** — reflects rating chosen (e.g. smiley face for "Good")
- **5-star rating** — student rates the teacher/session from 1–5 stars
- **Submit button** — confirms and sends the feedback, followed by a success toast notification

### Community (Student side)
- **Channels** — group chats organized by context, e.g.:
  - "Announcements and..." (general announcements)
  - "Grade 5-6 - M1..." (module-specific channel)
  - "Grade 5-6 - M2..." (module-specific channel)
- **Channel activity indicators** — shows last activity time (e.g. "Now", "2H") with an unread/active dot
- **Direct Messages** — 1:1 or small-group chat, labeled with a student ID (e.g. "S-329323 Channel")
- **Member count** — shows total members in a channel (e.g. "16 Members")
- **Support button** — persistent link/button for contacting support from within Community

### Homework / Task Delivery
- **Tasks sent via chat** — the tutor posts homework/tasks directly as a message in the student's channel (e.g. S-329323 Channel), rather than a separate assignments module
- **Task message format** — includes a title (e.g. "Task"), sender name and role tag (e.g. "Mohamed Mostafa — Tutor"), and timestamp
- **Structured task content** — task descriptions can include:
  - Scenario/story framing (e.g. a friend needing help with a coding problem)
  - Expected output snippet (e.g. sample program output)
  - Inline code formatting (e.g. highlighted `strftime(...)` function reference)
  - Warning/hint icons (⚠️) to flag important notes
  - Step-by-step guidance/sub-instructions
- **Rich text chat composer** — supports Bold, Italic, Underline, Strikethrough, Quote, Code block, ordered/unordered lists, emoji, and text color
- **Attachments** — "+" icon for adding files/attachments to a message
- **Send controls** — "Press Enter to send or Shift+Enter for a new line," plus a "Send" button

See also: [Overview](./01-overview.md), [Features](./02-features.md), [Teacher Dashboard](./05-teacher-dashboard.md)
