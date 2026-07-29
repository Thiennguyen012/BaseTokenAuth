<?php

namespace App\Repositories\VariantOption;

use App\Models\Variants\VariantOption;
use App\Repositories\Base\BaseRepository;

class VariantOptionRepository extends BaseRepository implements VariantOptionInterface
{
    public function model(): string
    {
        return VariantOption::class;
    }
}
