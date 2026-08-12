<?php

namespace App\Models\Variants;

use App\Models\Products\ProductVariantGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\ProductVariants\ProductVariant;

class VariantOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_group_id',
        'option_code',
        'option_name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'product_variant_group_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function productVariantGroup(): BelongsTo
    {
        return $this->belongsTo(ProductVariantGroup::class);
    }

    public function productVariants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_values')
            ->withTimestamps();
    }
}
