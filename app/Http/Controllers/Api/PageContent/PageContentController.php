<?php

namespace App\Http\Controllers\Api\PageContent;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\PageContent\StorePageContentRequest;
use App\Http\Requests\PageContent\UpdatePageContentRequest;
use App\Http\Resources\PageContentResource;
use App\Services\PageContent\PageContentService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class PageContentController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected PageContentService $pageContentService) {}

    /**
     * @OA\Get(
     *     path="/api/page-contents",
     *     summary="Danh sách nội dung trang",
     *     tags={"Page Contents"},
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

        $pageContents = $this->pageContentService->paginate($perPage, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.page_content')]),
            'data' => PageContentResource::collection($pageContents->getCollection()),
            'meta' => [
                'current_page' => $pageContents->currentPage(),
                'last_page' => $pageContents->lastPage(),
                'per_page' => $pageContents->perPage(),
                'total' => $pageContents->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/page-contents",
     *     summary="Tạo nội dung trang",
     *     tags={"Page Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StorePageContentRequest")),
     *     @OA\Response(response=201, description="Đã tạo", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=201),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageContentResource")
     *     )),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StorePageContentRequest $request): JsonResponse
    {
        try {
            $pageContent = $this->pageContentService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.page_content')]),
                'data' => new PageContentResource($pageContent),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.page_content')]));
        }
    }

    /**
     * @OA\Get(
     *     path="/api/page-contents/{id}",
     *     summary="Chi tiết nội dung trang (theo ID hoặc Slug)",
     *     tags={"Page Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageContentResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $pageContent = $this->pageContentService->find($id);

        if (!$pageContent) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.page_content')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.page_content')]),
            'data' => new PageContentResource($pageContent),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/page-contents/{id}",
     *     summary="Cập nhật nội dung trang",
     *     tags={"Page Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UpdatePageContentRequest")),
     *     @OA\Response(response=200, description="Đã cập nhật", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageContentResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdatePageContentRequest $request, string $id): JsonResponse
    {
        try {
            $pageContent = $this->pageContentService->find($id);

            if (!$pageContent) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.page_content')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $updated = $this->pageContentService->update($pageContent, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.page_content')]),
                'data' => new PageContentResource($updated),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.page_content')]));
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/page-contents/{id}",
     *     summary="Xóa nội dung trang",
     *     tags={"Page Contents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $pageContent = $this->pageContentService->find($id);

            if (!$pageContent) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.page_content')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->pageContentService->delete($pageContent);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.page_content')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.page_content')]));
        }
    }
}
