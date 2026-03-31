<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarTaskRequest;
use App\Models\UserTask;
use App\Repositories\Task\UserTaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTaskController extends Controller
{
    public function __construct(
        private readonly UserTaskRepositoryInterface $tasks,
    ) {}

    public function calendar(CalendarTaskRequest $request): JsonResponse
    {
        $month = $request->integer('month');
        $year  = $request->integer('year');

        $tasks = $this->tasks->calendarForUser($request->user(), $month, $year);

        return response()->json([
            'tasks' => $tasks,
            'month' => $month,
            'year'  => $year,
            'today' => now()->toDateString(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->tasks->getSummaryForUser($request->user()));
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 50);
        $filters = [
            'project_id' => $request->filled('project_id') ? $request->integer('project_id') : null,
            'status' => $request->filled('status') && in_array($request->integer('status'), [0, 1, 2, 3], true)
                ? $request->integer('status') : null,
        ];
        $tasks = $this->tasks->paginateForUser($request->user(), $filters, $perPage);

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|integer|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|integer|in:0,1,2,3',
            'start_at' => 'nullable|date',
            'due_at' => 'nullable|date|after_or_equal:start_at',
        ]);
        $validated['status'] = $validated['status'] ?? 0;

        $task = $this->tasks->createForUser($request->user(), $validated);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => $task,
        ], 201);
    }

    public function show(Request $request, UserTask $userTask): JsonResponse
    {
        $this->tasks->ensureOwnedBy($request->user(), $userTask);

        $userTask->load('project');

        return response()->json(['task' => $userTask]);
    }

    public function update(Request $request, UserTask $userTask): JsonResponse
    {
        $this->tasks->ensureOwnedBy($request->user(), $userTask);

        $validated = $request->validate([
            'project_id' => 'sometimes|nullable|integer|exists:projects,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|integer|in:0,1,2,3',
            'start_at' => 'nullable|date',
            'due_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        $userTask = $this->tasks->update($userTask, $validated);

        return response()->json([
            'message' => 'Updated successfully.',
            'task' => $userTask,
        ]);
    }

    public function destroy(Request $request, UserTask $userTask): JsonResponse
    {
        $this->tasks->ensureOwnedBy($request->user(), $userTask);
        $this->tasks->delete($userTask);

        return response()->json(['message' => 'Task deleted.']);
    }
}
