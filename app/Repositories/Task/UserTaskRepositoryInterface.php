<?php

namespace App\Repositories\Task;

use App\Models\User;
use App\Models\UserTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserTaskRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 50): LengthAwarePaginator;

    public function calendarForUser(User $user, int $month, int $year): Collection;

    public function getSummaryForUser(User $user): array;

    public function createForUser(User $user, array $data): UserTask;

    public function update(UserTask $task, array $data): UserTask;

    public function delete(UserTask $task): void;

    public function ensureOwnedBy(User $user, UserTask $task): void;
}
