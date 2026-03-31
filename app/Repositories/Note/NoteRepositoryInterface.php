<?php

namespace App\Repositories\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NoteRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 50, ?string $search = null): LengthAwarePaginator;

    public function createForUser(User $user, array $data): Note;

    public function update(Note $note, array $data): Note;

    public function delete(Note $note): void;

    public function ensureOwnedBy(User $user, Note $note): void;
}
