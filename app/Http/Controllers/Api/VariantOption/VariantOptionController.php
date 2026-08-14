<?php

namespace App\Http\Controllers\Api\VariantOption;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\VariantOption\StoreVariantOptionRequest;
use App\Http\Requests\VariantOption\UpdateVariantOptionRequest;
use App\Http\Resources\VariantOptionResource;
use App\Services\VariantOption\VariantOptionService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class VariantOptionController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected VariantOptionService $variantOptionService) {}

    /**
     * @OA\Get(
     *     path="/admin/api/variant-options",
     *     summary="Danh sách giá trị biến thể",
     *     tags={"Variant Options"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="product_variant_group_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=401, description="Chưa xác thực")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $perPage = $perPage > 0 ? min($perPage, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = (string) $request->query('search', '');
        $configurationId = $request->filled('product_variant_group_id')
            ? (int) $request->query('product_variant_group_id')
            : null;

        $variantOptions = $this->variantOptionService->paginate($perPage, $search, $configurationId);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.variant_option')]),
            'data' => VariantOptionResource::collection($variantOptions->getCollection()),
            'meta' => [
                'current_page' => $variantOptions->currentPage(),
                'last_page' => $variantOptions->lastPage(),
                'per_page' => $variantOptions->perPage(),
                'total' => $variantOptions->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/api/variant-options",
     *     summary="Tạo giá trị biến thể",
     *     tags={"Variant Options"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreVariantOptionRequest")),
     *     @OA\Response(response=201, description="Đã tạo", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=201),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/VariantOptionResource")
     *     )),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreVariantOptionRequest $request): JsonResponse
    {
        try {
            $variantOption = $this->variantOptionService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.variant_option')]),
                'data' => new VariantOptionResource($variantOption),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.variant_option')]));
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/api/variant-options/{id}",
     *     summary="Chi tiết giá trị biến thể",
     *     tags={"Variant Options"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/VariantOptionResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $variantOption = $this->variantOptionService->find($id);

        if (!$variantOption) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.variant_option')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.variant_option')]),
            'data' => new VariantOptionResource($variantOption),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/api/variant-options/{id}",
     *     summary="Cập nhật giá trị biến thể",
     *     tags={"Variant Options"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UpdateVariantOptionRequest")),
     *     @OA\Response(response=200, description="Đã cập nhật", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/VariantOptionResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdateVariantOptionRequest $request, string $id): JsonResponse
    {
        try {
            $variantOption = $this->variantOptionService->find($id);

            if (!$variantOption) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_option')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $variantOption = $this->variantOptionService->update($variantOption, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.variant_option')]),
                'data' => new VariantOptionResource($variantOption),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.variant_option')]));
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/api/variant-options/{id}",
     *     summary="Xóa giá trị biến thể",
     *     tags={"Variant Options"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $variantOption = $this->variantOptionService->find($id);

            if (!$variantOption) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_option')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->variantOptionService->delete($variantOption);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.variant_option')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.variant_option')]));
        }
    }
}
