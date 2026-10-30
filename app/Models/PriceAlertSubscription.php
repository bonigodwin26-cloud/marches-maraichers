<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlertSubscription extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'market_id', 'target_price_xof', 'phone', 'notification_consent', 'is_active',
    ];

    protected function casts(): array
    {
        return ['notification_consent' => 'boolean', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function market(): BelongsTo { return $this->belongsTo(Market::class); }
}
