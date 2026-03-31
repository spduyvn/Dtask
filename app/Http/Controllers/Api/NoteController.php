<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Repositories\Note\NoteRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteRepositoryInterface $notes,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 50);
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;
        $paginated = $this->notes->paginateForUser($request->user(), $perPage, $search);

        return response()->json($paginated);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:32',
            'content' => 'nullable|string',
        ]);

        $note = $this->notes->createForUser($request->user(), $validated);

        return response()->json([
            'message' => 'Note created successfully.',
            'note' => $note,
        ], 201);
    }

    public function show(Request $request, Note $note): JsonResponse
    {
        $this->notes->ensureOwnedBy($request->user(), $note);

        return response()->json(['note' => $note]);
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $this->notes->ensureOwnedBy($request->user(), $note);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'nullable|string|max:32',
            'content' => 'nullable|string',
        ]);

        $note = $this->notes->update($note, $validated);

        return response()->json([
            'message' => 'Note updated successfully.',
            'note' => $note,
        ]);
    }

    public function destroy(Request $request, Note $note): JsonResponse
    {
        $this->notes->ensureOwnedBy($request->user(), $note);
        $this->notes->delete($note);

        return response()->json(['message' => 'Note deleted.']);
    }
}
