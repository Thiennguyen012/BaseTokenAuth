<?php

namespace App\Http\Controllers\Api\VariantGroup;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\VariantGroup\StoreVariantGroupRequest;
use App\Http\Requests\VariantGroup\UpdateVariantGroupRequest;
use App\Http\Resources\VariantGroupResource;
use App\Services\VariantGroup\VariantGroupService;
use App\Traits\ValidatesRequestData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VariantGroupController extends Controller
{
    use ValidatesRequestData;

    public function __construct(protected VariantGroupService $variantGroupService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $perPage = $perPage > 0 ? min($perPage, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = (string) $request->query('search', '');

        $variantGroups = $this->variantGroupService->paginate($perPage, $search);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.variant_group')]),
            'data' => VariantGroupResource::collection($variantGroups->getCollection()),
            'meta' => [
                'current_page' => $variantGroups->currentPage(),
                'last_page' => $variantGroups->lastPage(),
                'per_page' => $variantGroups->perPage(),
                'total' => $variantGroups->total(),
            ],
        ]);
    }

    public function store(StoreVariantGroupRequest $request): JsonResponse
    {
        try {
            $variantGroup = $this->variantGroupService->create($request->validated());

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.variant_group')]),
                'data' => new VariantGroupResource($variantGroup),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.variant_group')]));
        }
    }

    public function show(string $id): JsonResponse
    {
        $variantGroup = $this->variantGroupService->find($id);

        if (!$variantGroup) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.variant_group')]),
            'data' => new VariantGroupResource($variantGroup),
        ]);
    }

    public function update(UpdateVariantGroupRequest $request, string $id): JsonResponse
    {
        try {
            $variantGroup = $this->variantGroupService->find($id);

            if (!$variantGroup) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $variantGroup = $this->variantGroupService->update($variantGroup, $request->validated());

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.variant_group')]),
                'data' => new VariantGroupResource($variantGroup),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.variant_group')]));
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $variantGroup = $this->variantGroupService->find($id);

            if (!$variantGroup) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.variant_group')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->variantGroupService->delete($variantGroup);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.variant_group')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.variant_group')]));
        }
    }
}
