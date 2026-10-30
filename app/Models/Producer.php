<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producer extends Model
{
    protected $fillable = ['name', 'organization', 'phone', 'market_id', 'commune', 'notes', 'notification_consent', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'notification_consent' => 'boolean'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(PriceObservation::class);
    }
}
