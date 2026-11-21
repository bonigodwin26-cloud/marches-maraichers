<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_six_digit_code(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Acheteur Test',
            'email' => 'acheteur@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => true,
        ]);

        Notification::assertSentTo(
            User::where('email', 'acheteur@example.com')->firstOrFail(),
            VerifyEmailCode::class,
            fn (VerifyEmailCode $notification) => preg_match('/^\d{6}$/', $notification->code) === 1,
        );
    }

    public function test_a_valid_code_verifies_the_email_address(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCode::class)->issue($user);

        $this->actingAs($user)
            ->post(route('verification.code.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->email_verification_code);
        Event::assertDispatched(Verified::class);
    }

    public function test_an_invalid_code_is_rejected_and_counted(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCode::class)->issue($user);
        $wrongCode = $code === '000000' ? '111111' : '000000';

        $this->actingAs($user)
            ->post(route('verification.code.verify'), ['code' => $wrongCode])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, $user->fresh()->email_verification_attempts);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCode::class)->issue($user);
        $user->forceFill(['email_verification_code_expires_at' => now()->subMinute()])->save();

        $this->actingAs($user)
            ->post(route('verification.code.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_new_code_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.code.resend'))
            ->assertSessionHas('status', 'verification-code-sent');

        Notification::assertSentTo($user, VerifyEmailCode::class);
    }
}
