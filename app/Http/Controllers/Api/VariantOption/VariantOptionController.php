<?php

namespace App\Http\Controllers\Api\VariantOption;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\VariantOption\StoreVariantOptionRequest;
use App\Http\Requests\VariantOption\UpdateVariantOptionRequest;
use App\Http\Resources\VariantOptionResource;
use App\Services\VariantOption\VariantOptionService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VariantOptionController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected VariantOptionService $variantOptionService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $perPage = $perPage > 0 ? min($perPage, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = (string) $request->query('search', '');
        $variantGroupId = $request->filled('variant_group_id')
            ? (int) $request->query('variant_group_id')
            : null;

        $variantOptions = $this->variantOptionService->paginate($perPage, $search, $variantGroupId);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.variant_option')]),
            'data' => VariantOptionResource::collection($variantOptions->getCollection()),
            'meta' => [
                'current_page' => $variantOptions->currentPage(),
                'last_page' => $variantOptions->lastPage(),
                'per_page' => $variantOptions->perPage(),
                'total' => $variantOptions->total(),
            ],
        ]);
    }

    public function store(StoreVariantOptionRequest $request): JsonResponse
    {
        try {
            $variantOption = $this->variantOptionService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.variant_option')]),
                'data' => new VariantOptionResource($variantOption),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.variant_option')]));
        }
    }

    public function show(string $id): JsonResponse
    {
        $variantOption = $this->variantOptionService->find($id);

        if (!$variantOption) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.variant_option')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.variant_option')]),
            'data' => new VariantOptionResource($variantOption),
        ]);
    }

    public function update(UpdateVariantOptionRequest $request, string $id): JsonResponse
    {
        try {
            $variantOption = $this->variantOptionService->find($id);

            if (!$variantOption) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_option')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $variantOption = $this->variantOptionService->update($variantOption, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.variant_option')]),
                'data' => new VariantOptionResource($variantOption),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.variant_option')]));
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $variantOption = $this->variantOptionService->find($id);

            if (!$variantOption) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_option')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->variantOptionService->delete($variantOption);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.variant_option')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.variant_option')]));
        }
    }
}
