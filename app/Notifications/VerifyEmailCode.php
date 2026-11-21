<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\EmailVerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de vérification — Marchés maraîchers du Bénin')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Voici le code à six chiffres pour confirmer votre adresse e-mail :')
            ->line('**'.$this->code.'**')
            ->line('Ce code est valable '.EmailVerificationCode::TTL_MINUTES.' minutes. Ne le partagez avec personne.')
            ->line('Si vous n’êtes pas à l’origine de cette inscription, vous pouvez ignorer ce message.')
            ->salutation('L’équipe des Marchés maraîchers du Bénin');
    }
}
