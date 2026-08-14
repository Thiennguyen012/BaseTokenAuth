<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\ReplaceFileRequest;
use App\Http\Requests\File\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Services\File\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class FileController extends Controller
{
    public function __construct(protected FileService $fileService) {}

    /**
     * @OA\Get(
     *     path="/admin/api/files/{id}", summary="Chi tiết file", tags={"Files"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $file = $this->fileService->find($id);
        return $file
            ? response()->json(['status_code' => 200, 'message' => 'Lấy file thành công', 'data' => new FileResource($file)])
            : response()->json(['status_code' => 404, 'message' => 'File không tồn tại'], 404);
    }

    /**
     * @OA\Patch(
     *     path="/admin/api/files/{id}", summary="Cập nhật metadata hoặc URL video", tags={"Files"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="external_url", type="string", format="uri"),
     *         @OA\Property(property="type", type="string"),
     *         @OA\Property(property="sort_order", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Đã cập nhật"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function update(UpdateFileRequest $request, string $id): JsonResponse
    {
        $file = $this->fileService->find($id);
        if (!$file) {
            return response()->json(['status_code' => 404, 'message' => 'File không tồn tại'], 404);
        }

        $file = $this->fileService->update($file, $request->validated());
        return response()->json(['status_code' => 200, 'message' => 'Cập nhật file thành công', 'data' => new FileResource($file)]);
    }

    /**
     * @OA\Post(
     *     path="/admin/api/files/{id}/replace", summary="Thay nội dung file nhưng giữ nguyên ID và quan hệ", tags={"Files"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(
     *         required={"file"},
     *         @OA\Property(property="file", type="string", format="binary"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="type", type="string"),
     *         @OA\Property(property="sort_order", type="integer")
     *     ))),
     *     @OA\Response(response=200, description="Đã thay file"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function replace(ReplaceFileRequest $request, string $id): JsonResponse
    {
        $file = $this->fileService->find($id);
        if (!$file) {
            return response()->json(['status_code' => 404, 'message' => 'File không tồn tại'], 404);
        }

        $data = $request->validated();
        $file = $this->fileService->replace($file, $request->file('file'), $data);
        return response()->json(['status_code' => 200, 'message' => 'Thay file thành công', 'data' => new FileResource($file)]);
    }

    /**
     * @OA\Delete(
     *     path="/admin/api/files/{id}", summary="Xóa file", tags={"Files"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $file = $this->fileService->find($id);
        if (!$file) {
            return response()->json(['status_code' => 404, 'message' => 'File không tồn tại'], 404);
        }

        $this->fileService->delete($file);
        return response()->json(['status_code' => Response::HTTP_OK, 'message' => 'Xóa file thành công']);
    }
}
