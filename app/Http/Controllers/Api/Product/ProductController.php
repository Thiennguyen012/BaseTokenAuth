<?php

namespace App\Http\Controllers\Api\Product;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\Product\ProductService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class ProductController extends Controller
{
    use ValidatesRequestData;

    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Danh sách sản phẩm",
     *     tags={"Products"},
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
        $search = $request->query('search', '');

        $products = $this->productService->paginate($perPage, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.product')]),
            'data' => ProductResource::collection($products->getCollection()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Tạo sản phẩm kèm ảnh",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(ref="#/components/schemas/StoreProductRequest")
     *     )),
     *     @OA\Response(response=201, description="Đã tạo", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=201),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *     )),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.product')]),
                'data' => new ProductResource($product),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.product')]));
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Chi tiết sản phẩm",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.product')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.product')]),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/products/{id}",
     *     summary="Cập nhật sản phẩm kèm ảnh",
     *     description="Gửi multipart/form-data bằng POST và đặt _method=PUT.",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(ref="#/components/schemas/UpdateProductRequest")
     *     )),
     *     @OA\Response(response=200, description="Đã cập nhật", @OA\JsonContent(
     *         @OA\Property(property="status_code", type="integer", example=200),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *     )),
     *     @OA\Response(response=404, description="Không tồn tại"),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            $product = $this->productService->find($id);

            if (!$product) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.product')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $updatedProduct = $this->productService->update($product, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.product')]),
                'data' => new ProductResource($updatedProduct),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.product')]));
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Xóa sản phẩm",
     *     tags={"Products"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã xóa"),
     *     @OA\Response(response=404, description="Không tồn tại")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = $this->productService->find($id);

            if (!$product) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.product')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->productService->delete($product);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.product')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.product')]));
        }
    }
}
