<?php

namespace App\Services\TagGroup;

use App\Repositories\TagGroup\TagGroupInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TagGroupService
{
    public function __construct(protected TagGroupInterface $tagGroupRepository) {}

    public function paginate($limit = 10, $search = '')
    {
        $where = [];
        $orderBy = ['sort_order' => 'asc', 'id' => 'desc'];

        if ($search) {
            $where['orWhere'] = [
                'name' => ['name', 'like', '%' . $search . '%'],
                'code' => ['code', 'like', '%' . $search . '%'],
            ];
        }

        return $this->tagGroupRepository->paginate($where, $orderBy, ['*'], [], $limit);
    }

    public function getAll($search = '')
    {
        $where = [];
        $orderBy = ['sort_order' => 'asc', 'name' => 'asc'];

        if ($search) {
            $where['orWhere'] = [
                'name' => ['name', 'like', '%' . $search . '%'],
                'code' => ['code', 'like', '%' . $search . '%'],
            ];
        }

        return $this->tagGroupRepository->get($where, $orderBy, ['*'], ['tags']);
    }

    public function find($id)
    {
        return is_numeric($id)
            ? $this->tagGroupRepository->find($id, ['*'], ['tags'])
            : $this->findByCode($id);
    }

    public function findByCode(string $code)
    {
        return $this->tagGroupRepository->first(['code' => $code], ['*'], ['tags']);
    }

    public function create(array $data)
    {
        $data['code'] = $this->uniqueCode($data['code'] ?? $data['name']);
        return $this->tagGroupRepository->create($data);
    }

    public function update($tagGroup, array $data)
    {
        if (array_key_exists('name', $data) || array_key_exists('code', $data)) {
            $data['code'] = $this->uniqueCode(
                $data['code'] ?? ($data['name'] ?? $tagGroup->name),
                (int) $tagGroup->id
            );
        }
        return $this->tagGroupRepository->edit($tagGroup, $data);
    }

    public function delete($tagGroup)
    {
        return $this->tagGroupRepository->delete($tagGroup);
    }

    private function uniqueCode(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'group';
        $code = $base;
        $suffix = 2;

        while (DB::table('tag_groups')
            ->where('code', $code)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $code = $base . '-' . $suffix++;
        }

        return $code;
    }
}
