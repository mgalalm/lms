---
paths:
  - 'app/Http/Resources/**'
---

# Resources

## JsonResource wrapping is disabled — resources are Inertia props, not JSON:API
AppServiceProvider::boot() calls JsonResource::withoutWrapping(). Without this, a single resource like `new ClassSessionResource($session)` passed as an Inertia prop serializes as `{"data": {...}}` instead of the flat object the Vue page expects — this bit us once (dashboard prop `nextSession.id` silently resolved to null because the real payload was `nextSession.data.id`).

Collections that need Laravel's pagination `data`/`links`/`meta` envelope (e.g. `ClassSessionResource::collection($sessions)->response()->getData(true)`, used for Sessions Schedule) are unaffected — `->response()` builds that envelope explicitly regardless of the global wrap setting.

Don't re-enable wrapping and don't add a `resource` key by hand to work around this — keep resources flat for single-resource Inertia props.
