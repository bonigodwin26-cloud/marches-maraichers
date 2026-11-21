<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Market;
use App\Models\PriceObservation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_buyers_are_redirected_to_their_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_collector' => false]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('buyer.dashboard'));
    }

    public function test_collectors_are_redirected_to_the_collection_dashboard(): void
    {
        $collector = User::factory()->create(['is_admin' => false, 'is_collector' => true]);
        $this->actingAs($collector);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('collector.dashboard'));
    }

    public function test_admins_must_confirm_two_factor_authentication(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get(route('admin.index'));

        $response->assertRedirect(route('security.edit'));
    }

    public function test_a_buyer_dashboard_shows_his_requests_and_price_alerts(): void
    {
        $buyer = User::factory()->create();
        [$product, $market] = $this->catalogue();
        ContactRequest::create([
            'buyer_id' => $buyer->id, 'product_id' => $product->id, 'market_id' => $market->id,
            'phone' => '+229012345678', 'status' => 'pending',
        ]);

        $this->actingAs($buyer)->get(route('buyer.dashboard'))
            ->assertOk()
            ->assertSee('buyer-dashboard', false)
            ->assertSee('requestCounts', false)
            ->assertSee('Tomate', false)
            ->assertSee('Dantokpa', false);
    }

    public function test_a_regular_user_cannot_open_the_collection_dashboard(): void
    {
        $buyer = User::factory()->create(['is_collector' => false]);

        $this->actingAs($buyer)->get(route('collector.dashboard'))->assertForbidden();
    }

    public function test_a_collector_dashboard_shows_only_his_observations(): void
    {
        $collector = User::factory()->create(['is_collector' => true]);
        $other = User::factory()->create(['is_collector' => true]);
        [$product, $market] = $this->catalogue();

        PriceObservation::create([
            'product_id' => $product->id, 'market_id' => $market->id, 'collected_by' => $collector->id,
            'price_xof' => 12000, 'unit_snapshot' => 'kg', 'observed_at' => now(), 'status' => 'pending',
        ]);
        PriceObservation::create([
            'product_id' => $product->id, 'market_id' => $market->id, 'collected_by' => $other->id,
            'price_xof' => 99000, 'unit_snapshot' => 'kg', 'observed_at' => now(), 'status' => 'published',
        ]);

        $response = $this->actingAs($collector)->get(route('collector.dashboard'))
            ->assertOk()
            ->assertSee('collector-dashboard', false);

        $response->assertViewHas('props.counts.all', 1)
            ->assertViewHas('props.observations', fn ($observations): bool => $observations->count() === 1
                && $observations->first()['price_xof'] === 12000);
    }

    public function test_a_collector_can_submit_a_price_which_stays_pending(): void
    {
        $collector = User::factory()->create(['is_collector' => true]);
        [$product, $market] = $this->catalogue();

        $this->actingAs($collector)->post(route('collector.prices.store'), [
            'product_id' => $product->id,
            'market_id' => $market->id,
            'price_xof' => 25000,
            'observed_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('collector.dashboard'));

        $this->assertDatabaseHas('price_observations', [
            'collected_by' => $collector->id,
            'price_xof' => 25000,
            'unit_snapshot' => 'kg',
            'status' => 'pending',
        ]);
    }

    public function test_an_admin_can_grant_the_collector_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'two_factor_confirmed_at' => now()]);
        $buyer = User::factory()->create(['is_admin' => false, 'is_collector' => false]);
        $this->actingAs($admin);

        $this->patch(route('admin.collectors.toggle', $buyer))->assertRedirect();
        $this->assertTrue($buyer->fresh()->is_collector);

        $this->patch(route('admin.collectors.toggle', $buyer))->assertRedirect();
        $this->assertFalse($buyer->fresh()->is_collector);
    }

    /** @return array{Product, Market} */
    private function catalogue(): array
    {
        $product = Product::create(['name' => 'Tomate', 'slug' => 'tomate-test', 'unit' => 'kg', 'is_active' => true]);
        $market = Market::create(['name' => 'Dantokpa', 'commune' => 'Cotonou', 'is_active' => true]);

        return [$product, $market];
    }
}
