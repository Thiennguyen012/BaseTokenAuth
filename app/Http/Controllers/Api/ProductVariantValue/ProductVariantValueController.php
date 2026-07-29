<?php

namespace App\Http\Controllers\Api\ProductVariantValue;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantValue\StoreProductVariantValueRequest;
use App\Http\Resources\ProductVariantValueResource;
use App\Services\ProductVariantValue\ProductVariantValueService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductVariantValueController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected ProductVariantValueService $productVariantValueService) {}

    public function store(StoreProductVariantValueRequest $request): JsonResponse
    {
        try {
            $productVariantValue = $this->productVariantValueService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.product_variant_value')]),
                'data' => new ProductVariantValueResource($productVariantValue),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.product_variant_value')]));
        }
    }
}
