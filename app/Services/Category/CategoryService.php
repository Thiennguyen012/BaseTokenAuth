<?php

namespace App\Services\Category;

use App\Repositories\Category\CategoryInterface;

class CategoryService
{
    protected $categoryRepository;

    public function __construct(CategoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function paginate($limit = 10, $search = '')
    {
        $where = [];
        $orderBy = ['created_at' => 'desc'];

        if ($search) {
            $where['orWhere'] = [
                'category_name' => ['category_name', 'like', '%' . $search . '%'],
                'description' => ['description', 'like', '%' . $search . '%'],
            ];
        }

        return $this->categoryRepository->paginate($where, $orderBy, ['*'], [], $limit);
    }

    public function getAll($search = '')
    {
        $where = [];
        $orderBy = ['category_name' => 'asc'];

        if ($search) {
            $where['orWhere'] = [
                'category_name' => ['category_name', 'like', '%' . $search . '%'],
                'description' => ['description', 'like', '%' . $search . '%'],
            ];
        }

        return $this->categoryRepository->get($where, $orderBy, ['*']);
    }

    public function find($id)
    {
        return is_numeric($id)
            ? $this->categoryRepository->find($id)
            : $this->findBySlug($id);
    }

    public function findBySlug(string $slug)
    {
        $trimmed = ltrim($slug, '/');
        return $this->categoryRepository->first(['slug' => $slug], [])
            ?? $this->categoryRepository->first(['slug' => $trimmed], [])
            ?? $this->categoryRepository->first(['slug' => '/' . $trimmed], []);
    }

    public function create(array $data)
    {
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? ($data['category_name'] ?? 'danh-muc'));
        $data = $this->handleThumbnail($data);
        return $this->categoryRepository->create($data);
    }

    public function update($category, array $data)
    {
        if (array_key_exists('slug', $data) || array_key_exists('category_name', $data)) {
            $data['slug'] = $this->uniqueSlug(
                !empty($data['slug']) ? $data['slug'] : ($data['category_name'] ?? $category->category_name),
                (int) $category->id
            );
        }
        $data = $this->handleThumbnail($data);
        return $this->categoryRepository->edit($category, $data);
    }

    public function delete($category)
    {
        return $this->categoryRepository->delete($category);
    }

    private function handleThumbnail(array $data): array
    {
        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof \Illuminate\Http\UploadedFile) {
            $data['thumbnail_path'] = $data['thumbnail']->store('categories', 'public');
            unset($data['thumbnail']);
        } elseif (isset($data['thumbnail_path']) && $data['thumbnail_path'] instanceof \Illuminate\Http\UploadedFile) {
            $data['thumbnail_path'] = $data['thumbnail_path']->store('categories', 'public');
        } elseif (isset($data['thumbnail_path']) && is_array($data['thumbnail_path']) && isset($data['thumbnail_path'][0]) && $data['thumbnail_path'][0] instanceof \Illuminate\Http\UploadedFile) {
            $data['thumbnail_path'] = $data['thumbnail_path'][0]->store('categories', 'public');
        }

        return $data;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::substr(\Illuminate\Support\Str::slug($value) ?: 'danh-muc', 0, 240);
        $slug = $base;
        $counter = 2;

        while (\Illuminate\Support\Facades\DB::table('categories')
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
