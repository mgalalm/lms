<?php

namespace Database\Seeders;

use App\Models\SessionPackage;
use Illuminate\Database\Seeder;

class SessionPackageSeeder extends Seeder
{
    /**
     * Seed a few purchasable session packages so the marketing Pricing
     * section and the student storefront have something to show. The
     * stripe_price_id values are placeholders — replace them with real
     * Stripe Price IDs (via /admin) before checkout can actually complete.
     */
    public function run(): void
    {
        SessionPackage::firstOrCreate(
            ['name' => 'Starter Pack'],
            [
                'description' => 'Perfect for trying things out.',
                'session_count' => 5,
                'price_cents' => 7500,
                'currency' => 'eur',
                'stripe_price_id' => 'price_placeholder_starter',
                'active' => true,
            ]
        );

        SessionPackage::firstOrCreate(
            ['name' => 'Standard Pack'],
            [
                'description' => 'Our most popular option.',
                'session_count' => 10,
                'price_cents' => 14000,
                'currency' => 'eur',
                'stripe_price_id' => 'price_placeholder_standard',
                'active' => true,
            ]
        );

        SessionPackage::firstOrCreate(
            ['name' => 'Value Pack'],
            [
                'description' => 'Best value for regular sessions.',
                'session_count' => 20,
                'price_cents' => 26000,
                'currency' => 'eur',
                'stripe_price_id' => 'price_placeholder_value',
                'active' => true,
            ]
        );
    }
}
