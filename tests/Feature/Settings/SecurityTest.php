<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /* @chisel-2fa */
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);
        /* @end-chisel-2fa */
        /* @chisel-passkeys */
        Features::passkeys([
            'confirmPassword' => true,
        ]);
        /* @end-chisel-passkeys */
    }

    public function test_security_settings_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            /* @chisel-password-confirmation */
            ->withSession(['auth.password_confirmed_at' => time()])
            /* @end-chisel-password-confirmation */
            ->get(route('security.edit'));

        $response->assertOk();

        /* @chisel-passkeys */
        $response->assertSee('Passkeys');
        $response->assertSee('No passkeys yet');
        /* @end-chisel-passkeys */
        /* @chisel-2fa */
        $response->assertSee('Two-factor authentication');
        $response->assertSee('Enable 2FA');
        /* @end-chisel-2fa */
    }

    /* @chisel-password-confirmation */
    public function test_security_settings_page_requires_password_confirmation_when_enabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }
    /* @end-chisel-password-confirmation */

    public function test_security_settings_page_renders_without_two_factor_when_feature_is_disabled(): void
    {
        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            /* @chisel-password-confirmation */
            ->withSession(['auth.password_confirmed_at' => time()])
            /* @end-chisel-password-confirmation */
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Mot de passe')
            ->assertSee('Envoyer le lien de réinitialisation')
            ->assertDontSee('Manage your passkeys for passwordless sign-in')
            ->assertDontSee('Add a passkey to sign in without a password')
            ->assertDontSee('Two-factor authentication');
    }

    public function test_two_factor_authentication_disabled_when_confirmation_abandoned_between_requests(): void
    {
        /* @chisel-2fa */
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->actingAs($user);

        $component = Livewire::test('pages::settings.security');

        $component->assertSet('twoFactorEnabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
        /* @end-chisel-2fa */
    }

    public function test_admin_is_redirected_to_dashboard_after_confirming_two_factor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $confirm = Mockery::mock(ConfirmTwoFactorAuthentication::class);
        $confirm->shouldReceive('__invoke')->once();
        $this->app->instance(ConfirmTwoFactorAuthentication::class, $confirm);

        Livewire::test('pages::settings.two-factor-setup-modal', ['requiresConfirmation' => true])
            ->set('code', '123456')
            ->call('confirmTwoFactor')
            ->assertRedirect(route('admin.index'));
    }

    public function test_security_settings_offers_a_password_reset_link_by_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee($user->email);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_link_is_not_sent_to_an_unknown_email_address(): void
    {
        Notification::fake();

        User::factory()->create();

        $this->post(route('password.email'), ['email' => 'inconnu@example.com']);

        Notification::assertNothingSent();
    }
}
