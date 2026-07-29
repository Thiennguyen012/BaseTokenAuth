<?php

namespace App\Repositories\VariantGroup;

use App\Models\Variants\VariantGroup;
use App\Repositories\Base\BaseRepository;

class VariantGroupRepository extends BaseRepository implements VariantGroupInterface
{
    public function model(): string
    {
        return VariantGroup::class;
    }
}
