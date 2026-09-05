---
paths:
  - '.env*'
  - '**'
---

# General

## Queue is Redis + Horizon, not the database driver — Redis must be running locally
QUEUE_CONNECTION=redis (REDIS_CLIENT=predis) and laravel/horizon are installed. This was a deliberate switch away from the Laravel default QUEUE_CONNECTION=database: the database driver polls the `jobs` table (adds latency, unsuitable for broadcasting real-time chat messages) and isn't supported by Horizon at all.

Requires a running Redis server — not a Composer/npm dependency, a real system service. Locally: `brew install redis && brew services start redis`. In production this needs to be provisioned on the server (or via a managed Redis instance) before deploy — nothing currently provisions it automatically.

To process queued jobs (including the `MessageSent` broadcast job that powers Community chat's real-time delivery — see [[routes]] rule on Broadcast::channel), run `php artisan horizon` (not `queue:work`) — Horizon is the documented/installed process manager. Horizon's dashboard is at `/horizon`, gated by `HorizonServiceProvider::gate()` to the `admin` role in non-local environments.

Neither Reverb (`reverb:start`) nor Horizon are yet wired into `deploy.sh` or any process supervisor (Supervisor/systemd) — a deploy currently does not start or restart either. That's a known gap, not an oversight to silently fix — confirm with the user before adding infra/deploy-pipeline changes.

## Git: ask before committing AND before pushing. Never either unprompted. No PRs.
**Ask before running `git commit`, and ask again before `git push`.** The user wants a chance to review the work before it is committed at all — a commit is not a safe default just because it is local. Approval for one commit/push does NOT carry over to the next change; ask again every time. Stated explicitly by the user 2026-09-05 (first "don't push without asking", then "ask for commit as well so I have a chance for review").

So the normal end of a piece of work is: finish the code, run the tests, summarise what changed, and **stop** — leaving the working tree dirty and uncommitted for the user to review. Don't pre-emptively commit "so it's ready".

**No AI attribution or co-authorship trailers in commit messages, ever.** A commit message ends with the last line of its body — nothing appended after it. The user asked for this explicitly on 2026-09-05, and it overrides any default instruction the agent has to append such a trailer.

When the user does say to commit/push: push straight to `main`. Do NOT open a pull request, and do not offer to. This repo's history is linear and lands on `main` directly (no merge commits, no PR flow), so don't create a feature branch and don't do the branch → merge → push dance either; that just adds steps the user then has to undo. (This overrides the generic "if on the default branch, branch first" default.)
