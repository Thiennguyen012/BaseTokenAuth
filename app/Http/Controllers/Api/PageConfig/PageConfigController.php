<?php

namespace App\Http\Controllers\Api\PageConfig;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageConfig\UpdatePageConfigRequest;
use App\Http\Resources\PageConfigResource;
use App\Services\PageConfig\PageConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class PageConfigController extends Controller
{
    public function __construct(protected PageConfigService $pageConfigService) {}

    /**
     * @OA\Get(
     *     path="/admin/api/page-configs",
     *     summary="Lấy cấu hình chung của trang",
     *     tags={"Page Configs"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/PageConfigResource")
     *     )),
     *     @OA\Response(response=401, description="Chưa xác thực")
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy cấu hình trang thành công',
            'data' => new PageConfigResource($this->pageConfigService->singleton()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/api/page-configs/{id}",
     *     summary="Lấy cấu hình chung của trang theo ID",
     *     tags={"Page Configs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $config = $this->pageConfigService->find($id);
        abort_if(!$config, Response::HTTP_NOT_FOUND, 'Không tìm thấy cấu hình trang');

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy cấu hình trang thành công',
            'data' => new PageConfigResource($config),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/api/page-configs/{id}",
     *     summary="Cập nhật cấu hình chung của trang",
     *     tags={"Page Configs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdatePageConfigRequest")),
     *     @OA\Response(response=200, description="Đã cập nhật"),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdatePageConfigRequest $request, int $id): JsonResponse
    {
        $config = $this->pageConfigService->find($id);
        abort_if(!$config, Response::HTTP_NOT_FOUND, 'Không tìm thấy cấu hình trang');
        $config = $this->pageConfigService->update($config, $request->validated());

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Cập nhật cấu hình trang thành công',
            'data' => new PageConfigResource($config),
        ]);
    }
}
