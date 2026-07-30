<?php

namespace App\Repositories\PageSection;

use App\Models\PageSections\PageSection;
use App\Repositories\Base\BaseRepository;

class PageSectionRepository extends BaseRepository implements PageSectionInterface
{
    public function model(): string
    {
        return PageSection::class;
    }
}
