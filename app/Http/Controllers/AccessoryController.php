<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Accessory;
use App\Models\User;
use App\Services\AccessoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessoryController extends Controller
{
    protected $accessoryService;

    public function __construct(AccessoryService $accessoryService)
    {
        $this->accessoryService = $accessoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $type = $request->route('type'); // Determined by route defaults (accessory or component)
        return response()->json($this->accessoryService->getAllAccessories($type));
    }

    public function list(Request $request): JsonResponse
    {
        $query = Accessory::query();

        // Filter by type if provided from route default
        if ($type = $request->route('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('model_number', 'like', "%{$search}%")
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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:255',
            'total_qty' => 'required|integer|min:0',
            'remaining_qty' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'asset_id' => 'nullable|integer|exists:assets,id',
        ]);

        // Set type based on route default (accessory or component)
        $data['type'] = $request->route('type', 'accessory');

        $accessory = Accessory::create($data);

        return response()->json($accessory, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $accessory = Accessory::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:255',
            'total_qty' => 'sometimes|required|integer|min:0',
            'remaining_qty' => 'sometimes|required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'asset_id' => 'nullable|integer|exists:assets,id',
        ]);

        $accessory->update($data);

        return response()->json($accessory->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $accessory = Accessory::findOrFail($id);
        $accessory->delete();

        return response()->json(['message' => 'Accessory deleted successfully']);
    }
    public function assign(Request $request, $id)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'quantity' => 'required|integer|min:1',
    ]);

    $accessory = Accessory::findOrFail($id);

    $quantity = $request->integer('quantity');
    $userId = $request->integer('user_id');

    if ($accessory->remaining_qty < $quantity) {
        return response()->json(['message' => 'Not enough stock'], 400);
    }

    $user = User::find($userId);

    $user->accessories()->attach($accessory->id, ['quantity' => $quantity]);

    $accessory->decrement('remaining_qty', $quantity);

    ActivityLog::create([
        'Employee_ID' => Auth::id(),
        'user_name'   => Auth::user()->name ?? 'System',
        'action'      => 'Assigned',
        'target_type' => 'Accessory',
        'target_name' => $accessory->name,
        'details'     => "Assigned {$quantity} to user: {$user->name}",
    ]);
    
    return response()->json(['message' => 'Accessory assigned successfully']);
}

    public function myAccessories()
    {
        $accessories = auth()->user()->accessories()->wherePivotNull('returned_at')->get();
        return response()->json($accessories);
    }

    public function myComponents()
    {
        $components = auth()->user()->components()->wherePivotNull('returned_at')->get();
        return response()->json($components);
    }
}