<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => true,
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_registration_requires_privacy_consent(): void
    {
        $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'sans-consentement@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('privacy_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'sans-consentement@example.com']);
    }

    public function test_registration_stores_the_consent_timestamp_and_policy_version(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'consentement@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => true,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'consentement@example.com')->firstOrFail();
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(config('privacy.version'), $user->privacy_policy_version);
    }

    public function test_the_privacy_policy_page_is_public(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Politique de confidentialité');
    }
}
