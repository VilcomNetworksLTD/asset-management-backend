<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Component;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComponentController extends Controller
{
    
    public function index(): JsonResponse
    {
        
        return response()->json(Component::latest()->get());
    }

    public function list(Request $request): JsonResponse
    {
        $query = Component::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'serial_no' => 'nullable|string',
            'total_qty' => 'required|integer',
            'remaining_qty' => 'nullable|integer',
            'price' => 'nullable|numeric'
        ]);

        if (!isset($validated['remaining_qty'])) {
            $validated['remaining_qty'] = $validated['total_qty'];
        }

        $component = Component::create($validated);
        return response()->json($component, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $component = Component::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'serial_no' => 'nullable|string',
            'total_qty' => 'sometimes|required|integer',
            'remaining_qty' => 'nullable|integer',
            'price' => 'nullable|numeric'
        ]);

        $component->update($validated);

        return response()->json($component->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $component = Component::findOrFail($id);
        $component->delete();

        return response()->json(['message' => 'Component deleted successfully']);
    }
    public function assign(Request $request, $id)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'quantity' => 'required|integer|min:1',
    ]);

    $component = Component::findOrFail($id);

    $quantity = $request->integer('quantity');
    $userId = $request->integer('user_id');

    if ($component->remaining_qty < $quantity) {
        return response()->json(['message' => 'Not enough stock'], 400);
    }

    $user = User::find($userId);

    $user->components()->attach($component->id, ['quantity' => $quantity]);

    $component->decrement('remaining_qty', $quantity);
    
    ActivityLog::create([
        'Employee_ID' => Auth::id(),
        'user_name'   => Auth::user()->name ?? 'System',
        'action'      => 'Assigned',
        'target_type' => 'Component',
        'target_name' => $component->name,
        'details'     => "Assigned {$quantity} to user: {$user->name} (ID: {$userId})",
    ]);

    return response()->json(['message' => 'Component assigned successfully']);
}

    public function myComponents(Request $request)
    {
        // optionally filter by the parent asset so the frontend doesn't have to
        // load the user's entire component inventory when they're only
        // interested in parts attached to a particular device.
        $query = auth()->user()->components()->wherePivotNull('returned_at');
        if ($assetId = $request->query('asset_id')) {
            $query->where('asset_id', $assetId);
        }
        return response()->json($query->get());
    }

}