---
paths:
  - '.env*'
---

# General

## Queue is Redis + Horizon, not the database driver — Redis must be running locally
QUEUE_CONNECTION=redis (REDIS_CLIENT=predis) and laravel/horizon are installed. This was a deliberate switch away from the Laravel default QUEUE_CONNECTION=database: the database driver polls the `jobs` table (adds latency, unsuitable for broadcasting real-time chat messages) and isn't supported by Horizon at all.

Requires a running Redis server — not a Composer/npm dependency, a real system service. Locally: `brew install redis && brew services start redis`. In production this needs to be provisioned on the server (or via a managed Redis instance) before deploy — nothing currently provisions it automatically.

To process queued jobs (including the `MessageSent` broadcast job that powers Community chat's real-time delivery — see [[routes]] rule on Broadcast::channel), run `php artisan horizon` (not `queue:work`) — Horizon is the documented/installed process manager. Horizon's dashboard is at `/horizon`, gated by `HorizonServiceProvider::gate()` to the `admin` role in non-local environments.

Neither Reverb (`reverb:start`) nor Horizon are yet wired into `deploy.sh` or any process supervisor (Supervisor/systemd) — a deploy currently does not start or restart either. That's a known gap, not an oversight to silently fix — confirm with the user before adding infra/deploy-pipeline changes.
