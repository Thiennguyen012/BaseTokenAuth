<?php

namespace App\Models\Products;

use App\Models\Variants\VariantGroup;
use App\Models\Variants\VariantOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariantGroup extends Model
{
    protected $fillable = ['product_id', 'variant_group_id', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'variant_group_id' => 'integer', 'is_required' => 'boolean', 'sort_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(VariantGroup::class, 'variant_group_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class)->orderBy('sort_order')->orderBy('id');
    }
}
