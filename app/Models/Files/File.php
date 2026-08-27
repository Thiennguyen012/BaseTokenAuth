<?php

namespace App\Models\Files;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = [
        'title',
        'file_name',
        'disk',
        'path',
        'external_url',
        'mime_type',
        'size',
        'model_type',
        'model_id',
        'type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'model_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (File $file): void {
            if ($file->disk && $file->path) {
                Storage::disk($file->disk)->delete($file->path);
            }
            if ($file->model_type && $file->model_id && $file->type) {
                $modelClass = $file->model_type;
                if (class_exists($modelClass)) {
                    $owner = $modelClass::find($file->model_id);
                    if ($owner && isset($owner->{$file->type . '_path'})) {
                        $owner->update([$file->type . '_path' => null]);
                    }
                }
            }
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }
}
