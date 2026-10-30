<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRequest extends Model
{
    protected $fillable = [
        'buyer_id', 'product_id', 'market_id', 'price_observation_id', 'producer_id',
        'organization', 'phone', 'requested_volume', 'requested_quantity', 'requested_unit_snapshot', 'message', 'status', 'notification_consent',
        'reviewed_by', 'reviewed_at', 'admin_note', 'transaction_declared', 'transaction_happened', 'transaction_declared_at', 'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'transaction_declared' => 'boolean', 'transaction_happened' => 'boolean', 'transaction_declared_at' => 'datetime', 'reminder_sent_at' => 'datetime', 'notification_consent' => 'boolean'];
    }

    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'buyer_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function market(): BelongsTo { return $this->belongsTo(Market::class); }
    public function producer(): BelongsTo { return $this->belongsTo(Producer::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
