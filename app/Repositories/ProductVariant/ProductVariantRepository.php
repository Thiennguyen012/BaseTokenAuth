<?php

namespace App\Repositories\ProductVariant;

use App\Models\ProductVariants\ProductVariant;
use App\Repositories\Base\BaseRepository;

class ProductVariantRepository extends BaseRepository implements ProductVariantInterface
{
    public function model(): string
    {
        return ProductVariant::class;
    }
}
