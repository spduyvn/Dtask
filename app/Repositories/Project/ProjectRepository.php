<?php

namespace App\Repositories\Project;

use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Project::query()
            ->with('projectType')
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => function ($q) {
                    $q->whereNotIn('status', [UserTask::STATUS_COMPLETED, UserTask::STATUS_PAUSED]);
                },
            ])
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($mq) use ($user) {
                        $mq->where('user_id', $user->id);
                    });
            });

        if (! empty($filters['project_type_id'])) {
            $query->where('project_type_id', $filters['project_type_id']);
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        $query->orderByDesc('created_at');

        return $query->paginate($perPage);
    }

    public function createForUser(User $user, array $data): Project
    {
        return Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'owner_id' => $user->id,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'project_type_id' => $data['project_type_id'] ?? ProjectType::first()?->id,
        ]);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function ensureAccessible(Request $request, Project $project): void
    {
        $user = $request->user();

        $isOwner = $project->owner_id == $user->id;
        $isMember = $project->members()->where('user_id', $user->id)->exists();

        if (! $isOwner && ! $isMember) {
            abort(404);
        }
    }

    public function ensureOwner(Request $request, Project $project): void
    {
        if ($project->owner_id != $request->user()->id) {
            abort(404);
        }
    }
}

