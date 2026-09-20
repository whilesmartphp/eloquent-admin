<?php

namespace Whilesmart\Admin\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Whilesmart\Admin\Contracts\AdminUserProvider;

class EloquentAdminUserProvider implements AdminUserProvider
{
    public function paginate(string $query, int $perPage): LengthAwarePaginator
    {
        $model = $this->model();
        $builder = $model::query();
        if ($query !== '') {
            $term = '%'.strtolower($query).'%';
            $columns = config('admin.user_search_columns', ['email']);
            $builder->where(function ($nested) use ($columns, $term) {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $nested->{$method}("lower({$column}) like ?", [$term]);
                }
            });
        }

        return $builder->latest()->paginate($perPage);
    }

    public function find(mixed $id): ?Authenticatable
    {
        $model = $this->model();

        return $model::query()->find($id);
    }

    private function model(): string
    {
        $model = config('admin.user_model');
        if (! is_string($model) || ! is_subclass_of($model, Authenticatable::class)) {
            throw new InvalidArgumentException('admin.user_model must implement Authenticatable.');
        }

        return $model;
    }
}
