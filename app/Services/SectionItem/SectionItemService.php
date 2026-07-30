<?php

namespace App\Services\SectionItem;

use App\Repositories\SectionItem\SectionItemInterface;
use App\Services\File\FileService;
use Illuminate\Support\Facades\DB;

class SectionItemService
{
    public function __construct(
        protected SectionItemInterface $sectionItemRepository,
        protected FileService $fileService
    ) {}

    public function paginate($limit = 10, $search = '', ?int $pageSectionId = null)
    {
        $where = $pageSectionId ? ['page_section_id' => $pageSectionId] : [];
        if ($search) {
            $where['orWhere'] = [
                'title' => ['title', 'like', "%{$search}%"],
                'subtitle' => ['subtitle', 'like', "%{$search}%"],
                'content' => ['content', 'like', "%{$search}%"],
            ];
        }
        return $this->sectionItemRepository->paginate($where, ['sort_order' => 'asc', 'id' => 'asc'], ['*'], ['files'], $limit);
    }

    public function getAll($search = '', ?int $pageSectionId = null)
    {
        $where = $pageSectionId ? ['page_section_id' => $pageSectionId] : [];
        if ($search) {
            $where['orWhere'] = [
                'title' => ['title', 'like', "%{$search}%"],
                'subtitle' => ['subtitle', 'like', "%{$search}%"],
            ];
        }
        return $this->sectionItemRepository->get($where, ['sort_order' => 'asc', 'id' => 'asc'], ['*']);
    }

    public function find($id)
    {
        return $this->sectionItemRepository->find($id)?->load('files');
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            [$files, $urls] = [$data['files'] ?? [], $data['video_urls'] ?? []];
            unset($data['files'], $data['video_urls']);
            $item = $this->sectionItemRepository->create($data);
            $this->appendMedia($item, $files, $urls);
            return $item->load('files');
        });
    }

    public function update($item, array $data)
    {
        return DB::transaction(function () use ($item, $data) {
            [$files, $urls] = [$data['files'] ?? [], $data['video_urls'] ?? []];
            unset($data['files'], $data['video_urls']);
            $item = $this->sectionItemRepository->edit($item, $data);
            $this->appendMedia($item, $files, $urls);
            return $item->load('files');
        });
    }

    public function delete($item)
    {
        return DB::transaction(fn () => $this->sectionItemRepository->delete($item));
    }

    private function appendMedia($item, iterable $files, iterable $urls): void
    {
        $order = ((int) $item->files()->max('sort_order')) + 1;
        foreach ($files as $file) {
            $this->fileService->upload($file, $item, [
                'disk' => 'public', 'directory' => "section-items/{$item->id}", 'sort_order' => $order++,
            ]);
        }
        foreach ($urls as $url) {
            $this->fileService->createExternalUrl($item, $url, ['sort_order' => $order++]);
        }
    }
}
