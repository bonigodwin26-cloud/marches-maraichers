<?php

namespace App\Services;

use App\Models\ContactRequest;
use App\Models\NotificationDelivery;
use App\Models\PriceAlertSubscription;
use App\Models\PriceObservation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketMessenger
{
    public function approved(ContactRequest $contactRequest): void
    {
        $contactRequest->loadMissing(['buyer', 'product', 'market', 'producer']);

        $producerBody = sprintf(
            'Demande approuvée : %s (%s) souhaite acheter %s de %s au marché %s. Merci de le/la contacter.',
            $contactRequest->buyer->name,
            $contactRequest->phone,
            $contactRequest->requested_volume ?: 'un volume à convenir',
            $contactRequest->product->name,
            $contactRequest->market->name,
        );
        if ($contactRequest->producer->notification_consent) {
            $this->queue($contactRequest, 'contact_approved_producer', $contactRequest->producer->name, $contactRequest->producer->phone, $producerBody);
        }

        $buyerBody = sprintf(
            'Votre demande pour %s au marché %s est approuvée. Contact : %s, %s.',
            $contactRequest->product->name,
            $contactRequest->market->name,
            $contactRequest->producer->name,
            $contactRequest->producer->phone,
        );
        if ($contactRequest->notification_consent) {
            $this->queue($contactRequest, 'contact_approved_buyer', $contactRequest->buyer->name, $contactRequest->phone, $buyerBody);
        }
    }

    public function rejected(ContactRequest $contactRequest): void
    {
        $contactRequest->loadMissing(['buyer', 'product', 'market']);
        $body = sprintf(
            'Votre demande de mise en relation pour %s au marché %s n’a pas été approuvée. Connectez-vous à la plateforme pour en savoir plus.',
            $contactRequest->product->name,
            $contactRequest->market->name,
        );
        if ($contactRequest->notification_consent) {
            $this->queue($contactRequest, 'contact_rejected_buyer', $contactRequest->buyer->name, $contactRequest->phone, $body);
        }
    }

    public function pricePublished(PriceObservation $observation): void
    {
        $observation->loadMissing(['product', 'market']);
        $subscriptions = PriceAlertSubscription::with(['user', 'product', 'market'])
            ->where('is_active', true)
            ->where('notification_consent', true)
            ->where('product_id', $observation->product_id)
            ->where(fn ($query) => $query->whereNull('market_id')->orWhere('market_id', $observation->market_id))
            ->where(fn ($query) => $query->whereNull('target_price_xof')->orWhere('target_price_xof', '>=', $observation->price_xof))
            ->get();

        foreach ($subscriptions as $subscription) {
            $body = sprintf(
                'Alerte prix : %s est publié à %s FCFA par %s sur %s.',
                $observation->product->name,
                number_format($observation->price_xof, 0, ',', ' '),
                $observation->unit_snapshot,
                $observation->market->name,
            );
            $delivery = NotificationDelivery::create([
                'price_alert_subscription_id' => $subscription->id,
                'event' => 'price_alert_published',
                'recipient_name' => $subscription->user?->name,
                'recipient_phone' => $subscription->phone,
                'channel' => config('services.market_messenger.channel', 'log'),
                'body' => $body,
                'status' => 'pending',
            ]);

            $this->retry($delivery);
        }
    }

    public function retry(NotificationDelivery $delivery): NotificationDelivery
    {
        if (! in_array($delivery->status, ['pending', 'failed', 'not_configured', 'logged'], true)) {
            return $delivery;
        }

        $channel = config('services.market_messenger.channel', 'log');
        $delivery->channel = $channel;
        $delivery->error = null;

        if ($channel === 'log') {
            Log::info('Notification de marché (mode journal uniquement, aucun message externe envoyé).', [
                'delivery_id' => $delivery->id,
                'event' => $delivery->event,
            ]);
            $delivery->status = 'logged';
            $delivery->attempted_at = now();
            $delivery->save();

            return $delivery;
        }

        $accountSid = config('services.market_messenger.account_sid');
        $apiKey = config('services.market_messenger.api_key');
        $apiSecret = config('services.market_messenger.api_secret');
        $from = $channel === 'twilio_whatsapp'
            ? config('services.market_messenger.whatsapp_from')
            : config('services.market_messenger.sms_from');
        $contentSid = config('services.market_messenger.whatsapp_content_sid');

        if (! in_array($channel, ['twilio_sms', 'twilio_whatsapp'], true) || ! $accountSid || ! $apiKey || ! $apiSecret || ! $from || ($channel === 'twilio_whatsapp' && ! $contentSid)) {
            $delivery->status = 'not_configured';
            $delivery->error = 'Renseignez les identifiants Twilio, le numéro expéditeur et, pour WhatsApp, un modèle approuvé.';
            $delivery->attempted_at = now();
            $delivery->save();

            return $delivery;
        }

        $to = $channel === 'twilio_whatsapp' ? 'whatsapp:'.$delivery->recipient_phone : $delivery->recipient_phone;
        $from = $channel === 'twilio_whatsapp' && ! str_starts_with($from, 'whatsapp:') ? 'whatsapp:'.$from : $from;
        $form = ['To' => $to, 'From' => $from];
        if ($channel === 'twilio_sms') {
            $form['Body'] = $delivery->body;
        } else {
            $form['ContentSid'] = $contentSid;
            $form['ContentVariables'] = json_encode([
                '1' => $delivery->body,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($apiKey, $apiSecret)
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $form)
                ->throw();

            $delivery->status = 'submitted';
            $delivery->provider_message_id = $response->json('sid');
            $delivery->error = null;
        } catch (Throwable $exception) {
            $delivery->status = 'failed';
            $delivery->error = mb_substr($exception->getMessage(), 0, 2000);
            Log::warning('Échec de notification du marché.', ['delivery_id' => $delivery->id, 'error' => $delivery->error]);
        }

        $delivery->channel = $channel;
        $delivery->attempted_at = now();
        $delivery->save();

        return $delivery;
    }

    private function queue(ContactRequest $contactRequest, string $event, ?string $name, string $phone, string $body): void
    {
        $delivery = NotificationDelivery::create([
            'contact_request_id' => $contactRequest->id,
            'event' => $event,
            'recipient_name' => $name,
            'recipient_phone' => $phone,
            'channel' => config('services.market_messenger.channel', 'log'),
            'body' => $body,
            'status' => 'pending',
        ]);

        $this->retry($delivery);
    }
}
