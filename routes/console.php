<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use App\Models\ContactRequest;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('market:remind-pending-requests', function () {
    $recipients = User::query()->where('is_admin', true)->whereNotNull('email')->pluck('email')->all();
    if (! $recipients) {
        $this->warn('Aucun administrateur avec une adresse e-mail : les rappels restent en attente.');
        return 0;
    }

    $requests = ContactRequest::with(['buyer', 'product', 'market'])
        ->where('status', 'pending')
        ->whereNull('reminder_sent_at')
        ->where('created_at', '<=', now()->subHours(48))
        ->get();

    foreach ($requests as $contactRequest) {
        $body = sprintf(
            "Une demande de mise en relation attend depuis plus de 48 heures.\n\nDemande : #%d\nAcheteur : %s\nProduit : %s\nMarché : %s\nTéléphone : %s\n\nConnectez-vous à l’espace équipe pour la traiter.",
            $contactRequest->id,
            $contactRequest->buyer?->name ?? 'Acheteur',
            $contactRequest->product?->name ?? 'Produit',
            $contactRequest->market?->name ?? 'Marché',
            $contactRequest->phone,
        );

        Mail::raw($body, function ($message) use ($recipients, $contactRequest) {
            $message->to($recipients)->subject('Rappel : demande en attente depuis 48 h (#'.$contactRequest->id.')');
        });

        $contactRequest->update(['reminder_sent_at' => now()]);
        $this->info('Rappel envoyé pour la demande #'.$contactRequest->id.'.');
    }

    if ($requests->isEmpty()) {
        $this->info('Aucune demande en attente depuis 48 heures.');
    }

    return 0;
})->purpose('Notifie par e-mail l’équipe des demandes en attente depuis 48 heures');

Schedule::command('market:remind-pending-requests')->hourly()->withoutOverlapping();
