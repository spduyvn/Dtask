<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /**
     * List users (with pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $users = $this->users->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $this->users->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user->only(['id', 'name', 'email', 'created_at']),
        ], 201);
    }

    /**
     * User detail.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
        ]);
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = $validated['password'];
        }

        $user = $this->users->update($user, $validated);

        return response()->json([
            'message' => 'Updated successfully.',
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
        ]);
    }

    /**
     * Delete user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->users->delete($user);

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }
}
