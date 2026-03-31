<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashCardGroup;
use App\Repositories\FlashCardGroup\FlashCardGroupRepositoryInterface;
use App\Http\Requests\FlashCardGroup\StoreFlashCardGroupRequest;
use App\Http\Requests\FlashCardGroup\UpdateFlashCardGroupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashCardGroupController extends Controller
{
    public function __construct(
        private readonly FlashCardGroupRepositoryInterface $groups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 50);
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;
        $paginated = $this->groups->paginateForUser($request->user(), $perPage, $search);

        return response()->json($paginated);
    }

    public function all(Request $request): JsonResponse
    {
        $allGroups = $this->groups->getAllForUser($request->user());
        return response()->json(['data' => $allGroups]);
    }

    public function store(StoreFlashCardGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $group = $this->groups->createForUser($request->user(), $validated);

        return response()->json([
            'message' => 'FlashCardGroup created successfully.',
            'group' => $group,
        ], 201);
    }

    public function show(Request $request, FlashCardGroup $flashCardGroup): JsonResponse
    {
        $this->groups->ensureOwnedBy($request->user(), $flashCardGroup);
        return response()->json(['group' => $flashCardGroup]);
    }

    public function update(UpdateFlashCardGroupRequest $request, FlashCardGroup $flashCardGroup): JsonResponse
    {
        $this->groups->ensureOwnedBy($request->user(), $flashCardGroup);
        $validated = $request->validated();
        $group = $this->groups->update($flashCardGroup, $validated);

        return response()->json([
            'message' => 'FlashCardGroup updated successfully.',
            'group' => $group,
        ]);
    }

    public function destroy(Request $request, FlashCardGroup $flashCardGroup): JsonResponse
    {
        $this->groups->ensureOwnedBy($request->user(), $flashCardGroup);
        $this->groups->delete($flashCardGroup);

        return response()->json(['message' => 'FlashCardGroup deleted.']);
    }
}
