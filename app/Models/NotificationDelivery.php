<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'contact_request_id', 'price_alert_subscription_id', 'event', 'recipient_name', 'recipient_phone', 'channel', 'body',
        'status', 'provider_message_id', 'error', 'attempted_at',
    ];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    /** @return BelongsTo<ContactRequest, $this> */
    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class);
    }

    /** @return BelongsTo<PriceAlertSubscription, $this> */
    public function priceAlertSubscription(): BelongsTo
    {
        return $this->belongsTo(PriceAlertSubscription::class);
    }
}
