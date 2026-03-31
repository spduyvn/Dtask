<?php

namespace App\Repositories\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NoteRepository implements NoteRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 50, ?string $search = null): LengthAwarePaginator
    {
        $query = $user->notes();

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    public function createForUser(User $user, array $data): Note
    {
        return $user->notes()->create($data);
    }

    public function update(Note $note, array $data): Note
    {
        $note->update($data);

        return $note->fresh();
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }

    public function ensureOwnedBy(User $user, Note $note): void
    {
        if ($note->user_id != $user->id) {
            abort(404);
        }
    }
}   
