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
            return $this->fileRepository->create([
                'title' => $data['title'] ?? $uploadedFile->getClientOriginalName(),
                'file_name' => $data['file_name'] ?? $uploadedFile->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $uploadedFile->getMimeType()
                    ?: $uploadedFile->getClientMimeType()
                    ?: 'application/octet-stream',
                'size' => $uploadedFile->getSize(),
                'model_type' => $model?->getMorphClass(),
                'model_id' => $model?->getKey(),
                'type' => $data['type'] ?? null,
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

        return $this->fileRepository->get($where, ['id' => 'asc'], ['*']);
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
        return $this->fileRepository->edit($file, array_intersect_key($data, array_flip([
            'title',
            'file_name',
            'type',
        ])));
    }

    public function delete(File $file): bool
    {
        return (bool) $this->fileRepository->delete($file);
    }
}
