<?php

namespace App\Http\Controllers\Api\PublicSite;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PageConfigResource;
use App\Http\Resources\PageContentResource;
use App\Http\Resources\ProductResource;
use App\Services\Category\CategoryService;
use App\Services\PageConfig\PageConfigService;
use App\Services\PageContent\PageContentService;
use App\Services\Product\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class LandingController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected CategoryService $categories,
        protected PageContentService $pages,
        protected PageConfigService $pageConfig,
    ) {}

    /** @OA\Get(path="/api/products", summary="Danh sách sản phẩm công khai đang hoạt động", tags={"Public"}, @OA\Parameter(name="search", in="query", @OA\Schema(type="string")), @OA\Parameter(name="category_ids[]", in="query", description="Lọc AND theo danh mục", @OA\Schema(type="array", @OA\Items(type="integer"))), @OA\Parameter(name="sort", in="query", description="latest, price_asc, price_desc, name_asc hoặc name_desc", @OA\Schema(type="string")), @OA\Parameter(name="is_featured", in="query", @OA\Schema(type="boolean")), @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")), @OA\Response(response=200, description="Thành công")) */
    public function products(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('per_page', Helpers::LIMIT_PER_PAGE), Helpers::LIMIT_PER_PAGE));
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->query('category_ids', [])))));
        $sort = $this->normalizeProductSort((string) $request->query('sort', 'latest'));
        $isFeatured = $request->has('is_featured') ? $request->boolean('is_featured') : null;
        $items = $this->products->paginatePublic($limit, (string) $request->query('search', ''), $categoryIds, $sort, $isFeatured);
        return $this->paginated($items, ProductResource::collection($items->getCollection()), 'Lấy danh sách sản phẩm thành công');
    }

    /** @OA\Get(path="/api/products/{id}", summary="Chi tiết sản phẩm công khai theo ID hoặc slug", tags={"Public"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")), @OA\Response(response=200, description="Thành công"), @OA\Response(response=404, description="Không tồn tại")) */
    public function product(string $id): JsonResponse
    {
        $item = $this->products->findPublic($id);
        abort_if(!$item, 404, 'Không tìm thấy sản phẩm');
        return response()->json(['status_code' => 200, 'message' => 'Lấy sản phẩm thành công', 'data' => new ProductResource($item)]);
    }

    /** @OA\Get(path="/api/categories", summary="Danh sách danh mục công khai", tags={"Public"}, @OA\Response(response=200, description="Thành công")) */
    public function categories(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('per_page', Helpers::LIMIT_PER_PAGE), Helpers::LIMIT_PER_PAGE));
        $items = $this->categories->paginate($limit, (string) $request->query('search', ''));
        return $this->paginated($items, CategoryResource::collection($items->getCollection()), 'Lấy danh sách danh mục thành công');
    }

    /** @OA\Get(path="/api/page-contents", summary="Danh sách nội dung trang công khai", tags={"Public"}, @OA\Response(response=200, description="Thành công")) */
    public function pages(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('per_page', Helpers::LIMIT_PER_PAGE), Helpers::LIMIT_PER_PAGE));
        $items = $this->pages->paginate($limit, (string) $request->query('search', ''));
        return $this->paginated($items, PageContentResource::collection($items->getCollection()), 'Lấy nội dung trang thành công');
    }

    /** @OA\Get(path="/api/page-contents/{id}", summary="Chi tiết nội dung trang theo ID hoặc slug", tags={"Public"}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")), @OA\Response(response=200, description="Thành công"), @OA\Response(response=404, description="Không tồn tại")) */
    public function page(string $id): JsonResponse
    {
        $item = $this->pages->find($id);
        abort_if(!$item, 404, 'Không tìm thấy nội dung trang');
        return response()->json(['status_code' => 200, 'message' => 'Lấy nội dung trang thành công', 'data' => new PageContentResource($item)]);
    }

    /** @OA\Get(path="/api/page-configs", summary="Cấu hình chung của landing page", tags={"Public"}, @OA\Response(response=200, description="Thành công")) */
    public function config(): JsonResponse
    {
        return response()->json(['status_code' => 200, 'message' => 'Lấy cấu hình trang thành công', 'data' => new PageConfigResource($this->pageConfig->singleton())]);
    }

    private function normalizeProductSort(string $sort): string
    {
        return in_array($sort, ['latest', 'price_asc', 'price_desc', 'name_asc', 'name_desc'], true)
            ? $sort
            : 'latest';
    }

    private function paginated($paginator, $data, string $message): JsonResponse
    {
        return response()->json(['status_code' => Response::HTTP_OK, 'message' => $message, 'data' => $data, 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }
}
