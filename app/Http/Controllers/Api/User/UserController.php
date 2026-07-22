<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\User\UserService;
use App\Traits\ValidatesRequestData;
use App\CPU\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ValidatesRequestData;
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', Helpers::LIMIT_PER_PAGE);
        $perPage = $perPage > 0 ? min($perPage, Helpers::LIMIT_PER_PAGE) : Helpers::LIMIT_PER_PAGE;
        $search = $request->query('search', '');

        $users = $this->userService->paginate($perPage, $search);
        $users->getCollection()->load('roles');

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.list', ['entity' => __('messages.entities.user')]),
            'data' => UserResource::collection($users->getCollection()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar');
            }

            $user = $this->userService->create($data);

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => __('messages.common.created', ['entity' => __('messages.entities.user')]),
                'data' => new UserResource($user),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.create_error', ['entity' => __('messages.entities.user')]));
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return $this->errorResponse(
                __('messages.common.not_found', ['entity' => __('messages.entities.user')]),
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.user')]),
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $user = $this->userService->find($id);

            if (!$user) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.user')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = $request->validated();
            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar');
            }

            $roleIds = $data['role_ids'] ?? null;
            unset($data['role_ids']);

            $updatedUser = $this->userService->update($user, $data);

            if (array_key_exists('role_ids', $request->validated())) {
                $updatedUser->roles()->sync($roleIds ?? []);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.updated', ['entity' => __('messages.entities.user')]),
                'data' => new UserResource($updatedUser),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.update_error', ['entity' => __('messages.entities.user')]));
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = $this->userService->find($id);

            if (!$user) {
                return $this->errorResponse(
                    __('messages.common.not_found', ['entity' => __('messages.entities.user')]),
                    Response::HTTP_NOT_FOUND
                );
            }

            $this->userService->delete($user);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => __('messages.common.deleted', ['entity' => __('messages.entities.user')]),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, __('messages.common.delete_error', ['entity' => __('messages.entities.user')]));
        }
    }
}
