<?php

namespace App\Repositories\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface ProjectRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 50): LengthAwarePaginator;

    public function createForUser(User $user, array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;

    public function ensureAccessible(Request $request, Project $project): void;

    public function ensureOwner(Request $request, Project $project): void;
}

