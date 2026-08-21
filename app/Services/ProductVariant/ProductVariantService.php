<?php

namespace App\Services\ProductVariant;

use App\Models\Products\Product;
use App\Models\Variants\VariantOption;
use App\Models\ProductVariants\ProductVariant;
use App\Repositories\ProductVariant\ProductVariantInterface;
use App\Services\File\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function __construct(
        protected ProductVariantInterface $productVariantRepository,
        protected FileService $fileService
    ) {}

    public function paginate(int $limit = 10, string $search = '', ?int $productId = null, array $optionIds = [])
    {
        $where = [];

        if ($search !== '') {
            $where['sku'] = ['sku', 'like', '%' . $search . '%'];
        }

        if ($productId !== null) {
            $where['product_id'] = $productId;
        }

        foreach (array_unique(array_map('intval', $optionIds)) as $optionId) {
            if ($optionId > 0) {
                $where['whereHas'][] = ['options', ['variant_options.id' => $optionId]];
            }
        }

        return $this->productVariantRepository->paginate(
            $where,
            ['id' => 'desc'],
            ['*'],
            ['product', 'files', 'options.productVariantGroup.group'],
            $limit
        );
    }

    public function find($id)
    {
        return $this->productVariantRepository->first(
            ['id' => $id],
            [],
            ['*'],
            ['product', 'files', 'options.productVariantGroup.group']
        );
    }

    public function create(array $data)
    {
        $data['price'] = isset($data['price']) && $data['price'] !== '' && $data['price'] !== null ? $data['price'] : 0;
        $data['stock'] = isset($data['stock']) && $data['stock'] !== '' && $data['stock'] !== null ? $data['stock'] : 0;

        return DB::transaction(function () use ($data) {
            $product = Product::query()->with('variantGroupConfigurations')->lockForUpdate()->findOrFail($data['product_id']);
            $optionIds = $this->validateAndNormalizeOptions($product, $data['option_ids']);
            $images = $data['images'] ?? [];

            unset($data['option_ids'], $data['images']);
            $data['combination_key'] = hash('sha256', $optionIds->implode(':'));

            if ($product->variants()->where('combination_key', $data['combination_key'])->exists()) {
                throw ValidationException::withMessages([
                    'option_ids' => ['Tổ hợp lựa chọn này đã tồn tại cho sản phẩm.'],
                ]);
            }

            $variant = $this->productVariantRepository->create($data);
            $variant->options()->attach($optionIds->all());
            $this->uploadImages($variant, $images);

            return $variant->load(['product', 'files', 'options.productVariantGroup.group']);
        });
    }

    public function createAllCombinations(array $data): array
    {
        $data['price'] = isset($data['price']) && $data['price'] !== '' && $data['price'] !== null ? $data['price'] : 0;
        $data['stock'] = isset($data['stock']) && $data['stock'] !== '' && $data['stock'] !== null ? $data['stock'] : 0;

        return DB::transaction(function () use ($data) {
            $product = Product::query()
                ->with(['variantGroupConfigurations.options'])
                ->lockForUpdate()
                ->findOrFail($data['product_id']);

            $groups = $product->variantGroupConfigurations
                ->map(function ($configuration) {
                    $options = $configuration->options
                        ->where('is_active', true)
                        ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                        ->values();

                    if ($configuration->is_required && $options->isEmpty()) {
                        throw ValidationException::withMessages([
                            'generate_all_combinations' => ["Nhóm {$configuration->group?->group_name} chưa có giá trị hoạt động."],
                        ]);
                    }

                    return $options;
                })
                ->filter(fn ($options) => $options->isNotEmpty())
                ->values();

            if ($groups->isEmpty()) {
                throw ValidationException::withMessages([
                    'generate_all_combinations' => ['Sản phẩm chưa có giá trị biến thể để tạo tổ hợp.'],
                ]);
            }

            $combinations = [[]];
            foreach ($groups as $options) {
                $combinations = collect($combinations)
                    ->flatMap(fn ($combination) => $options->map(fn ($option) => [...$combination, $option]))
                    ->all();
                if (count($combinations) > 500) {
                    throw ValidationException::withMessages([
                        'generate_all_combinations' => ['Số tổ hợp vượt quá giới hạn 500 biến thể.'],
                    ]);
                }
            }

            unset($data['option_ids'], $data['images'], $data['sku'], $data['generate_all_combinations']);
            $created = collect();
            $skipped = 0;
            $baseSku = $this->skuPart($product->sku ?: 'SP-' . $product->id);

            foreach ($combinations as $combination) {
                $optionIds = collect($combination)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                $combinationKey = hash('sha256', $optionIds->implode(':'));
                if ($product->variants()->where('combination_key', $combinationKey)->exists()) {
                    $skipped++;
                    continue;
                }

                $sku = collect($combination)
                    ->pluck('option_code')
                    ->prepend($baseSku)
                    ->map(fn ($part) => $this->skuPart($part))
                    ->filter()
                    ->implode('-');
                $sku = Str::limit($sku, 100, '');

                if (ProductVariant::query()->where('sku', $sku)->exists()) {
                    throw ValidationException::withMessages(['sku' => ["SKU {$sku} đã tồn tại."]]);
                }

                $variant = $this->productVariantRepository->create([
                    ...$data,
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'combination_key' => $combinationKey,
                ]);
                $variant->options()->attach($optionIds->all());
                $created->push($variant->load(['product', 'files', 'options.productVariantGroup.group']));
            }

            return ['variants' => $created, 'created' => $created->count(), 'skipped' => $skipped];
        });
    }

    public function update($variant, array $data)
    {
        if (array_key_exists('price', $data)) {
            $data['price'] = isset($data['price']) && $data['price'] !== '' && $data['price'] !== null ? $data['price'] : 0;
        }
        if (array_key_exists('stock', $data)) {
            $data['stock'] = isset($data['stock']) && $data['stock'] !== '' && $data['stock'] !== null ? $data['stock'] : 0;
        }

        return DB::transaction(function () use ($variant, $data) {
            $variant = $variant->newQuery()->lockForUpdate()->findOrFail($variant->id);
            $productId = (int) ($data['product_id'] ?? $variant->product_id);
            $product = Product::query()->with('variantGroupConfigurations')->lockForUpdate()->findOrFail($productId);
            $optionIds = array_key_exists('option_ids', $data)
                ? $data['option_ids']
                : $variant->options()->pluck('variant_options.id')->all();
            $images = $data['images'] ?? [];
            $optionIds = $this->validateAndNormalizeOptions($product, $optionIds);
            $combinationKey = hash('sha256', $optionIds->implode(':'));

            if ($product->variants()
                ->where('combination_key', $combinationKey)
                ->whereKeyNot($variant->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'option_ids' => ['Tổ hợp lựa chọn này đã tồn tại cho sản phẩm.'],
                ]);
            }

            unset($data['option_ids'], $data['images']);
            $data['product_id'] = $productId;
            $data['combination_key'] = $combinationKey;
            $variant = $this->productVariantRepository->edit($variant, $data);
            $variant->options()->sync($optionIds->all());
            $this->uploadImages($variant, $images);

            return $variant->load(['product', 'files', 'options.productVariantGroup.group']);
        });
    }

    public function delete($variant)
    {
        return $this->productVariantRepository->delete($variant);
    }

    private function validateAndNormalizeOptions(Product $product, array $optionIds)
    {
        $optionIds = collect($optionIds)->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $options = VariantOption::query()->with('productVariantGroup')->whereIn('id', $optionIds)->where('is_active', true)->get();

        if ($options->count() !== $optionIds->count()) {
            throw ValidationException::withMessages([
                'option_ids' => ['Một hoặc nhiều lựa chọn không tồn tại hoặc đã ngừng hoạt động.'],
            ]);
        }

        $allowedGroups = $product->variantGroupConfigurations->pluck('id');
        $selectedGroups = $options->pluck('product_variant_group_id');

        if ($selectedGroups->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'option_ids' => ['Mỗi nhóm biến thể chỉ được chọn một lựa chọn.'],
            ]);
        }

        if ($selectedGroups->diff($allowedGroups)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'option_ids' => ['Lựa chọn chứa nhóm không được cấu hình cho sản phẩm.'],
            ]);
        }

        $requiredGroups = $product->variantGroupConfigurations
            ->filter(fn ($group) => (bool) $group->is_required)
            ->pluck('id');

        if ($requiredGroups->diff($selectedGroups)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'option_ids' => ['Chưa chọn đủ các nhóm biến thể bắt buộc.'],
            ]);
        }

        return $optionIds;
    }

    private function uploadImages($variant, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $this->fileService->uploadMany($images, $variant, [
            'disk' => 'public',
            'directory' => 'product-variants/' . $variant->id,
            'type' => 'image',
        ]);
    }

    private function skuPart(string $value): string
    {
        return Str::upper(Str::slug($value, '-'));
    }
}
