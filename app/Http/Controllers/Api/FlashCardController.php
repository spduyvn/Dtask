<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashCard;
use App\Repositories\FlashCard\FlashCardRepositoryInterface;
use App\Http\Requests\FlashCard\StoreFlashCardRequest;
use App\Http\Requests\FlashCard\UpdateFlashCardRequest;
use App\Http\Requests\FlashCard\ReviewFlashCardRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FlashCardController extends Controller
{
    public function __construct(
        private readonly FlashCardRepositoryInterface $flashCards,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 50);
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;
        $groupId = (int) $request->input('group_id');

        $paginated = $this->flashCards->paginateByGroup($request->user(), $groupId, $perPage, $search);

        return response()->json($paginated);
    }

    public function study(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 50);
        $groupId = $request->filled('group_id') ? (int) $request->input('group_id') : null;

        if (!$groupId) {
            return response()->json(['message' => 'Group ID is required'], 422);
        }

        $studyCards = $this->flashCards->getStudyCards($request->user(), $groupId, $limit);
        return response()->json(['data' => $studyCards]);
    }

    public function store(StoreFlashCardRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $groupId = (int) $validated['flash_card_group_id'];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $img = Image::make($file->getRealPath());
            // Resize to 600px width, maintain aspect ratio
            $img->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $filename = 'flash_cards/' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('local')->put($filename, (string) $img->encode());
            $validated['image_path'] = $filename;
        }

        $flashCard = $this->flashCards->createInGroup($request->user(), $groupId, $validated);

        return response()->json([
            'message' => 'FlashCard created successfully.',
            'flashCard' => $flashCard,
        ], 201);
    }

    public function show(Request $request, FlashCard $flashCard): JsonResponse
    {
        $this->flashCards->ensureOwnedBy($request->user(), $flashCard);
        return response()->json(['flashCard' => $flashCard]);
    }

    /**
     * Serve the image securely
     */
    public function image(Request $request, FlashCard $flashCard): BinaryFileResponse
    {
        $this->flashCards->ensureOwnedBy($request->user(), $flashCard);

        if (!$flashCard->image_path || !Storage::disk('local')->exists($flashCard->image_path)) {
            abort(404);
        }

        return response()->file(storage_path('app/' . $flashCard->image_path));
    }

    public function update(UpdateFlashCardRequest $request, FlashCard $flashCard): JsonResponse
    {
        $this->flashCards->ensureOwnedBy($request->user(), $flashCard);

        $validated = $request->validated();

        if (filter_var($request->input('remove_image'), FILTER_VALIDATE_BOOLEAN)) {
            if ($flashCard->image_path) {
                Storage::disk('local')->delete($flashCard->image_path);
            }
            $validated['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            if ($flashCard->image_path) {
                Storage::disk('local')->delete($flashCard->image_path);
            }

            $file = $request->file('image');
            $img = Image::make($file->getRealPath());
            $img->resize(600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $filename = 'flash_cards/' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('local')->put($filename, (string) $img->encode());
            $validated['image_path'] = $filename;
        }

        $flashCard = $this->flashCards->update($flashCard, $validated);

        return response()->json([
            'message' => 'FlashCard updated successfully.',
            'flashCard' => $flashCard,
        ]);
    }

    public function review(ReviewFlashCardRequest $request, FlashCard $flashCard): JsonResponse
    {
        $this->flashCards->ensureOwnedBy($request->user(), $flashCard);

        $validated = $request->validated();

        $flashCard = $this->flashCards->review($flashCard, $validated['rating']);

        return response()->json([
            'message' => 'FlashCard reviewed.',
            'flashCard' => $flashCard,
        ]);
    }

    public function destroy(Request $request, FlashCard $flashCard): JsonResponse
    {
        $this->flashCards->ensureOwnedBy($request->user(), $flashCard);
        $this->flashCards->delete($flashCard);

        return response()->json(['message' => 'FlashCard deleted.']);
    }
}
