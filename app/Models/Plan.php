<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'price_per_branch', 'features', 'is_active', 'sort_order'])]
class Plan extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_branch' => 'decimal:3',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Merchant, $this>
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function includes(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }
}
