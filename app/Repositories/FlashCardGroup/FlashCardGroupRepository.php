<?php

namespace App\Repositories\FlashCardGroup;

use App\Models\FlashCardGroup;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FlashCardGroupRepository implements FlashCardGroupRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 50, ?string $search = null): LengthAwarePaginator
    {
        $query = FlashCardGroup::where('user_id', $user->id);

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->withCount('flashCards')->orderByDesc('updated_at')->paginate($perPage);
    }

    public function getAllForUser(User $user): Collection
    {
        return FlashCardGroup::where('user_id', $user->id)
            ->withCount('flashCards')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function createForUser(User $user, array $data): FlashCardGroup
    {
        $data['user_id'] = $user->id;
        return FlashCardGroup::create($data);
    }

    public function update(FlashCardGroup $flashCardGroup, array $data): FlashCardGroup
    {
        $flashCardGroup->update($data);
        return $flashCardGroup->fresh();
    }

    public function delete(FlashCardGroup $flashCardGroup): void
    {
        $flashCardGroup->delete();
    }

    public function ensureOwnedBy(User $user, FlashCardGroup $flashCardGroup): void
    {
        if ($flashCardGroup->user_id != $user->id) {
            abort(404);
        }
    }
}
