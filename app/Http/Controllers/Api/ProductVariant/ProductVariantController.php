<?php

namespace App\Http\Controllers\Api\ProductVariant;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariant\StoreProductVariantRequest;
use App\Http\Requests\ProductVariant\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Services\ProductVariant\ProductVariantService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected ProductVariantService $productVariantService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $perPage = $perPage > 0 ? min($perPage, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = (string) $request->query('search', '');
        $productId = $request->filled('product_id') ? (int) $request->query('product_id') : null;
        $variants = $this->productVariantService->paginate($perPage, $search, $productId);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.product_variant')]),
            'data' => ProductVariantResource::collection($variants->getCollection()),
            'meta' => [
                'current_page' => $variants->currentPage(),
                'last_page' => $variants->lastPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
            ],
        ]);
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        try {
            $productVariant = $this->productVariantService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.product_variant')]),
                'data' => new ProductVariantResource($productVariant),
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json(['status_code' => 422, 'message' => 'Dữ liệu biến thể không hợp lệ.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.product_variant')]));
        }
    }

    public function show(string $id): JsonResponse
    {
        $variant = $this->productVariantService->find($id);

        if (!$variant) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.product_variant')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.product_variant')]),
            'data' => new ProductVariantResource($variant),
        ]);
    }

    public function update(UpdateProductVariantRequest $request, string $id): JsonResponse
    {
        try {
            $variant = $this->productVariantService->find($id);

            if (!$variant) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.product_variant')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $variant = $this->productVariantService->update($variant, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.product_variant')]),
                'data' => new ProductVariantResource($variant),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => __('validation.custom.product_variant.invalid_data'),
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.product_variant')]));
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $variant = $this->productVariantService->find($id);

            if (!$variant) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.product_variant')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->productVariantService->delete($variant);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.product_variant')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.product_variant')]));
        }
    }
}
