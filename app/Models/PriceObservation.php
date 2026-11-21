<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property int $market_id
 * @property int|null $producer_id
 * @property int $collected_by
 * @property int $price_xof
 * @property string $unit_snapshot
 * @property string|null $quality
 * @property float|null $available_quantity
 * @property Carbon $observed_at
 * @property string $status
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property string|null $notes
 */
class PriceObservation extends Model
{
    protected $fillable = [
        'product_id', 'market_id', 'producer_id', 'collected_by', 'price_xof',
        'unit_snapshot', 'quality', 'available_quantity', 'observed_at', 'status', 'reviewed_by', 'reviewed_at', 'review_note', 'notes',
    ];

    protected function casts(): array
    {
        return ['observed_at' => 'datetime', 'reviewed_at' => 'datetime'];
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
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
