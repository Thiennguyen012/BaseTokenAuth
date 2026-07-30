<?php

namespace App\Models\PageSections;

use App\Models\Concerns\HasFiles;
use App\Models\PageContents\PageContent;
use App\Models\SectionItems\SectionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    use HasFiles;

    protected $fillable = [
        'page_content_id',
        'title',
        'subtitle',
        'content',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'page_content_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PageSection $section): void {
            $section->items()->get()->each->delete();
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(PageContent::class, 'page_content_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
