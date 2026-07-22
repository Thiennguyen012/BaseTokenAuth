<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\User\UserAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(UserAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $deviceInfo = [
            'device_name' => $request->input('device_name', 'Unknown'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $result = $this->authService->login($credentials, $deviceInfo);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.success', ['entity' => __('messages.entities.login')]),
            'data' => [
                'user' => $result['user'],
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'token_type' => $result['token_type'],
                'access_token_expires_in' => $result['access_token_expires_in'],
                'refresh_token_expires_in' => $result['refresh_token_expires_in'],
            ]
        ]);
    }

    // public function register(RegisterRequest $request)
    // {
    //     $user = $this->authService->register($request->validated());

    //     return response()->json([
    //         'status_code' => Response::HTTP_CREATED,
    //         'message' => __('messages.common.created', ['entity' => __('messages.entities.user')]),
    //         'data' => new UserResource($user),
    //     ], Response::HTTP_CREATED);
    // }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $result = $this->authService->refresh($request->refresh_token);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.success', ['entity' => __('messages.entities.token_refresh')]),
            'data' => [
                'user' => $result['user'],
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'token_type' => $result['token_type'],
                'access_token_expires_in' => $result['access_token_expires_in'],
                'refresh_token_expires_in' => $result['refresh_token_expires_in'],
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->validate([
            'refresh_token' => 'nullable|string',
        ]);

        $this->authService->logout($request->user(), $request->refresh_token);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.success', ['entity' => __('messages.entities.logout')]),
        ]);
    }

    public function logoutFromDevice(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $this->authService->logoutFromDevice($request->user(), $request->refresh_token);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Logged out from device successfully',
        ]);
    }

    public function logoutFromAllDevices(Request $request)
    {
        $this->authService->logoutFromAllDevices($request->user());

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Logged out from all devices successfully',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('roles.permissions');
        $permissions = $user->roles
            ->flatMap(function ($role) {
                return $role->permissions;
            })
            ->unique('id')
            ->values();
        $payload = $user->toArray();
        unset($payload['roles']);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.fetched', ['entity' => __('messages.entities.profile')]),
            'data' => [
                ...$payload,
                'permissions' => $permissions,
            ]
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $updatedUser = $this->authService->update($request->user(), $data);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => __('messages.common.updated', ['entity' => __('messages.entities.profile')]),
            'data' => $updatedUser
        ]);
    }
}
