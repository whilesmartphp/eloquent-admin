<?php

namespace Whilesmart\Admin\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminUserProvider
{
    public function paginate(string $query, int $perPage): LengthAwarePaginator;

    public function find(mixed $id): ?Authenticatable;
}
