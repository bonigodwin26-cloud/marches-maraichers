<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_does_not_see_the_admin_navigation_in_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Bénin · Administration')
            ->assertSee('Bénin · Mon compte')
            ->assertDontSee('>Tableau de bord<', false);
    }

    public function test_a_collector_sees_the_collection_navigation_in_settings(): void
    {
        $collector = User::factory()->create(['is_admin' => false, 'is_collector' => true]);

        $this->actingAs($collector)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Bénin · Collecte')
            ->assertSee(route('collector.dashboard'), false)
            ->assertDontSee('Bénin · Administration');
    }

    public function test_an_admin_sees_the_admin_navigation_in_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Bénin · Administration')
            ->assertSee('Tableau de bord');
    }
}
