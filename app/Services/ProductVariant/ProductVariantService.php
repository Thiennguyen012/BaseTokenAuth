<?php

namespace App\Services\ProductVariant;

use App\Models\Products\Product;
use App\Models\Variants\VariantOption;
use App\Repositories\ProductVariant\ProductVariantInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    public function __construct(protected ProductVariantInterface $productVariantRepository) {}

    public function paginate(int $limit = 10, string $search = '', ?int $productId = null)
    {
        $where = [];

        if ($search !== '') {
            $where['sku'] = ['sku', 'like', $search];
        }

        if ($productId !== null) {
            $where['product_id'] = $productId;
        }

        return $this->productVariantRepository->paginate(
            $where,
            ['id' => 'desc'],
            ['*'],
            ['options.group'],
            $limit
        );
    }

    public function find($id)
    {
        return $this->productVariantRepository->first(
            ['id' => $id],
            [],
            ['*'],
            ['options.group']
        );
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->with('variantGroups')->lockForUpdate()->findOrFail($data['product_id']);
            $optionIds = $this->validateAndNormalizeOptions($product, $data['option_ids']);

            unset($data['option_ids']);
            $data['combination_key'] = hash('sha256', $optionIds->implode(':'));

            if ($product->variants()->where('combination_key', $data['combination_key'])->exists()) {
                throw ValidationException::withMessages([
                    'option_ids' => ['Tổ hợp lựa chọn này đã tồn tại cho sản phẩm.'],
                ]);
            }

            $variant = $this->productVariantRepository->create($data);
            $variant->options()->attach($optionIds->all());

            return $variant->load('options.group');
        });
    }

    public function update($variant, array $data)
    {
        return DB::transaction(function () use ($variant, $data) {
            $variant = $variant->newQuery()->lockForUpdate()->findOrFail($variant->id);
            $productId = (int) ($data['product_id'] ?? $variant->product_id);
            $product = Product::query()->with('variantGroups')->lockForUpdate()->findOrFail($productId);
            $optionIds = array_key_exists('option_ids', $data)
                ? $data['option_ids']
                : $variant->options()->pluck('variant_options.id')->all();
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

            unset($data['option_ids']);
            $data['product_id'] = $productId;
            $data['combination_key'] = $combinationKey;
            $variant = $this->productVariantRepository->edit($variant, $data);
            $variant->options()->sync($optionIds->all());

            return $variant->load('options.group');
        });
    }

    public function delete($variant)
    {
        return $this->productVariantRepository->delete($variant);
    }

    private function validateAndNormalizeOptions(Product $product, array $optionIds)
    {
        $optionIds = collect($optionIds)->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $options = VariantOption::query()->whereIn('id', $optionIds)->where('is_active', true)->get();

        if ($options->count() !== $optionIds->count()) {
            throw ValidationException::withMessages([
                'option_ids' => ['Một hoặc nhiều lựa chọn không tồn tại hoặc đã ngừng hoạt động.'],
            ]);
        }

        $allowedGroups = $product->variantGroups->pluck('id');
        $selectedGroups = $options->pluck('variant_group_id');

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

        $requiredGroups = $product->variantGroups
            ->filter(fn ($group) => (bool) $group->pivot->is_required)
            ->pluck('id');

        if ($requiredGroups->diff($selectedGroups)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'option_ids' => ['Chưa chọn đủ các nhóm biến thể bắt buộc.'],
            ]);
        }

        return $optionIds;
    }
}
