<?php

namespace App\Repositories\Product;

use App\Repositories\Base\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductInterface extends BaseInterface
{
    public function paginateListing(array $where, array $with, int $limit, string $sort = 'latest'): LengthAwarePaginator;
}
