<?php

namespace App\Models\PageContents;

use App\Models\PageSections\PageSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageContent extends Model
{
    protected $fillable = [
        'slug',
        'title',
    ];

    protected static function booted(): void
    {
        static::deleting(function (PageContent $page): void {
            $page->sections()->get()->each->delete();
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
