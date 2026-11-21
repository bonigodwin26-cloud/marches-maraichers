<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int|null $market_id
 * @property int|null $target_price_xof
 * @property string $phone
 * @property bool $notification_consent
 * @property bool $is_active
 */
class PriceAlertSubscription extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'market_id', 'target_price_xof', 'phone', 'notification_consent', 'is_active',
    ];

    protected function casts(): array
    {
        return ['notification_consent' => 'boolean', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
