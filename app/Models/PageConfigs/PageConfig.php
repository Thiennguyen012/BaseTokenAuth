<?php

namespace App\Models\PageConfigs;

use App\Models\Concerns\HasFiles;
use Illuminate\Database\Eloquent\Model;

class PageConfig extends Model
{
    use HasFiles;

    protected $fillable = [
        'company_name',
        'slogan',
        'description',
        'addresses',
        'hotline',
        'email',
        'working_hour',
        'socials',
        'favicon_path',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'addresses' => 'array',
            'socials' => 'array',
        ];
    }
}
