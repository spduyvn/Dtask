<?php

namespace App\Repositories\Task;

use App\Models\User;
use App\Models\UserTask;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserTaskRepository implements UserTaskRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = $user->userTasks();

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $query->orderByRaw('COALESCE(due_at, due_date) asc')
            ->orderByDesc('id');

        return $query->paginate($perPage);
    }

    public function calendarForUser(User $user, int $month, int $year): Collection
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth()->endOfDay();

        return $user->userTasks()
            ->select(['id', 'user_id', 'project_id', 'title', 'status', 'start_date', 'due_date', 'start_at', 'due_at'])
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                // Task has a due date in this month
                $q->where(function ($sub) use ($startOfMonth, $endOfMonth) {
                    $sub->whereBetween('due_at', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('due_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
                })
                    // Or task has a start date in this month
                    ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                        $sub->whereBetween('start_at', [$startOfMonth, $endOfMonth])
                            ->orWhereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
                    });
            })
            ->orderByRaw('COALESCE(due_at, due_date, start_at, start_date) asc')
            ->get();
    }

    public function getSummaryForUser(User $user): array
    {
        $todayStr = Carbon::today()->toDateString();
        $nowStr   = Carbon::now()->toDateTimeString();

        $active = $user->userTasks()
            ->where('status', '!=', UserTask::STATUS_COMPLETED)
            ->count();

        $dueToday = $user->userTasks()
            ->where('status', '!=', UserTask::STATUS_COMPLETED)
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('due_at', $todayStr)
                    ->orWhere(function ($sub) use ($todayStr) {
                        $sub->whereNull('due_at')->where('due_date', $todayStr);
                    });
            })
            ->count();

        $overdue = $user->userTasks()
            ->where('status', '!=', UserTask::STATUS_COMPLETED)
            ->where(function ($q) use ($nowStr, $todayStr) {
                $q->where('due_at', '<', $nowStr)
                    ->orWhere(function ($sub) use ($todayStr) {
                        $sub->whereNull('due_at')->whereNotNull('due_date')->where('due_date', '<', $todayStr);
                    });
            })
            ->count();

        return [
            'activeTasks' => $active,
            'dueToday'    => $dueToday,
            'overdue'     => $overdue,
        ];
    }

    public function createForUser(User $user, array $data): UserTask
    {
        return $user->userTasks()->create($data);
    }

    public function update(UserTask $task, array $data): UserTask
    {
        $task->update($data);

        return $task->fresh();
    }

    public function delete(UserTask $task): void
    {
        $task->delete();
    }

    public function ensureOwnedBy(User $user, UserTask $task): void
    {
        if ($task->user_id != $user->id) {
            abort(404);
        }
    }
}
