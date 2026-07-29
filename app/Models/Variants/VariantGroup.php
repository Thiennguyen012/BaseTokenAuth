<?php

namespace App\Models\Variants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_code',
        'group_name',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variant_groups')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }
}
