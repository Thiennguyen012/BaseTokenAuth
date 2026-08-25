<?php

namespace App\Services\Product;

use App\Repositories\Product\ProductInterface;
use App\Services\File\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductInterface $productRepository, protected FileService $fileService)
    {
        $this->productRepository = $productRepository;
    }

    public function paginate($limit = 10, $search = '', array $categoryIds = [], string $sort = 'latest', ?bool $isFeatured = null, array $categorySlugs = [], array $tagSlugs = [], array $tagIds = [], array $tagGroupIds = [], array $tagGroupCodes = [])
    {
        $where = [];

        if ($search) {
            $where['orWhere'] = [
                'product_name' => ['product_name', 'like', '%' . $search . '%'],
                'sku' => ['sku', 'like', '%' . $search . '%'],
                'description' => ['description', 'like', '%' . $search . '%'],
                'slug' => ['slug', 'like', '%' . $search . '%'],
            ];
        }

        if ($categoryIds !== []) {
            foreach ($categoryIds as $categoryId) {
                $where['whereHas'][] = ['categories', ['categories.id' => (int) $categoryId]];
            }
        }

        if ($categorySlugs !== []) {
            foreach ($categorySlugs as $categorySlug) {
                $where['whereHas'][] = ['categories', ['categories.slug' => (string) $categorySlug]];
            }
        }

        if ($tagIds !== []) {
            foreach ($tagIds as $tagId) {
                $where['whereHas'][] = ['tags', ['tags.id' => (int) $tagId]];
            }
        }

        if ($tagSlugs !== []) {
            foreach ($tagSlugs as $tagSlug) {
                $where['whereHas'][] = ['tags', ['tags.slug' => (string) $tagSlug]];
            }
        }

        if ($tagGroupIds !== []) {
            foreach ($tagGroupIds as $groupId) {
                $where['whereHas'][] = ['tags.tagGroup', ['tag_groups.id' => (int) $groupId]];
            }
        }

        if ($tagGroupCodes !== []) {
            foreach ($tagGroupCodes as $groupCode) {
                $where['whereHas'][] = ['tags.tagGroup', ['tag_groups.code' => (string) $groupCode]];
            }
        }

        if ($isFeatured !== null) {
            $where['is_featured'] = $isFeatured;
        }

        return $this->productRepository->paginateListing($where, ['files', 'categories', 'tags.tagGroup', 'variantGroupConfigurations.group', 'variantGroupConfigurations.options', 'variants.options.productVariantGroup.group'], $limit, $sort);
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

        if ($search) {
            $where['orWhere']['slug'] = ['slug', 'like', '%' . $search . '%'];
        }

        return $this->productRepository->get($where, $orderBy, ['*']);
    }

    public function find($id)
    {
        $product = $this->productRepository->find($id);

        return $product?->load(['files', 'categories', 'tags', 'variantGroupConfigurations.group', 'variantGroupConfigurations.options', 'variants.options.productVariantGroup.group']);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $groups = $data['variant_groups'] ?? [];
            $categoryIds = $data['category_ids'] ?? [];
            $tagIds = $data['tag_ids'] ?? [];
            $images = $data['images'] ?? [];
            unset($data['variant_groups'], $data['category_ids'], $data['tag_ids'], $data['images']);
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['product_name']);
            $product = $this->productRepository->create($data);
            $this->syncCategories($product, $categoryIds);
            $this->syncTags($product, $tagIds);
            $this->syncVariantGroups($product, $groups);
            $this->uploadImages($product, $images);

            return $product->load(['files', 'categories', 'tags', 'variantGroupConfigurations.group', 'variantGroupConfigurations.options', 'variants.options.productVariantGroup.group']);
        });
    }

    public function update($product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            $hasGroups = array_key_exists('variant_groups', $data);
            $hasCategories = array_key_exists('category_ids', $data);
            $hasTags = array_key_exists('tag_ids', $data);
            $groups = $data['variant_groups'] ?? [];
            $categoryIds = $data['category_ids'] ?? [];
            $tagIds = $data['tag_ids'] ?? [];
            $images = $data['images'] ?? [];
            unset($data['variant_groups'], $data['category_ids'], $data['tag_ids'], $data['images']);
            if (array_key_exists('product_name', $data)) {
                $data['slug'] = $this->uniqueSlug(
                    $data['product_name'],
                    (int) $product->id
                );
            } elseif (array_key_exists('slug', $data)) {
                $data['slug'] = $this->uniqueSlug(
                    $data['slug'] ?: $product->product_name,
                    (int) $product->id
                );
            }
            $product = $this->productRepository->edit($product, $data);
            if ($hasCategories) $this->syncCategories($product, $categoryIds);
            if ($hasTags) $this->syncTags($product, $tagIds);
            if ($hasGroups) {
                $this->syncVariantGroups($product, $groups);
            }
            $this->uploadImages($product, $images);

            return $product->load(['files', 'categories', 'tags', 'variantGroupConfigurations.group', 'variantGroupConfigurations.options', 'variants.options.productVariantGroup.group']);
        });
    }

    public function delete($product)
    {
        return DB::transaction(function () use ($product) {
            $product->variants()->update(['is_active' => false]);
            return $this->productRepository->delete($product);
        });
    }

    public function paginatePublic($limit = 10, $search = '', array $categoryIds = [], string $sort = 'latest', ?bool $isFeatured = null, array $categorySlugs = [], array $tagSlugs = [], array $tagIds = [], array $tagGroupIds = [], array $tagGroupCodes = [])
    {
        $where = ['is_active' => true];
        if ($search) {
            $where['orWhere'] = [
                'product_name' => ['product_name', 'like', '%' . $search . '%'],
                'slug' => ['slug', 'like', '%' . $search . '%'],
                'sku' => ['sku', 'like', '%' . $search . '%'],
                'description' => ['description', 'like', '%' . $search . '%'],
            ];
        }
        foreach ($categoryIds as $categoryId) {
            $where['whereHas'][] = ['categories', ['categories.id' => (int) $categoryId]];
        }
        foreach ($categorySlugs as $categorySlug) {
            $where['whereHas'][] = ['categories', ['categories.slug' => (string) $categorySlug]];
        }
        foreach ($tagIds as $tagId) {
            $where['whereHas'][] = ['tags', ['tags.id' => (int) $tagId]];
        }
        foreach ($tagSlugs as $tagSlug) {
            $where['whereHas'][] = ['tags', ['tags.slug' => (string) $tagSlug]];
        }
        foreach ($tagGroupIds as $groupId) {
            $where['whereHas'][] = ['tags.tagGroup', ['tag_groups.id' => (int) $groupId]];
        }
        foreach ($tagGroupCodes as $groupCode) {
            $where['whereHas'][] = ['tags.tagGroup', ['tag_groups.code' => (string) $groupCode]];
        }
        if ($isFeatured !== null) {
            $where['is_featured'] = $isFeatured;
        }
        return $this->productRepository->paginateListing($where, ['files', 'categories', 'tags.tagGroup', 'variantGroupConfigurations.group', 'variantGroupConfigurations.options', 'variants' => fn ($query) => $query->where('is_active', true), 'variants.options.productVariantGroup.group'], $limit, $sort);
    }

    public function findPublic($id)
    {
        $identity = ctype_digit((string) $id) ? ['id' => (int) $id] : ['slug' => $id];

        return $this->productRepository->first(
            $identity + ['is_active' => true], [], ['*'],
            ['files', 'categories', 'tags', 'variantGroupConfigurations.group', 'variantGroupConfigurations.options', 'variants' => fn ($query) => $query->where('is_active', true), 'variants.options.productVariantGroup.group']
        );
    }

    public function removeVariantGroup($product, int $configurationId): void
    {
        DB::transaction(function () use ($product, $configurationId) {
            $configuration = $product->variantGroupConfigurations()->findOrFail($configurationId);
            $this->ensureOptionsAreUnused($configuration->options()->pluck('id')->all());
            $configuration->delete();
        });
    }

    public function updateVariantGroup($product, int $configurationId, array $data)
    {
        $configuration = $product->variantGroupConfigurations()->findOrFail($configurationId);
        $configuration->update($data);

        return $configuration->fresh(['group', 'options']);
    }

    private function syncVariantGroups($product, array $groups): void
    {
        $configurationIds = [];
        foreach ($groups as $group) {
            $configuration = $product->variantGroupConfigurations()->updateOrCreate(
                ['variant_group_id' => $group['variant_group_id']],
                ['is_required' => $group['is_required'] ?? true, 'sort_order' => $group['sort_order'] ?? 0]
            );
            $configurationIds[] = $configuration->id;

            if (array_key_exists('options', $group) || !empty($group['options_present'])) {
                $keptOptionIds = [];
                foreach (($group['options'] ?? []) as $option) {
                    $savedOption = $configuration->options()->updateOrCreate(
                        isset($option['id']) ? ['id' => $option['id']] : ['option_code' => $option['option_code']],
                        [
                            'option_code' => $option['option_code'],
                            'option_name' => $option['option_name'],
                            'sort_order' => $option['sort_order'] ?? 0,
                            'is_active' => $option['is_active'] ?? true,
                        ]
                    );
                    $keptOptionIds[] = $savedOption->id;
                }
                $removedOptionIds = $configuration->options()->whereNotIn('id', $keptOptionIds)->pluck('id');
                $this->ensureOptionsAreUnused($removedOptionIds->all());
                $configuration->options()->whereIn('id', $removedOptionIds)->delete();
            }
        }

        $removedConfigurationIds = $product->variantGroupConfigurations()->whereNotIn('id', $configurationIds)->pluck('id');
        $removedOptionIds = DB::table('variant_options')->whereIn('product_variant_group_id', $removedConfigurationIds)->pluck('id');
        $this->ensureOptionsAreUnused($removedOptionIds->all());
        $product->variantGroupConfigurations()->whereIn('id', $removedConfigurationIds)->delete();
    }

    private function syncCategories($product, array $categoryIds): void
    {
        $sync = [];
        foreach (array_values($categoryIds) as $index => $categoryId) {
            $sync[(int) $categoryId] = ['sort_order' => $index + 1];
        }
        $product->categories()->sync($sync);
    }

    private function ensureOptionsAreUnused(array $optionIds): void
    {
        if ($optionIds !== [] && DB::table('product_variant_values')->whereIn('variant_option_id', $optionIds)->exists()) {
            throw ValidationException::withMessages([
                'variant_groups' => 'Không thể xóa nhóm hoặc giá trị đang được biến thể sản phẩm sử dụng.',
            ]);
        }
    }

    private function uploadImages($product, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $this->fileService->uploadMany($images, $product, [
            'disk' => 'public',
            'directory' => 'products/' . $product->id,
            'type' => 'image',
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::substr(Str::slug($value) ?: 'san-pham', 0, 240);
        $slug = $base;
        $suffix = 2;

        while (DB::table('products')
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function syncTags($product, array $tagIds): void
    {
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        $product->tags()->sync($uniqueIds);
    }
}
