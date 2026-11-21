<?php

namespace App\Models;

use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCode;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar_path
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $privacy_accepted_at
 * @property string|null $privacy_policy_version
 * @property string|null $email_verification_code
 * @property Carbon|null $email_verification_code_expires_at
 * @property int $email_verification_attempts
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property bool $is_admin
 * @property bool $is_collector
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'avatar_path', 'privacy_accepted_at', 'privacy_policy_version'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'email_verification_code'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_collector' => 'boolean',
        ];
    }

    /**
     * Remplace l'e-mail de vérification par défaut par un code à six chiffres.
     * Le lien signé de Fortify reste disponible en secours.
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = app(EmailVerificationCode::class)->issue($this);

        $this->notify(new VerifyEmailCode($code));
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isCollector(): bool
    {
        return $this->is_collector || $this->is_admin;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
