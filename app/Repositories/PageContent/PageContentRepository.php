<?php

namespace App\Repositories\PageContent;

use App\Models\PageContents\PageContent;
use App\Repositories\Base\BaseRepository;

class PageContentRepository extends BaseRepository implements PageContentInterface
{
    public function model(): string
    {
        return PageContent::class;
    }
}
