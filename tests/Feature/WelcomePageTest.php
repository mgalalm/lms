<?php

namespace Tests\Feature;

use App\Models\SessionPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_lists_purchasable_session_packages(): void
    {
        $purchasable = SessionPackage::factory()->create(['name' => '10-Session Package']);
        SessionPackage::factory()->create(['active' => false, 'name' => 'Hidden Package']);
        SessionPackage::factory()->create(['stripe_price_id' => null, 'name' => 'Unpriced Package']);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('packages', 1)
                ->where('packages.0.name', $purchasable->name)
            );
    }

    public function test_welcome_page_renders_with_no_packages(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('packages', 0)
            );
    }
}
