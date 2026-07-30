<?php

namespace App\Services\File;

use App\Models\Files\File;
use App\Repositories\File\FileInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class FileService
{
    public function __construct(protected FileInterface $fileRepository) {}

    public function upload(UploadedFile $uploadedFile, ?Model $model = null, array $data = []): File
    {
        $disk = $data['disk'] ?? config('filesystems.default', 'local');
        $directory = trim($data['directory'] ?? 'files', '/');
        $path = Storage::disk($disk)->putFile($directory, $uploadedFile);

        if ($path === false) {
            throw new RuntimeException('Không thể lưu file.');
        }

        try {
            $mimeType = $uploadedFile->getMimeType()
                ?: $uploadedFile->getClientMimeType()
                ?: 'application/octet-stream';

            return $this->fileRepository->create([
                'title' => $data['title'] ?? $uploadedFile->getClientOriginalName(),
                'file_name' => $data['file_name'] ?? $uploadedFile->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $uploadedFile->getSize(),
                'model_type' => $model?->getMorphClass(),
                'model_id' => $model?->getKey(),
                'type' => $data['type'] ?? $this->typeFromMime($mimeType),
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    public function uploadMany(iterable $uploadedFiles, ?Model $model = null, array $data = []): Collection
    {
        $files = collect();

        try {
            foreach ($uploadedFiles as $uploadedFile) {
                $files->push($this->upload($uploadedFile, $model, $data));
            }

            return $files;
        } catch (Throwable $exception) {
            $files->each(fn (File $file) => $file->delete());

            throw $exception;
        }
    }

    public function find($id): ?File
    {
        return $this->fileRepository->find($id);
    }

    public function getForModel(Model $model, ?string $type = null): Collection
    {
        $where = [
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
        ];

        if ($type !== null) {
            $where['type'] = $type;
        }

        return $this->fileRepository->get($where, ['sort_order' => 'asc', 'id' => 'asc'], ['*']);
    }

    public function attach(File $file, Model $model): File
    {
        return $this->fileRepository->edit($file, [
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
        ]);
    }

    public function update(File $file, array $data): File
    {
        $attributes = array_intersect_key($data, array_flip([
            'title',
            'file_name',
            'type',
            'external_url',
            'sort_order',
        ]));

        if (!empty($attributes['external_url'])) {
            $oldDisk = $file->disk;
            $oldPath = $file->path;
            $attributes += ['disk' => null, 'path' => null, 'mime_type' => null, 'size' => null];
            $attributes['type'] ??= 'video';
            $updated = $this->fileRepository->edit($file, $attributes);

            if ($oldDisk && $oldPath) {
                Storage::disk($oldDisk)->delete($oldPath);
            }

            return $updated;
        }

        return $this->fileRepository->edit($file, $attributes);
    }

    public function createExternalUrl(Model $model, string $url, array $data = []): File
    {
        return $this->fileRepository->create([
            'title' => $data['title'] ?? $url,
            'file_name' => null,
            'disk' => null,
            'path' => null,
            'external_url' => $url,
            'mime_type' => null,
            'size' => null,
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'type' => $data['type'] ?? 'video',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function replace(File $file, UploadedFile $uploadedFile, array $data = []): File
    {
        $disk = $data['disk'] ?? $file->disk ?? 'public';
        $directory = trim($data['directory'] ?? dirname($file->path ?: 'files/file'), '/.');
        $path = Storage::disk($disk)->putFile($directory ?: 'files', $uploadedFile);

        if ($path === false) {
            throw new RuntimeException('Không thể lưu file.');
        }

        $oldDisk = $file->disk;
        $oldPath = $file->path;
        $mimeType = $uploadedFile->getMimeType() ?: $uploadedFile->getClientMimeType() ?: 'application/octet-stream';

        try {
            $updated = $this->fileRepository->edit($file, [
                'title' => $data['title'] ?? $uploadedFile->getClientOriginalName(),
                'file_name' => $uploadedFile->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'external_url' => null,
                'mime_type' => $mimeType,
                'size' => $uploadedFile->getSize(),
                'type' => $data['type'] ?? $this->typeFromMime($mimeType),
                'sort_order' => $data['sort_order'] ?? $file->sort_order,
            ]);

            if ($oldDisk && $oldPath && ($oldDisk !== $disk || $oldPath !== $path)) {
                Storage::disk($oldDisk)->delete($oldPath);
            }

            return $updated;
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function delete(File $file): bool
    {
        return (bool) $this->fileRepository->delete($file);
    }

    private function typeFromMime(string $mimeType): string
    {
        return str_starts_with($mimeType, 'image/')
            ? 'image'
            : (str_starts_with($mimeType, 'video/') ? 'video' : 'file');
    }
}
