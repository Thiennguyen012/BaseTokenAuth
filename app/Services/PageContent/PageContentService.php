<?php

namespace App\Services\PageContent;

use App\Repositories\PageContent\PageContentInterface;

class PageContentService
{
    public function __construct(protected PageContentInterface $pageContentRepository) {}

    public function paginate($limit = 10, $search = '')
    {
        $where = [];
        $orderBy = ['created_at' => 'desc'];

        if ($search) {
            $where['orWhere'] = [
                'title' => ['title', 'like', '%' . $search . '%'],
                'slug' => ['slug', 'like', '%' . $search . '%'],
            ];
        }

        return $this->pageContentRepository->paginate(
            $where,
            $orderBy,
            ['*'],
            ['sections.files', 'sections.items.files'],
            $limit
        );
    }

    public function getAll($search = '')
    {
        $where = [];
        $orderBy = ['title' => 'asc'];

        if ($search) {
            $where['orWhere'] = [
                'title' => ['title', 'like', '%' . $search . '%'],
                'slug' => ['slug', 'like', '%' . $search . '%'],
            ];
        }

        return $this->pageContentRepository->get($where, $orderBy, ['*']);
    }

    public function findBySlug(string $slug)
    {
        $trimmed = ltrim($slug, '/');
        $pageContent = $this->pageContentRepository->first(['slug' => $slug], [])
            ?? $this->pageContentRepository->first(['slug' => $trimmed], [])
            ?? $this->pageContentRepository->first(['slug' => '/' . $trimmed], []);

        return $pageContent?->load(['sections.files', 'sections.items.files']);
    }

    public function find($id)
    {
        if (!is_numeric($id)) {
            return $this->findBySlug((string) $id);
        }

        $pageContent = $this->pageContentRepository->find($id);

        return $pageContent?->load(['sections.files', 'sections.items.files']);
    }

    public function create(array $data)
    {
        $pageContent = $this->pageContentRepository->create($data);

        return $pageContent->load(['sections.files', 'sections.items.files']);
    }

    public function update($pageContent, array $data)
    {
        $updated = $this->pageContentRepository->edit($pageContent, $data);

        return $updated->load(['sections.files', 'sections.items.files']);
    }

    public function delete($pageContent)
    {
        return $this->pageContentRepository->delete($pageContent);
    }
}
