<?php

namespace App\Repositories\Category;

use App\Models\Categories\Category;
use App\Repositories\Base\BaseRepository;

class CategoryRepository extends BaseRepository implements CategoryInterface
{
    public function model(): string
    {
        return Category::class;
    }
}
