<?php

namespace App\Repositories\FlashCard;

use App\Models\FlashCard;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class FlashCardRepository implements FlashCardRepositoryInterface
{
    public function paginateByGroup(User $user, int $groupId, int $perPage = 50, ?string $search = null): LengthAwarePaginator
    {
        $query = FlashCard::where('user_id', $user->id)
            ->where('flash_card_group_id', $groupId);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', '%' . $search . '%')
                    ->orWhere('answer', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    public function getStudyCards(User $user, int $groupId, int $limit = 50): Collection
    {
        return FlashCard::where('user_id', $user->id)
            ->where('flash_card_group_id', $groupId)
            ->where(function ($q) {
                $q->whereNull('next_review_at')
                    ->orWhere('next_review_at', '<=', Carbon::now());
            })
            ->orderBy('next_review_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();
    }

    public function createInGroup(User $user, int $groupId, array $data): FlashCard
    {
        $data['user_id'] = $user->id;
        $data['flash_card_group_id'] = $groupId;
        return FlashCard::create($data);
    }

    public function update(FlashCard $flashCard, array $data): FlashCard
    {
        $flashCard->update($data);
        return $flashCard->fresh();
    }

    public function delete(FlashCard $flashCard): void
    {
        if ($flashCard->image_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($flashCard->image_path);
        }
        $flashCard->delete();
    }

    public function ensureOwnedBy(User $user, FlashCard $flashCard): void
    {
        if ($flashCard->user_id != $user->id) {
            abort(404);
        }
    }

    public function review(FlashCard $flashCard, string $rating): FlashCard
    {
        $level = $flashCard->level;
        $rating = strtolower($rating);

        if ($rating === 'again') {
            $level = 0;
            $nextReviewAt = Carbon::now()->addMinutes(1);
        } elseif ($rating === 'hard') {
            $level = max(0, $level - 1);
            $nextReviewAt = Carbon::now()->addMinutes(15);
        } elseif ($rating === 'good') {
            $level += 1;
            $nextReviewAt = Carbon::now()->addDays($level);
        } else {
            $level += 2;
            $nextReviewAt = Carbon::now()->addDays($level * 2);
        }

        $flashCard->update([
            'level' => $level,
            'last_reviewed_at' => Carbon::now(),
            'next_review_at' => $nextReviewAt,
        ]);

        return $flashCard->fresh();
    }
}
