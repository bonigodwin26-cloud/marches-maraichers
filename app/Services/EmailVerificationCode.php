<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmailVerificationCode
{
    /** Durée de validité d'un code, en minutes. */
    public const TTL_MINUTES = 15;

    /** Nombre maximum de tentatives avant renouvellement du code. */
    public const MAX_ATTEMPTS = 5;

    /**
     * Génère un nouveau code à six chiffres et le stocke sous forme hachée.
     * Le code en clair est retourné uniquement pour être envoyé par e-mail.
     */
    public function issue(User $user): string
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'email_verification_attempts' => 0,
        ])->save();

        return $code;
    }

    /**
     * Vérifie le code soumis. Une mauvaise tentative est comptabilisée.
     */
    public function verify(User $user, string $code): bool
    {
        if ($user->hasVerifiedEmail()
            || ! $user->email_verification_code
            || ! $user->email_verification_code_expires_at?->isFuture()
            || $user->email_verification_attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $user->email_verification_code)) {
            $user->increment('email_verification_attempts');

            return false;
        }

        return true;
    }

    /**
     * Supprime le code après vérification pour éviter toute réutilisation.
     */
    public function clear(User $user): void
    {
        $user->forceFill([
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_attempts' => 0,
        ])->save();
    }
}
