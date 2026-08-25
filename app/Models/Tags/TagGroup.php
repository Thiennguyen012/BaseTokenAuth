<?php

namespace App\Models\Tags;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagGroup extends Model
{
    use HasFactory;

    protected $table = 'tag_groups';

    protected $fillable = [
        'name',
        'code',
        'sort_order',
    ];

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'tag_group_id')->orderBy('name', 'asc');
    }
}
