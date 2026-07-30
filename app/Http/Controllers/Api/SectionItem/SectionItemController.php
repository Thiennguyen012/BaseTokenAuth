<?php

namespace App\Http\Controllers\Api\SectionItem;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\SectionItem\StoreSectionItemRequest;
use App\Http\Requests\SectionItem\UpdateSectionItemRequest;
use App\Http\Resources\SectionItemResource;
use App\Services\SectionItem\SectionItemService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class SectionItemController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected SectionItemService $sectionItemService) {}

    /**
     * @OA\Get(
     *     path="/api/section-items",
     *     summary="Danh sách phần tử mục trang",
     *     tags={"Section Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page_section_id", in="query", @OA\Schema(type="integer")),
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
        $pageSectionId = $request->filled('page_section_id') ? (int) $request->query('page_section_id') : null;

        $sectionItems = $this->sectionItemService->paginate($perPage, $search, $pageSectionId);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.section_item')]),
            'data' => SectionItemResource::collection($sectionItems->getCollection()),
            'meta' => [
                'current_page' => $sectionItems->currentPage(),
                'last_page' => $sectionItems->lastPage(),
                'per_page' => $sectionItems->perPage(),
                'total' => $sectionItems->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/section-items",
     *     summary="Tạo phần tử mục trang",
     *     tags={"Section Items"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/StoreSectionItemRequest"))),
     *     @OA\Response(response=201, description="Đã tạo", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=201),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/SectionItemResource")
     *     )),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreSectionItemRequest $request): JsonResponse
    {
        try {
            $sectionItem = $this->sectionItemService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.section_item')]),
                'data' => new SectionItemResource($sectionItem),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.section_item')]));
        }
    }

    /**
     * @OA\Get(
     *     path="/api/section-items/{id}",
     *     summary="Chi tiết phần tử mục trang",
     *     tags={"Section Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/SectionItemResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $sectionItem = $this->sectionItemService->find($id);

        if (!$sectionItem) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.section_item')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.section_item')]),
            'data' => new SectionItemResource($sectionItem),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/section-items/{id}",
     *     summary="Cập nhật phần tử mục trang",
     *     tags={"Section Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/UpdateSectionItemRequest"))),
     *     @OA\Response(response=200, description="Đã cập nhật", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/SectionItemResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdateSectionItemRequest $request, string $id): JsonResponse
    {
        try {
            $sectionItem = $this->sectionItemService->find($id);

            if (!$sectionItem) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.section_item')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $updated = $this->sectionItemService->update($sectionItem, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.section_item')]),
                'data' => new SectionItemResource($updated),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.section_item')]));
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/section-items/{id}",
     *     summary="Xóa phần tử mục trang",
     *     tags={"Section Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $sectionItem = $this->sectionItemService->find($id);

            if (!$sectionItem) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.section_item')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->sectionItemService->delete($sectionItem);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.section_item')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.section_item')]));
        }
    }
}
