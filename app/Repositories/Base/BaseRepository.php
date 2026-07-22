<?php

namespace App\Repositories\Base;

use App\CPU\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;

abstract class BaseRepository implements BaseInterface
{
    protected Model $model;

    protected array $search = [];

    public function __construct()
    {
        $this->makeModel();
    }

    abstract public function model(): string;

    public function resetModel(): void
    {
        $this->makeModel();
    }

    public function makeModel(): Model
    {
        return $this->model = app($this->model());
    }

    protected function query(array $where = [], array $orderBy = [], array $select = [], array $with = []): Builder
    {
        $query = $this->model->newQuery();

        if (!empty($select)) {
            $query->select($select);
        }

        if (!empty($where)) {
            $this->applyConditions($query, $where);
        }

        if (!empty($orderBy)) {
            $this->orderBy($query, $orderBy);
        }

        if (!empty($with)) {
            $query->with($with);
        }

        return $query;
    }

    public function find($id)
    {
        return $this->model->newQuery()->find($id);
    }

    public function first(array $where, array $orderBy = [], array $select = [], array $with = [])
    {
        return $this->query($where, $orderBy, $select, $with)->first();
    }

    public function firstOrCreate(array $where)
    {
        return $this->model->newQuery()->firstOrCreate($where);
    }

    public function get(array $where = [], array $orderBy = [], array $select = [], array $with = [])
    {
        return $this->query($where, $orderBy, $select, $with)->get();
    }

    public function count(array $where)
    {
        return $this->query($where)->count();
    }

    public function sum(array $where, $field)
    {
        return $this->query($where)->sum($field);
    }

    public function push(array $where, $field, $value, $unique = true)
    {
        return $this->query($where)->push($field, $value, $unique);
    }

    public function pull(array $where, $field, $value)
    {
        return $this->query($where)->pull($field, $value);
    }

    public function all()
    {
        return $this->model->newQuery()->get();
    }

    public function pluck($column, $key = null)
    {
        return $this->model->newQuery()->pluck($column, $key);
    }

    public function pluckWhere(array $where, $column, $key = null)
    {
        return $this->query($where)->pluck($column, $key);
    }

    public function increment(array $where, $column, $value = 1)
    {
        return $this->query($where)->increment($column, $value);
    }

    public function decrement(array $where, $column, $value = 1)
    {
        return $this->query($where)->decrement($column, $value);
    }

    public function create($data)
    {
        $model = $this->model->newInstance($data);
        $model->save();

        return $model;
    }

    public function createMany($data)
    {
        $this->model->newQuery()->insert($data);
    }

    public function edit($model, $data)
    {
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function editWhere(array $where, array $data, $limit = 0, $offset = 0)
    {
        $query = $this->query($where);

        if ($limit > 0) {
            $query->offset($offset)->limit($limit);
        }

        $query->update($data);

        return $query;
    }

    public function delete($model)
    {
        return $model->delete();
    }

    public function restore($model)
    {
        return $model->restore();
    }

    public function forceDelete($model)
    {
        return $model->forceDelete();
    }

    public function withTrashed()
    {
        $this->model = $this->model->withTrashed();

        return $this;
    }

    public function onlyTrashed()
    {
        $this->model = $this->model->onlyTrashed();

        return $this;
    }

    public function upsert($data, $unique_column, $update_column)
    {
        $this->model->newQuery()->upsert($data, $unique_column, $update_column);

        return true;
    }

    public function updateOrCreate($data, $data_update)
    {
        return $this->model->newQuery()->updateOrCreate($data, $data_update);
    }

    public function deleteWhere(array $where = [])
    {
        $query = $this->query($where);
        $query->delete();

        return true;
    }

    public function paginate(array $where = [], array $orderBy = [], array $select = [], array $with = [], $limit = Helpers::LIMIT_PER_PAGE)
    {
        return $this->query($where, $orderBy, $select, $with)->paginate($limit);
    }

    protected function applyConditions(Builder $query, array $where = []): void
    {
        foreach ($where as $field => $value) {
            switch ($field) {
                case 'orWhere':
                    $query->where(function (Builder $query) use ($value): void {
                        foreach ($value as $f => $v) {
                            $this->where($query, $f, $v, 'orWhere');
                        }
                    });
                    break;
                case 'whereRaw':
                    $query->whereRaw($value);
                    break;
                case 'whereHas':
                    foreach ($value as $valueItem) {
                        [$relationship, $val] = $valueItem;
                        $query->whereHas($relationship, function (Builder $query) use ($val): void {
                            foreach ($val as $f => $v) {
                                $this->where($query, $f, $v);
                            }
                        });
                    }
                    break;
                case 'withCount':
                    foreach ($value as $relationship => $val) {
                        $query->withCount([$relationship => function (Builder $query) use ($val): void {
                            foreach ($val as $f => $v) {
                                $this->where($query, $f, $v);
                            }
                        }])->having(Str::snake($relationship) . '_count', '>', 0);
                    }
                    break;
                case 'with_count':
                    foreach ($value as $relationship => $val) {
                        $query->withCount([$relationship => function (Builder $query) use ($val): void {
                            foreach ($val as $f => $v) {
                                $this->where($query, $f, $v);
                            }
                        }]);
                    }
                    break;
                default:
                    $this->where($query, $field, $value);
            }
        }
    }

    public function where(Builder $query, $field, $value, $method = 'where'): void
    {
        if (is_array($value)) {
            [$field, $condition, $val] = $value;

            switch ($condition) {
                case 'whereIn':
                    $query->whereIn($field, $val);
                    break;
                case 'whereNotIn':
                    $query->whereNotIn($field, $val);
                    break;
                case 'whereBetween':
                    $query->whereBetween($field, $val);
                    break;
                case 'whereNotNull':
                    $query->whereNotNull($field);
                    break;
                case 'whereDate':
                    $query->whereDate($field, $val);
                    break;
                case 'like':
                    $query->$method($field, $condition, '%' . $val . '%');
                    break;
                case '<=':
                    $query->$method($field, '<=', $val);
                    break;
                case '>=':
                    $query->$method($field, '>=', $val);
                    break;
                default:
                    $query->$method($field, $condition, $val);
            }
        } else {
            $query->$method($field, $value);
        }
    }

    public function orderBy(Builder $query, array $orderBy): void
    {
        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }
    }

