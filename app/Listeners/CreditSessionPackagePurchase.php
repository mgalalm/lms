<?php

namespace App\Listeners;

use App\Enums\SessionPackagePurchaseStatus;
use App\Models\SessionPackagePurchase;
use Laravel\Cashier\Events\WebhookReceived;

class CreditSessionPackagePurchase
{
    /**
     * Credit a student's session ledger once Stripe confirms their
     * package purchase was paid.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'checkout.session.completed') {
            return;
        }

        $session = $event->payload['data']['object'];

        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $purchaseId = $session['metadata']['session_package_purchase_id'] ?? null;

        if (! $purchaseId) {
            return;
        }

        $purchase = SessionPackagePurchase::with('user')->find($purchaseId);

        if (! $purchase || $purchase->status === SessionPackagePurchaseStatus::Paid) {
            return;
        }

        $purchase->markAsPaid($session['id']);
    }
}
