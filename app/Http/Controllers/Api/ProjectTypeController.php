<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = ProjectType::orderBy('name')->get();

        return response()->json(['data' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:32',
        ]);

        $type = ProjectType::create($validated);

        return response()->json([
            'message' => 'Project type created.',
            'project_type' => $type,
        ], 201);
    }

    public function update(Request $request, ProjectType $projectType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'nullable|string|max:32',
        ]);

        $projectType->update($validated);

        return response()->json([
            'message' => 'Project type updated.',
            'project_type' => $projectType->fresh(),
        ]);
    }

    public function destroy(ProjectType $projectType): JsonResponse
    {
        if ($projectType->projects()->exists()) {
            return response()->json([
                'message' => 'Cannot delete: some projects use this type. Change or remove them first.',
            ], 422);
        }

        $projectType->delete();

        return response()->json(['message' => 'Project type deleted.']);
    }
}
