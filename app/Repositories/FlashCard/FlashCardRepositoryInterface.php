<?php

namespace App\Repositories\FlashCard;

use App\Models\FlashCard;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface FlashCardRepositoryInterface
{
    public function paginateByGroup(User $user, int $groupId, int $perPage = 50, ?string $search = null): LengthAwarePaginator;
    public function getStudyCards(User $user, int $groupId, int $limit = 50): Collection;
    public function createInGroup(User $user, int $groupId, array $data): FlashCard;
    public function update(FlashCard $flashCard, array $data): FlashCard;
    public function delete(FlashCard $flashCard): void;
    public function ensureOwnedBy(User $user, FlashCard $flashCard): void;
    public function review(FlashCard $flashCard, string $rating): FlashCard;
}
