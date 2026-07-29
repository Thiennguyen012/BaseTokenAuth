<?php

namespace App\Models\Variants;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_group_id',
        'option_code',
        'option_name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variant_group_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(VariantGroup::class, 'variant_group_id');
    }
}
