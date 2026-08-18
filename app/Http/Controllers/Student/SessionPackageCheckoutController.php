<?php

namespace App\Http\Controllers\Student;

use App\Enums\SessionPackagePurchaseStatus;
use App\Http\Controllers\Controller;
use App\Models\SessionPackage;
use App\Models\SessionPackagePurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Checkout;

class SessionPackageCheckoutController extends Controller
{
    /**
     * Start a Stripe Checkout session for the given package. A plain
     * (non-Inertia) navigation, since the browser needs to leave the app
     * for Stripe's hosted checkout page.
     */
    public function store(Request $request, SessionPackage $sessionPackage): Checkout
    {
        abort_unless($sessionPackage->active && $sessionPackage->stripe_lookup_key, 404);

        $purchase = $request->user()->sessionPackagePurchases()->create([
            'session_package_id' => $sessionPackage->id,
            'status' => SessionPackagePurchaseStatus::Pending,
            'session_count' => $sessionPackage->session_count,
            'price_cents' => $sessionPackage->price_cents,
            'currency' => $sessionPackage->currency,
            // Redeemed once the purchase is actually marked paid, not
            // here — see SessionPackagePurchase::markAsPaid().
            'referral_code' => $request->filled('referral_code')
                ? strtoupper($request->string('referral_code')->value())
                : null,
        ]);

        $checkout = $request->user()->checkout([$sessionPackage->resolveStripePriceId() => 1], [
            'success_url' => route('session-packages.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('session-packages.checkout.cancel', $purchase),
            'metadata' => ['session_package_purchase_id' => $purchase->id],
            'allow_promotion_codes' => true,
        ]);

        // Recorded up front (not just by the webhook) so a stuck/undelivered
        // webhook still leaves admins something to verify against in Stripe.
        $purchase->update(['stripe_checkout_session_id' => $checkout->asStripeCheckoutSession()->id]);

        return $checkout;
    }

    /**
     * Landing page after a successful Stripe Checkout. Crediting the
     * student's ledger happens via the Stripe webhook, not here — the
     * customer may close this tab before it loads.
     */
    public function success(): Response
    {
        return Inertia::render('student/SessionPackageCheckoutResult', [
            'outcome' => 'success',
        ]);
    }

    /**
     * The customer backed out of Stripe Checkout — mark the pending
     * purchase as cancelled so it doesn't linger indefinitely.
     */
    public function cancel(SessionPackagePurchase $purchase): RedirectResponse
    {
        if ($purchase->status === SessionPackagePurchaseStatus::Pending) {
            $purchase->update(['status' => SessionPackagePurchaseStatus::Cancelled]);
        }

        return redirect()->route('session-packages.index');
    }
}
