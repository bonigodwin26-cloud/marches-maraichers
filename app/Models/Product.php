<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property string $unit
 * @property string $slug
 * @property string|null $image_path
 * @property bool $is_active
 * @property int|null $published_observations_count
 */
class Product extends Model
{
    protected $fillable = ['name', 'category', 'unit', 'slug', 'image_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<PriceObservation, $this> */
    public function observations(): HasMany
    {
        return $this->hasMany(PriceObservation::class);
    }
}
