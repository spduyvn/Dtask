<?php

namespace App\Repositories\FlashCardGroup;

use App\Models\FlashCardGroup;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface FlashCardGroupRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 50, ?string $search = null): LengthAwarePaginator;
    public function getAllForUser(User $user): Collection;
    public function createForUser(User $user, array $data): FlashCardGroup;
    public function update(FlashCardGroup $flashCardGroup, array $data): FlashCardGroup;
    public function delete(FlashCardGroup $flashCardGroup): void;
    public function ensureOwnedBy(User $user, FlashCardGroup $flashCardGroup): void;
}
