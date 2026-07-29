<?php

namespace App\Repositories\Product;

use App\Models\Products\Product;
use App\Repositories\Base\BaseRepository;

class ProductRepository extends BaseRepository implements ProductInterface
{
    public function model(): string
    {
        return Product::class;
    }
}
