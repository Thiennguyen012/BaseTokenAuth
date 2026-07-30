<?php

namespace App\Models\Concerns;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFiles
{
    protected static function bootHasFiles(): void
    {
        static::deleting(function ($model): void {
            $usesSoftDeletes = method_exists($model, 'isForceDeleting');

            if (!$usesSoftDeletes || $model->isForceDeleting()) {
                $model->files()->get()->each->delete();
            }
        });
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model', 'model_type', 'model_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
