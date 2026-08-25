<?php

namespace App\Http\Controllers\Api\Tag;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Services\Tag\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function __construct(protected TagService $tagService) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $limit = $limit > 0 ? min($limit, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = (string) $request->query('search', '');

        $items = $this->tagService->paginate($limit, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy danh sách nhãn (tag) thành công',
            'data' => TagResource::collection($items->getCollection()),
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
        $item = $this->tagService->find($id);
        abort_if(!$item, Response::HTTP_NOT_FOUND, 'Không tìm thấy nhãn sản phẩm');

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Lấy chi tiết nhãn sản phẩm thành công',
            'data' => new TagResource($item),
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $item = $this->tagService->create($request->validated());

        return response()->json([
            'status_code' => Response::HTTP_CREATED,
            'message' => 'Tạo nhãn sản phẩm thành công',
            'data' => new TagResource($item),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateTagRequest $request, string $id): JsonResponse
    {
        $item = $this->tagService->find($id);
        abort_if(!$item, Response::HTTP_NOT_FOUND, 'Không tìm thấy nhãn sản phẩm');

        $item = $this->tagService->update($item, $request->validated());

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Cập nhật nhãn sản phẩm thành công',
            'data' => new TagResource($item),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $item = $this->tagService->find($id);
        abort_if(!$item, Response::HTTP_NOT_FOUND, 'Không tìm thấy nhãn sản phẩm');

        $this->tagService->delete($item);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Xóa nhãn sản phẩm thành công',
        ]);
    }
}
