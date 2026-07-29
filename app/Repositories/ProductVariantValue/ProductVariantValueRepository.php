<?php

namespace App\Repositories\ProductVariantValue;

use App\Models\ProductVariants\ProductVariantValue;
use App\Repositories\Base\BaseRepository;

class ProductVariantValueRepository extends BaseRepository implements ProductVariantValueInterface
{
    public function model(): string
    {
        return ProductVariantValue::class;
    }
}
