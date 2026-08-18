<?php

namespace App\Repositories\Product;

use App\Models\Products\Product;
use App\Repositories\Base\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository implements ProductInterface
{
    public function model(): string
    {
        return Product::class;
    }

    public function paginateListing(array $where, array $with, int $limit, string $sort = 'latest'): LengthAwarePaginator
    {
        $query = $this->query($where, [], ['products.*'], $with)
            ->withMin([
                'variants as min_price' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('price'),
            ], 'price');

        match ($sort) {
            'price_asc' => $query->orderByRaw('min_price IS NULL ASC')->orderBy('min_price'),
            'price_desc' => $query->orderByRaw('min_price IS NULL ASC')->orderByDesc('min_price'),
            'name_asc' => $query->orderBy('product_name'),
            'name_desc' => $query->orderByDesc('product_name'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($limit);
    }
}
