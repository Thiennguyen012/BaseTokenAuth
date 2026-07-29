<?php

namespace App\Models\ProductVariants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'variant_option_id',
    ];

    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'variant_option_id' => 'integer',
        ];
    }
}
