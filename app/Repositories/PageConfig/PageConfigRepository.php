<?php

namespace App\Repositories\PageConfig;

use App\Models\PageConfigs\PageConfig;
use App\Repositories\Base\BaseRepository;

class PageConfigRepository extends BaseRepository implements PageConfigInterface
{
    public function model(): string
    {
        return PageConfig::class;
    }
}
