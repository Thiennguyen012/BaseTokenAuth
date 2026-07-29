<?php

namespace App\Services\ProductVariantValue;

use App\Repositories\ProductVariantValue\ProductVariantValueInterface;

class ProductVariantValueService
{
    public function __construct(protected ProductVariantValueInterface $productVariantValueRepository) {}

    public function create(array $data)
    {
        return $this->productVariantValueRepository->create($data);
    }
}
