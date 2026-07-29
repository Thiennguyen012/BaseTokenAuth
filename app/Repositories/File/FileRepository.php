<?php

namespace App\Repositories\File;

use App\Models\Files\File;
use App\Repositories\Base\BaseRepository;

class FileRepository extends BaseRepository implements FileInterface
{
    public function model(): string
    {
        return File::class;
    }
}
