<?php

namespace App\Services\Product;

use App\Repositories\Product\ProductInterface;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function paginate($limit = 10, $search = '')
    {
        $where = [];
        $orderBy = ['created_at' => 'desc'];

        if ($search) {
            $where['orWhere'] = [
                'product_name' => ['product_name', 'like', '%' . $search . '%'],
                'sku' => ['sku', 'like', '%' . $search . '%'],
                'description' => ['description', 'like', '%' . $search . '%'],
            ];
        }

        return $this->productRepository->paginate($where, $orderBy, ['*'], ['variantGroups.options', 'variants.options.group'], $limit);
    }

    public function getAll($search = '')
    {
        $where = [];
        $orderBy = ['product_name' => 'asc'];

        if ($search) {
            $where['orWhere'] = [
                'product_name' => ['product_name', 'like', '%' . $search . '%'],
                'sku' => ['sku', 'like', '%' . $search . '%'],
            ];
        }

        return $this->productRepository->get($where, $orderBy, ['*']);
    }

    public function find($id)
    {
        $product = $this->productRepository->find($id);

        return $product?->load(['variantGroups.options', 'variants.options.group']);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $groups = $data['variant_groups'] ?? [];
            unset($data['variant_groups']);
            $product = $this->productRepository->create($data);
            $this->syncVariantGroups($product, $groups);

            return $product->load(['variantGroups.options', 'variants.options.group']);
        });
    }

    public function update($product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            $hasGroups = array_key_exists('variant_groups', $data);
            $groups = $data['variant_groups'] ?? [];
            unset($data['variant_groups']);
            $product = $this->productRepository->edit($product, $data);
            if ($hasGroups) {
                $this->syncVariantGroups($product, $groups);
            }

            return $product->load(['variantGroups.options', 'variants.options.group']);
        });
    }

    public function delete($product)
    {
        return DB::transaction(function () use ($product) {
            $product->variants()->update(['is_active' => false]);
            return $this->productRepository->delete($product);
        });
    }

    private function syncVariantGroups($product, array $groups): void
    {
        $sync = [];
        foreach ($groups as $group) {
            $sync[$group['variant_group_id']] = [
                'is_required' => $group['is_required'] ?? true,
                'sort_order' => $group['sort_order'] ?? 0,
            ];
        }
        $product->variantGroups()->sync($sync);
    }
}
