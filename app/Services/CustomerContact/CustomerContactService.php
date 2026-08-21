<?php

namespace App\Services\CustomerContact;

use App\Repositories\CustomerContact\CustomerContactInterface;

class CustomerContactService
{
    public function __construct(protected CustomerContactInterface $repository) {}

    public function paginate(int $limit = 10, string $search = '', ?int $categoryId = null)
    {
        $where = [];
        if ($search !== '') {
            $where['orWhere'] = [
                'full_name' => ['full_name', 'like', $search],
                'phone' => ['phone', 'like', $search],
                'email' => ['email', 'like', $search],
                'consultation_content' => ['consultation_content', 'like', $search],
            ];
        }
        if ($categoryId) {
            $where['category_id'] = $categoryId;
        }

        return $this->repository->paginate($where, ['created_at' => 'desc'], ['*'], ['category'], $limit);
    }

    public function find(int $id)
    {
        return $this->repository->find($id)?->load('category');
    }

    public function create(array $data)
    {
        return $this->repository->create($data)->load('category');
    }

    public function update($contact, array $data)
    {
        return $this->repository->edit($contact, $data)->load('category');
    }

    public function delete($contact): bool
    {
        return (bool) $this->repository->delete($contact);
    }
}