    public function aggregate(array $pipeline)
    {
        return $this->model->newQuery()->raw(function ($query) use ($pipeline) {
            return $query->aggregate($pipeline, ['allowDiskUse' => true]);
        });
    }

    public function match(array $conditions)
    {
        $match = [];

        if ($conditions) {
            foreach ($conditions as $condition) {
                $operator = $condition['condition'] ?? '';

                switch ($operator) {
                    case '=':
                    case 'is':
                        $match[] = [$condition['key'] => $condition['value']];
                        break;
                    case 'in':
                        if ($condition['key'] == '_id') {
                            $condition['value'] = array_map(static fn ($id) => new ObjectId($id), $condition['value']);
                        }
                        $match[] = [$condition['key'] => ['$in' => $condition['value']]];
                        break;
                    case 'not_in':
                    case 'nin':
                        $match[] = [$condition['key'] => ['$nin' => $condition['value']]];
                        break;
                    case '!=':
                    case 'not':
                        $match[] = [$condition['key'] => ['$ne' => $condition['value']]];
                        break;
                    case 'greater':
                    case '>':
                        $match[] = [$condition['key'] => ['$gt' => $this->formatDate($condition)]];
                        break;
                    case '>=':
                        $match[] = [$condition['key'] => ['$gte' => $this->formatDate($condition)]];
                        break;
                    case 'less':
                    case '<':
                        $match[] = [$condition['key'] => ['$lt' => $this->formatDate($condition)]];
                        break;
                    case '<=':
                        $match[] = [$condition['key'] => ['$lte' => $this->formatDate($condition)]];
                        break;
                    case 'like':
                        $match[] = [$condition['key'] => ['$regex' => $condition['value']]];
                        break;
                    case 'not_have':
                        $match[] = [$condition['key'] => ['$not' => ['$regex' => $condition['value']]]];
                        break;
                    default:
                        $match[] = [$condition['key'] => $condition['value']];
                }
            }
        }

        return $match;
    }

    protected function formatDate(array $condition)
    {
        $value = $condition['value'];
        $type = $condition['type'] ?? '';

        switch ($type) {
            case 'datetime':
                $value = new \MongoDB\BSON\UTCDateTime(new \DateTime($value));
                break;
        }

        return $value;
    }

    public function project(array $select)
    {
        $project = [];

        foreach ($select as $value) {
            $project[$value] = 1;
        }

        return $project;
    }

    public function sort(array $data)
    {
        $sort = [];

        foreach ($data as $value) {
            $sort[$value['key']] = ($value['value'] == 'ASC') ? 1 : -1;
        }

        return $sort;
    }

    public function setValue(array $data, $prefix = '')
    {
        $set = [];

        foreach ($data as $value) {
            $set[$value] = '$' . $prefix . $value;
        }

        return $set;
    }
}
