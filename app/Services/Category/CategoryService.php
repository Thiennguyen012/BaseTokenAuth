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
        return $this->categoryRepository->find($id);
    }

    public function create(array $data)
    {
        return $this->categoryRepository->create($data);
    }

    public function update($category, array $data)
    {
        return $this->categoryRepository->edit($category, $data);
    }

    public function delete($category)
    {
        return $this->categoryRepository->delete($category);
    }
}
