<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Existing method for Admin
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return response()->json($users);
    }

    public function list(Request $request): JsonResponse
    {
        // Eager load department so we can show the name in the table
        $query = \App\Models\User::query()
            ->withTrashed()
            ->with(['department:id,name', 'status']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->toString()) {
            $query->where('role', $role);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'is_verified' => 'nullable|boolean',
        ]);

        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'department_id' => $data['department_id'] ?? null,
            'password' => bcrypt($data['password']),
            'role' => $data['role'] ?? 'staff',
            'is_verified' => $data['is_verified'] ?? true,
        ]);

        return response()->json($user, 201);
    }

    public function updateById(Request $request, int $id): JsonResponse
    {
        $user = \App\Models\User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|string|max:50',
            'is_verified' => 'sometimes|required|boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh('department'));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = \App\Models\User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Fetch a specific user's details including their assigned items for the Admin Panel
     */
    public function getUserDetails(int $id): JsonResponse
    {
        $user = \App\Models\User::with([
            'assets',        
            'licenses',      
            'components',    
            'consumables',   
            'accessories'    
        ])->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Fetch Caleb's current profile data
     */
    public function show(): JsonResponse
    {
        return response()->json(Auth::user());
    }

    /**
     * Handle Caleb updating his name/email
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
        ]);

        $user = $this->userService->updateProfile(Auth::user(), $request->only('name', 'email'));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Handle Password Change
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $result = $this->userService->updatePassword(Auth::user(), $request->all());

        if (!$result) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Consolidated endpoint for components, accessories, licenses, and consumables assigned to the current user.
     */
    public function getMyAssignedItems(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user->load(['components', 'accessories', 'licenses']);

        return response()->json([
            'components' => $user->components,
            'accessories' => $user->accessories,
            'licenses' => $user->licenses,
        ]);
    }
}