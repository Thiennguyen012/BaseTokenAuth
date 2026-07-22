<?php

namespace App\Http\Controllers\Api\Category;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\Category\CategoryService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    use ValidatesRequestData;

    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $perPage = $perPage > 0 ? min($perPage, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = $request->query('search', '');

        $categories = $this->categoryService->paginate($perPage, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.category')]),
            'data' => CategoryResource::collection($categories->getCollection()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.category')]),
                'data' => new CategoryResource($category),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.category')]));
        }
    }

    public function show(string $id): JsonResponse
    {
        $category = $this->categoryService->find($id);

        if (!$category) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.category')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.category')]),
            'data' => new CategoryResource($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        try {
            $category = $this->categoryService->find($id);

            if (!$category) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.category')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $updatedCategory = $this->categoryService->update($category, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.category')]),
                'data' => new CategoryResource($updatedCategory),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.category')]));
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $category = $this->categoryService->find($id);

            if (!$category) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.category')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->categoryService->delete($category);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.category')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.category')]));
        }
    }
}
