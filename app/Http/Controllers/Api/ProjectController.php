<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UserTask;
use App\Repositories\Project\ProjectRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = (int) $request->get('per_page', 50);
        $filters = [
            'project_type_id' => $request->filled('project_type_id') ? $request->integer('project_type_id') : null,
            'name' => $request->filled('name') ? trim((string) $request->input('name')) : null,
        ];
        $projects = $this->projects->paginateForUser($user, $filters, $perPage);

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:32',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_type_id' => 'nullable|integer|exists:project_types,id',
        ]);

        $project = $this->projects->createForUser($user, $validated);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->projects->ensureAccessible($request, $project);

        $project->load('projectType');
        $project->loadCount([
            'tasks',
            'tasks as open_tasks_count' => function ($q) {
                $q->whereNotIn('status', [UserTask::STATUS_COMPLETED, UserTask::STATUS_PAUSED]);
            },
        ]);

        return response()->json(['project' => $project]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->projects->ensureOwner($request, $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:32',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'project_type_id' => 'sometimes|nullable|integer|exists:project_types,id',
        ]);

        $project = $this->projects->update($project, $validated);

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $project,
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->projects->ensureOwner($request, $project);
        $this->projects->delete($project);

        return response()->json(['message' => 'Project deleted.']);
    }

}

