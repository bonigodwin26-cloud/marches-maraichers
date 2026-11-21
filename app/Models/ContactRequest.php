<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $buyer_id
 * @property int $product_id
 * @property int $market_id
 * @property int|null $price_observation_id
 * @property int|null $producer_id
 * @property string|null $organization
 * @property string $phone
 * @property string|null $requested_volume
 * @property float|null $requested_quantity
 * @property string|null $requested_unit_snapshot
 * @property string|null $message
 * @property string $status
 * @property bool $notification_consent
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $transaction_declared_at
 * @property bool|null $transaction_happened
 */
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

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** @return BelongsTo<Producer, $this> */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
