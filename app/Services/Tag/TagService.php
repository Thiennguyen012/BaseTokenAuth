<?php

namespace App\Services\Tag;

use App\Repositories\Tag\TagInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TagService
{
    public function __construct(protected TagInterface $tagRepository) {}

    public function paginate($limit = 10, $search = '')
    {
        $where = [];
        $orderBy = ['id' => 'desc'];

        if ($search) {
            $where['orWhere'] = [
                'name' => ['name', 'like', '%' . $search . '%'],
                'slug' => ['slug', 'like', '%' . $search . '%'],
            ];
        }

        return $this->tagRepository->paginate($where, $orderBy, ['*'], [], $limit);
    }

    public function getAll($search = '')
    {
        $where = [];
        $orderBy = ['name' => 'asc'];

        if ($search) {
            $where['orWhere'] = [
                'name' => ['name', 'like', '%' . $search . '%'],
                'slug' => ['slug', 'like', '%' . $search . '%'],
            ];
        }

        return $this->tagRepository->get($where, $orderBy, ['*']);
    }

    public function find($id)
    {
        return is_numeric($id)
            ? $this->tagRepository->find($id)
            : $this->findBySlug($id);
    }

    public function findBySlug(string $slug)
    {
        return $this->tagRepository->first(['slug' => $slug], []);
    }

    public function create(array $data)
    {
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        return $this->tagRepository->create($data);
    }

    public function update($tag, array $data)
    {
        if (array_key_exists('name', $data) || array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniqueSlug(
                $data['slug'] ?? ($data['name'] ?? $tag->name),
                (int) $tag->id
            );
        }
        return $this->tagRepository->edit($tag, $data);
    }

    public function delete($tag)
    {
        return $this->tagRepository->delete($tag);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'tag';
        $slug = $base;
        $suffix = 2;

        while (DB::table('tags')
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
