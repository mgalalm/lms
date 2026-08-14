<?php

namespace Database\Seeders;

use App\Models\SessionPackage;
use Illuminate\Database\Seeder;

class SessionPackageSeeder extends Seeder
{
    /**
     * Seed a few purchasable session packages so the marketing Pricing
     * section and the student storefront have something to show. Each
     * stripe_lookup_key must match the lookup key set on a Price in the
     * Stripe dashboard — the actual Price ID is resolved from Stripe at
     * checkout time (see SessionPackage::resolveStripePriceId()), so it
     * never needs to be hardcoded here.
     *
     * Uses updateOrCreate (keyed by name) so re-running this after
     * changing a package's price or lookup key fixes existing rows too,
     * rather than only seeding once.
     */
    public function run(): void
    {
        SessionPackage::updateOrCreate(
            ['name' => 'Starter Pack'],
            [
                'description' => 'Perfect for trying things out.',
                'session_count' => 5,
                'price_cents' => 7500,
                'currency' => 'eur',
                'stripe_lookup_key' => 'starter_pack',
                'active' => true,
            ]
        );

        SessionPackage::updateOrCreate(
            ['name' => 'Standard Pack'],
            [
                'description' => 'Our most popular option.',
                'session_count' => 10,
                'price_cents' => 14000,
                'currency' => 'eur',
                'stripe_lookup_key' => 'standard_pack',
                'active' => true,
            ]
        );

        SessionPackage::updateOrCreate(
            ['name' => 'Value Pack'],
            [
                'description' => 'Best value for regular sessions.',
                'session_count' => 20,
                'price_cents' => 26000,
                'currency' => 'eur',
                'stripe_lookup_key' => 'value_pack',
                'active' => true,
            ]
        );
    }
}
