<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserAvatarService
{
    public function store(User $user, UploadedFile $avatar): string
    {
        $directory = "user/avatar/{$user->id}";
        $extension = $avatar->getClientOriginalExtension();
        $fileName = "{$user->id}_avartar_" . now()->timestamp . ".{$extension}";

        $path = $avatar->storeAs($directory, $fileName, 'public');

        if ($user->avatar && $user->avatar !== $path) {
            Storage::disk('public')->delete($user->avatar);
        }

        return $path;
    }
}
