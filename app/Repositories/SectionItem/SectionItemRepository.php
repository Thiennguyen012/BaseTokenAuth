<?php

namespace App\Repositories\SectionItem;

use App\Models\SectionItems\SectionItem;
use App\Repositories\Base\BaseRepository;

class SectionItemRepository extends BaseRepository implements SectionItemInterface
{
    public function model(): string
    {
        return SectionItem::class;
    }
}
