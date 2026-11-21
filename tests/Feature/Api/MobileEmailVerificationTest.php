<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MobileEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_a_verification_token_and_sends_a_code(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/mobile/v1/register', [
            'name' => 'Acheteur Mobile',
            'email' => 'mobile@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => true,
        ])->assertCreated()
            ->assertJsonPath('requires_email_verification', true)
            ->assertJsonStructure(['data' => ['verification_token', 'email']]);

        Notification::assertSentTo(
            User::where('email', 'mobile@example.com')->firstOrFail(),
            VerifyEmailCode::class,
        );

        $this->withToken($response->json('data.verification_token'))
            ->getJson('/api/mobile/v1/me')
            ->assertForbidden();
    }

    public function test_registration_requires_privacy_consent(): void
    {
        $this->postJson('/api/mobile/v1/register', [
            'name' => 'Acheteur Mobile',
            'email' => 'sans-consentement@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('privacy_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'sans-consentement@example.com']);
    }

    public function test_registration_stores_the_privacy_consent(): void
    {
        Notification::fake();

        $this->postJson('/api/mobile/v1/register', [
            'name' => 'Acheteur Mobile',
            'email' => 'consentement@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => true,
        ])->assertCreated();

        $user = User::where('email', 'consentement@example.com')->firstOrFail();
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(config('privacy.version'), $user->privacy_policy_version);
    }

    public function test_the_code_grants_a_full_mobile_token(): void
    {
        Notification::fake();

        $this->postJson('/api/mobile/v1/register', [
            'name' => 'Acheteur Mobile',
            'email' => 'mobile@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => true,
        ])->assertCreated();

        $user = User::where('email', 'mobile@example.com')->firstOrFail();
        $code = null;

        Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $response = $this->postJson('/api/mobile/v1/login', [
            'email' => 'mobile@example.com',
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified')
            ->assertJsonStructure(['data' => ['verification_token', 'email']]);

        $verified = $this->withToken($response->json('data.verification_token'))
            ->postJson('/api/mobile/v1/email/verify-code', ['code' => $code])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // Le guard Sanctum est mis en cache entre deux requêtes dans les tests.
        Auth::forgetGuards();

        $this->withToken($verified->json('data.token'))
            ->getJson('/api/mobile/v1/me')
            ->assertOk();
    }

    public function test_an_invalid_code_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        app(EmailVerificationCode::class)->issue($user);
        $token = $user->createToken('mobile-email-verification', ['verify-email'], now()->addMinutes(30))->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/mobile/v1/email/verify-code', ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'invalid_verification_code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_new_code_can_be_requested_from_the_api(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('mobile-email-verification', ['verify-email'], now()->addMinutes(30))->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/mobile/v1/email/resend-code')
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmailCode::class);
    }
}
