<?php

namespace App\Http\Controllers\Api\TagGroup;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagGroupResource;
use App\Services\TagGroup\TagGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagGroupController extends Controller
{
    public function __construct(protected TagGroupService $tagGroupService) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $limit = $limit > 0 ? min($limit, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = (string) $request->query('search', '');

        $items = $this->tagGroupService->paginate($limit, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy danh sách nhóm nhãn thành công',
            'data' => TagGroupResource::collection($items->getCollection()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $item = $this->tagGroupService->find($id);
        abort_if(!$item, Response::HTTP_NOT_FOUND, 'Không tìm thấy nhóm nhãn');

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy chi tiết nhóm nhãn thành công',
            'data' => new TagGroupResource($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $item = $this->tagGroupService->create($validated);

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'Tạo nhóm nhãn thành công',
            'data' => new TagGroupResource($item),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = $this->tagGroupService->find($id);
        abort_if(!$item, Response::HTTP_NOT_FOUND, 'Không tìm thấy nhóm nhãn');

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $item = $this->tagGroupService->update($item, $validated);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Cập nhật nhóm nhãn thành công',
            'data' => new TagGroupResource($item),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $item = $this->tagGroupService->find($id);
        abort_if(!$item, Response::HTTP_NOT_FOUND, 'Không tìm thấy nhóm nhãn');

        $this->tagGroupService->delete($item);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Xóa nhóm nhãn thành công',
        ]);
    }
}
