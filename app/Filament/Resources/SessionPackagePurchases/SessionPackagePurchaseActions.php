<?php

namespace App\Filament\Resources\SessionPackagePurchases;

use App\Models\SessionPackagePurchase;
use Filament\Notifications\Notification;
use Laravel\Cashier\Cashier;

/**
 * Shared side effects for admin resolution actions, reused by both the
 * table's row actions and the ViewRecord page's header actions.
 */
class SessionPackagePurchaseActions
{
    /**
     * Look up the purchase's Stripe Checkout Session directly and credit
     * it only if Stripe actually shows it as paid — the safe way to
     * resolve a purchase stuck pending because a webhook never arrived
     * (e.g. it was created before a webhook endpoint existed, or Stripe
     * couldn't reach the app when it tried).
     */
    public static function verifyWithStripe(SessionPackagePurchase $purchase): void
    {
        if (! $purchase->stripe_checkout_session_id) {
            Notification::make()
                ->title('No Stripe Checkout Session on record')
                ->body('This purchase has nothing to verify against — use "Mark as Paid" instead if you\'ve confirmed payment some other way.')
                ->danger()
                ->send();

            return;
        }

        $session = Cashier::stripe()->checkout->sessions->retrieve($purchase->stripe_checkout_session_id);

        if ($session->payment_status !== 'paid') {
            Notification::make()
                ->title('Stripe still shows this as unpaid')
                ->body('Payment status: '.$session->payment_status)
                ->warning()
                ->send();

            return;
        }

        $purchase->markAsPaid($session->id);

        Notification::make()
            ->title('Verified with Stripe and credited')
            ->success()
            ->send();
    }

    /**
     * Manually mark a purchase paid and credit it without checking Stripe
     * — a deliberate escape hatch for cases with no Checkout Session on
     * record at all (e.g. confirmed paid via bank statement/support ticket).
     */
    public static function markAsPaidManually(SessionPackagePurchase $purchase): void
    {
        $purchase->markAsPaid();

        Notification::make()
            ->title('Marked as paid and credited')
            ->success()
            ->send();
    }
}
