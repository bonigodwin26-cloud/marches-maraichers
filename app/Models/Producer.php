<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $organization
 * @property string $phone
 * @property int|null $market_id
 * @property string|null $commune
 * @property string|null $notes
 * @property bool $notification_consent
 * @property bool $is_active
 */
class Producer extends Model
{
    protected $fillable = ['name', 'organization', 'phone', 'market_id', 'commune', 'notes', 'notification_consent', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'notification_consent' => 'boolean'];
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** @return HasMany<PriceObservation, $this> */
    public function observations(): HasMany
    {
        return $this->hasMany(PriceObservation::class);
    }
}
