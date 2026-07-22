<?php

namespace App\CPU;

use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Auth;
use Random\RandomException;

class Helpers
{
    const LIMIT_PER_PAGE = 20;
    const TYPE_IMAGE = 1;
    const TYPE_IMAGE_IPHONE = 2;
    const TYPE_VIDEO = 3;

    public static function remove_invalid_charcaters($str): string
    {
        return str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', preg_replace('/\s\s+/', ' ', $str));
    }

    public static function permission_check($permission_name): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->is_super_admin == UserRepository::IS_SUPER_ADMIN) {
            return true;
        }

        return $user->roles()->whereHas('permissions', function ($query) use ($permission_name) {
            $query->where('permission_name', $permission_name);
        })->exists();
    }
    static function getExtension($ex)
    {
        $ex_img = ['png', 'jpg', 'jpeg'];
        $ex_iphone_img = ['heif', 'heic'];
        $ex_video = ['mp4', 'mov'];
        if (in_array($ex, $ex_img)) {
            return self::TYPE_IMAGE;
        }
        if (in_array($ex, $ex_iphone_img)) {
            return self::TYPE_IMAGE_IPHONE;
        }
        if (in_array($ex, $ex_video)) {
            return self::TYPE_VIDEO;
        }
        return false;
    }


    public static function image_path($image_path): string
    {
        return $image_path ? asset('/storage' . $image_path) : '';
    }

    /**
     * @throws RandomException
     */
    public static function generateUniqueString($length = 16): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function cleanValue($value): string
    {
        if (is_null($value)) return '';
        return trim((string)$value);
    }

}

// if (!function_exists('translate')) {
//     function translate($key): string
//     {
//         return __('messages.' . $key);
//     }
// }



