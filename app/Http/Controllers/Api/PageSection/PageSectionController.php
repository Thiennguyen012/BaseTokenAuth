<?php

namespace App\Http\Controllers\Api\PageSection;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\PageSection\StorePageSectionRequest;
use App\Http\Requests\PageSection\UpdatePageSectionRequest;
use App\Http\Resources\PageSectionResource;
use App\Services\PageSection\PageSectionService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class PageSectionController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected PageSectionService $pageSectionService) {}

    /**
     * @OA\Get(
     *     path="/admin/api/page-sections",
     *     summary="Danh sách mục trang",
     *     tags={"Page Sections"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page_content_id", in="query", @OA\Schema(type="integer")),
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
        $pageContentId = $request->filled('page_content_id') ? (int) $request->query('page_content_id') : null;

        $pageSections = $this->pageSectionService->paginate($perPage, $search, $pageContentId);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.page_section')]),
            'data' => PageSectionResource::collection($pageSections->getCollection()),
            'meta' => [
                'current_page' => $pageSections->currentPage(),
                'last_page' => $pageSections->lastPage(),
                'per_page' => $pageSections->perPage(),
                'total' => $pageSections->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/api/page-sections",
     *     summary="Tạo mục trang",
     *     tags={"Page Sections"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/StorePageSectionRequest"))),
     *     @OA\Response(response=201, description="Đã tạo", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=201),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageSectionResource")
     *     )),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StorePageSectionRequest $request): JsonResponse
    {
        try {
            $pageSection = $this->pageSectionService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.page_section')]),
                'data' => new PageSectionResource($pageSection),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.page_section')]));
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/api/page-sections/{id}",
     *     summary="Chi tiết mục trang",
     *     tags={"Page Sections"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageSectionResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $pageSection = $this->pageSectionService->find($id);

        if (!$pageSection) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.page_section')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.page_section')]),
            'data' => new PageSectionResource($pageSection),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/api/page-sections/{id}",
     *     summary="Cập nhật mục trang",
     *     tags={"Page Sections"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/UpdatePageSectionRequest"))),
     *     @OA\Response(response=200, description="Đã cập nhật", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageSectionResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdatePageSectionRequest $request, string $id): JsonResponse
    {
        try {
            $pageSection = $this->pageSectionService->find($id);

            if (!$pageSection) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.page_section')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $updated = $this->pageSectionService->update($pageSection, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.page_section')]),
                'data' => new PageSectionResource($updated),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.page_section')]));
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/api/page-sections/{id}",
     *     summary="Xóa mục trang",
     *     tags={"Page Sections"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $pageSection = $this->pageSectionService->find($id);

            if (!$pageSection) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.page_section')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->pageSectionService->delete($pageSection);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.page_section')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.page_section')]));
        }
    }
}
