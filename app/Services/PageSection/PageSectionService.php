<?php

namespace App\Services\PageSection;

use App\Repositories\PageSection\PageSectionInterface;
use App\Services\File\FileService;
use Illuminate\Support\Facades\DB;

class PageSectionService
{
    public function __construct(
        protected PageSectionInterface $pageSectionRepository,
        protected FileService $fileService
    ) {}

    public function paginate($limit = 10, $search = '', ?int $pageContentId = null)
    {
        $where = $pageContentId ? ['page_content_id' => $pageContentId] : [];
        if ($search) {
            $where['orWhere'] = [
                'title' => ['title', 'like', "%{$search}%"],
                'subtitle' => ['subtitle', 'like', "%{$search}%"],
                'content' => ['content', 'like', "%{$search}%"],
            ];
        }
        return $this->pageSectionRepository->paginate($where, ['sort_order' => 'asc', 'id' => 'asc'], ['*'], ['files', 'items.files'], $limit);
    }

    public function getAll($search = '', ?int $pageContentId = null)
    {
        $where = $pageContentId ? ['page_content_id' => $pageContentId] : [];
        if ($search) {
            $where['orWhere'] = [
                'title' => ['title', 'like', "%{$search}%"],
                'subtitle' => ['subtitle', 'like', "%{$search}%"],
            ];
        }
        return $this->pageSectionRepository->get($where, ['sort_order' => 'asc', 'id' => 'asc'], ['*']);
    }

    public function find($id)
    {
        return $this->pageSectionRepository->find($id)?->load(['files', 'items.files']);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            [$files, $urls] = [$data['files'] ?? [], $data['video_urls'] ?? []];
            unset($data['files'], $data['video_urls']);
            $section = $this->pageSectionRepository->create($data);
            $this->appendMedia($section, $files, $urls);
            return $section->load(['files', 'items.files']);
        });
    }

    public function update($section, array $data)
    {
        return DB::transaction(function () use ($section, $data) {
            [$files, $urls] = [$data['files'] ?? [], $data['video_urls'] ?? []];
            unset($data['files'], $data['video_urls']);
            $section = $this->pageSectionRepository->edit($section, $data);
            $this->appendMedia($section, $files, $urls);
            return $section->load(['files', 'items.files']);
        });
    }

    public function delete($section)
    {
        return DB::transaction(fn () => $this->pageSectionRepository->delete($section));
    }

    private function appendMedia($section, iterable $files, iterable $urls): void
    {
        $order = ((int) $section->files()->max('sort_order')) + 1;
        foreach ($files as $file) {
            $this->fileService->upload($file, $section, [
                'disk' => 'public', 'directory' => "page-sections/{$section->id}", 'sort_order' => $order++,
            ]);
        }
        foreach ($urls as $url) {
            $this->fileService->createExternalUrl($section, $url, ['sort_order' => $order++]);
        }
    }
}
