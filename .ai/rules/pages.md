---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Stripe Checkout redirects need a plain &lt;a&gt;, not Inertia &lt;Link&gt;
Routes that return a Cashier `Checkout` response (redirect to Stripe's hosted page) must be linked to with a native `<a href="...">` (e.g. `<Button as="a" :href="route(...)">`), never Inertia's `<Link>`. `<Link>` intercepts the click and does an XHR-based "visit" even for plain GETs, which can't navigate the browser off-site to Stripe. Same reasoning as the existing Zoom join_url/start_url links in NextSessionCard.vue — follow that pattern for any other route that hands off to an external host.
