<?php

namespace App\Services\PageConfig;

use App\Models\PageConfigs\PageConfig;
use App\Repositories\PageConfig\PageConfigInterface;
use App\Services\File\FileService;

class PageConfigService
{
    public function __construct(protected PageConfigInterface $pageConfigRepository, protected FileService $fileService) {}

    public function singleton(): PageConfig
    {
        return PageConfig::query()->firstOrCreate([], ['company_name' => ''])->load('files');
    }

    public function find(int $id)
    {
        return $this->pageConfigRepository->find($id)?->load('files');
    }

    public function update($pageConfig, array $data)
    {
        $favicon = $data['favicon'][0] ?? null;
        $logo = $data['logo'][0] ?? null;
        unset($data['favicon'], $data['logo']);
        $updated = $this->pageConfigRepository->edit($pageConfig, $data);
        $this->storeSingletonFile($updated, $favicon, 'favicon', 'page-config/favicon', 'favicon_path');
        $this->storeSingletonFile($updated, $logo, 'logo', 'page-config/logo', 'logo_path');
        return $updated->load('files');
    }

    private function storeSingletonFile(PageConfig $config, $uploadedFile, string $type, string $directory, string $pathField): void
    {
        if (!$uploadedFile) return;
        $existing = $config->files()->where('type', $type)->first();
        $file = $existing
            ? $this->fileService->replace($existing, $uploadedFile, ['disk' => 'public', 'directory' => $directory, 'type' => $type])
            : $this->fileService->upload($uploadedFile, $config, ['disk' => 'public', 'directory' => $directory, 'type' => $type]);
        $this->pageConfigRepository->edit($config, [$pathField => $file->path]);
    }

}
