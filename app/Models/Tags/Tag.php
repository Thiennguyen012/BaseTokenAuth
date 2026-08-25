<?php

namespace App\Models\Tags;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'tag_group_id',
    ];

    protected $appends = [
        'tag_group_name',
    ];

    public function tagGroup()
    {
        return $this->belongsTo(TagGroup::class, 'tag_group_id');
    }

    public function getTagGroupNameAttribute(): ?string
    {
        return $this->tagGroup?->name;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tag')
            ->withTimestamps();
    }
}
