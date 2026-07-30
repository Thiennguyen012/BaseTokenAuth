<?php

namespace App\Models\SectionItems;

use App\Models\Concerns\HasFiles;
use App\Models\PageSections\PageSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionItem extends Model
{
    use HasFiles;

    protected $fillable = [
        'page_section_id',
        'title',
        'subtitle',
        'content',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'page_section_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }
}
