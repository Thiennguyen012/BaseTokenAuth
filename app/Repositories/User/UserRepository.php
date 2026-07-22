<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Base\BaseRepository;

class UserRepository extends BaseRepository implements UserInterface
{

    const STATUS_ACTIVE = 1;
    const STATUS_BLOCK = 0;

    const IS_SUPER_ADMIN = 1;

    public function model()
    {
        // TODO: Implement model() method.
        return User::class;
    }

    public function findByPhone(string $phone)
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    static function getStatus(): array
    {
        return [
            self::STATUS_ACTIVE => 'active',
            self::STATUS_BLOCK => 'block',
        ];
    }
}
