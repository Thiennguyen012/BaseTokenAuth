<?php

namespace App\Repositories\RefreshToken;

use App\Repositories\Base\BaseInterface;

interface RefreshTokenInterface extends BaseInterface
{
    public function findByToken(string $token);

    public function revokeTokenForUser(int $userId, string $token);

    public function revokeUserTokens(int $userId);

    public function markAsUsed(int $id);
    
    public function deleteExpiredTokens();
    
    public function deleteUserExpiredTokens(int $userId);
}
