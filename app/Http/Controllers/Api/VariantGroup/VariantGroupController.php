<?php

namespace App\Http\Controllers\Api\VariantGroup;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\VariantGroup\StoreVariantGroupRequest;
use App\Http\Requests\VariantGroup\UpdateVariantGroupRequest;
use App\Http\Resources\VariantGroupResource;
use App\Services\VariantGroup\VariantGroupService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class VariantGroupController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected VariantGroupService $variantGroupService) {}

    /**
     * @OA\Get(
     *     path="/api/variant-groups",
     *     summary="Danh sách nhóm biến thể",
     *     tags={"Variant Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
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

        $variantGroups = $this->variantGroupService->paginate($perPage, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.variant_group')]),
            'data' => VariantGroupResource::collection($variantGroups->getCollection()),
            'meta' => [
                'current_page' => $variantGroups->currentPage(),
                'last_page' => $variantGroups->lastPage(),
                'per_page' => $variantGroups->perPage(),
                'total' => $variantGroups->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/variant-groups",
     *     summary="Tạo nhóm biến thể",
     *     tags={"Variant Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreVariantGroupRequest")),
     *     @OA\Response(response=201, description="Đã tạo", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=201),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/VariantGroupResource")
     *     )),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreVariantGroupRequest $request): JsonResponse
    {
        try {
            $variantGroup = $this->variantGroupService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.variant_group')]),
                'data' => new VariantGroupResource($variantGroup),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.variant_group')]));
        }
    }

    /**
     * @OA\Get(
     *     path="/api/variant-groups/{id}",
     *     summary="Chi tiết nhóm biến thể",
     *     tags={"Variant Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/VariantGroupResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $variantGroup = $this->variantGroupService->find($id);

        if (!$variantGroup) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.variant_group')]),
            'data' => new VariantGroupResource($variantGroup),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/variant-groups/{id}/usage",
     *     summary="Số sản phẩm đang sử dụng nhóm biến thể",
     *     tags={"Variant Groups"}, security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function usage(string $id): JsonResponse
    {
        $count = $this->variantGroupService->usageCount($id);

        if ($count === null) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy số sản phẩm sử dụng nhóm biến thể thành công',
            'data' => ['products_count' => $count],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/variant-groups/{id}",
     *     summary="Cập nhật nhóm biến thể",
     *     tags={"Variant Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UpdateVariantGroupRequest")),
     *     @OA\Response(response=200, description="Đã cập nhật", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/VariantGroupResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdateVariantGroupRequest $request, string $id): JsonResponse
    {
        try {
            $variantGroup = $this->variantGroupService->find($id);

            if (!$variantGroup) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $variantGroup = $this->variantGroupService->update($variantGroup, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.variant_group')]),
                'data' => new VariantGroupResource($variantGroup),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.variant_group')]));
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/variant-groups/{id}",
     *     summary="Xóa nhóm biến thể",
     *     tags={"Variant Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $variantGroup = $this->variantGroupService->find($id);

            if (!$variantGroup) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->variantGroupService->delete($variantGroup);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.variant_group')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.variant_group')]));
        }
    }
}
