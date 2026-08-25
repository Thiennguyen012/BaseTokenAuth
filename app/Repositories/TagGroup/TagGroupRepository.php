<?php

namespace App\Repositories\TagGroup;

use App\Models\Tags\TagGroup;
use App\Repositories\Base\BaseRepository;

class TagGroupRepository extends BaseRepository implements TagGroupInterface
{
    public function model(): string
    {
        return TagGroup::class;
    }
}
