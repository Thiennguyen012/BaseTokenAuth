<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasFiles;
use App\Models\ProductVariants\ProductVariant;
use App\Models\Variants\VariantGroup;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasFiles;

    protected $fillable = [
        'product_name',
        'sku',
        'description',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantGroups(): BelongsToMany
    {
        return $this->belongsToMany(VariantGroup::class, 'product_variant_groups')
            ->withPivot(['is_required', 'sort_order'])
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }
}
