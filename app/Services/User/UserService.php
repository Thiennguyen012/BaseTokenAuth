<?php

namespace App\Services\User;

use App\Repositories\User\UserInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $userRepository;
    protected $userAvatarService;

    public function __construct(
        UserInterface $userRepository,
        UserAvatarService $userAvatarService
    ) {
        $this->userRepository = $userRepository;
        $this->userAvatarService = $userAvatarService;
    }

    public function getAll($search = '')
    {
        $where = [];
        $orderBy = ['name' => 'asc'];

        if ($search) {
            $where['orWhere'] = [
                'name' => ['name', 'like', '%' . $search . '%'],
                'phone' => ['phone', 'like', '%' . $search . '%']
            ];
        }

        return $this->userRepository->get($where, $orderBy, ['*']);
    }

    public function paginate($limit = 10, $search = '')
    {
        $where = [];
        $orderBy = ['created_at' => 'desc'];

        if ($search) {
            $where['orWhere'] = [
                'name' => ['name', 'like', '%' . $search . '%'],
                'email' => ['email', 'like', '%' . $search . '%'],
                'phone' => ['phone', 'like', '%' . $search . '%']
            ];
        }

        return $this->userRepository->paginate($where, $orderBy, ['*'], [], $limit);
    }

    public function find($id)
    {
        return $this->userRepository->find($id);
    }

    public function findByEmail($email)
    {
        return $this->userRepository->findByEmail($email);
    }

    public function findByPhone($phone)
    {
        return $this->userRepository->findByPhone($phone);
    }

    public function create($data)
    {
        $avatar = $data['avatar'] ?? null;
        unset($data['avatar'], $data['password_confirmation']);

        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepository->create($data);

        if ($avatar instanceof UploadedFile) {
            $user = $this->userRepository->edit($user, [
                'avatar' => $this->userAvatarService->store($user, $avatar),
            ]);
        }

        return $user;
    }

    public function update($user, $data)
    {
        $avatar = $data['avatar'] ?? null;
        unset($data['avatar'], $data['password_confirmation']);

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($avatar instanceof UploadedFile) {
            $data['avatar'] = $this->userAvatarService->store($user, $avatar);
        }

        return $this->userRepository->edit($user, $data);
    }

    public function delete($user)
    {
        return $this->userRepository->delete($user);
    }

    public function restore($user)
    {
        return $this->userRepository->restore($user);
    }

    public function forceDelete($user)
    {
        return $this->userRepository->forceDelete($user);
    }
}
