<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $commune
 * @property string|null $department
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $opening_hours
 * @property string|null $contact_phone
 * @property string|null $image_path
 * @property bool $is_active
 * @property int|null $published_observations_count
 */
class Market extends Model
{
    protected $fillable = ['name', 'commune', 'department', 'latitude', 'longitude', 'opening_hours', 'contact_phone', 'image_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    }

    /** @return HasMany<PriceObservation, $this> */
    public function observations(): HasMany
    {
        return $this->hasMany(PriceObservation::class);
    }
}
