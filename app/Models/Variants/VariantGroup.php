<?php

namespace App\Models\Variants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Products\ProductVariantGroup;
use App\Models\Variants\VariantOption;

class VariantGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_code',
        'group_name',
    ];

    public function productConfigurations(): HasMany
    {
        return $this->hasMany(ProductVariantGroup::class);
    }

    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(
            VariantOption::class,
            ProductVariantGroup::class,
            'variant_group_id',
            'product_variant_group_id'
        );
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variant_groups')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }
}
