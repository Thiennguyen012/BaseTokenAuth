<?php

namespace App\Repositories\Tag;

use App\Models\Tags\Tag;
use App\Repositories\Base\BaseRepository;

class TagRepository extends BaseRepository implements TagInterface
{
    public function model(): string
    {
        return Tag::class;
    }
}
