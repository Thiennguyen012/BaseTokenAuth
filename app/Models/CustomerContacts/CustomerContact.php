<?php

namespace App\Models\CustomerContacts;

use App\Models\Categories\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'category_id',
        'consultation_content',
    ];

    protected function casts(): array
    {
        return ['category_id' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
