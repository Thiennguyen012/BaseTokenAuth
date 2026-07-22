<?php

namespace App\Repositories\User;

use App\Repositories\Base\BaseInterface;

interface UserInterface extends BaseInterface
{
    public function findByPhone(string $phone);
    public function findByEmail(string $email);
}
