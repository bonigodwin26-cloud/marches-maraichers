<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function market(): BelongsTo { return $this->belongsTo(Market::class); }
    public function producer(): BelongsTo { return $this->belongsTo(Producer::class); }
    public function collector(): BelongsTo { return $this->belongsTo(User::class, 'collected_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
