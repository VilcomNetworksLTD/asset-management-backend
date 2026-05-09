<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenseController extends Controller
{
    /**
     * Fetch all licenses for the list view.
     */
    public function index(): JsonResponse
    {
        return response()->json(License::with('department')->latest()->get());
    }

    public function list(Request $request): JsonResponse
    {
        $query = License::query()
            ->with('department'); // Eager load the relation

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_key', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($manufacturer = $request->string('manufacturer')->toString()) {
            $query->where('manufacturer', $manufacturer);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
    'name' => 'required|string|max:255',
    'product_key' => 'nullable|string|max:255',
    'manufacturer' => 'nullable|string|max:255',
    'total_seats' => 'required|integer|min:1',
    'remaining_seats' => 'required|integer|min:0',
    'price' => 'nullable|numeric|min:0',
    'expiry_date' => 'nullable|date',
    'department_id' => 'nullable|exists:departments,id',
    'allocation_type' => 'nullable|string|max:255',
    'renewal_type' => 'nullable|string|max:255',
]);

        $license = License::create($data);

        return response()->json($license, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $license = License::findOrFail($id);

        $data = $request->validate([
    'name' => 'required|string|max:255',
    'product_key' => 'nullable|string|max:255',
    'manufacturer' => 'nullable|string|max:255',
    'total_seats' => 'required|integer|min:1',
    'remaining_seats' => 'required|integer|min:0',
    'price' => 'nullable|numeric|min:0',
    'expiry_date' => 'nullable|date',
    'department_id' => 'nullable|exists:departments,id',
    'allocation_type' => 'nullable|string|max:255',
    'renewal_type' => 'nullable|string|max:255',
]);
        $license->update($data);

        return response()->json($license->fresh('department'));
    }

    public function destroy(int $id): JsonResponse
    {
        $license = License::findOrFail($id);
        $license->delete();

        return response()->json(['message' => 'License deleted successfully']);
    }
    public function assign(Request $request, $id)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $license = License::findOrFail($id);


    if ($license->remaining_seats < 1) {
        return response()->json(['message' => 'No seats available'], 400);
    }

    $user = User::find($request->user_id);

    $user->licenses()->attach($license->id);

    $license->decrement('remaining_seats');
    
    ActivityLog::create([
        'Employee_ID' => Auth::id(),
        'user_name'   => Auth::user()->name ?? 'System',
        'action'      => 'Assigned',
        'target_type' => 'License',
        'target_name' => $license->name,
        'details'     => "Assigned a seat to user: {$user->name}",
    ]);

    return response()->json(['message' => 'License seat assigned successfully']);
}

    public function myLicenses()
    {
        $licenses = auth()->user()->licenses()->wherePivotNull('returned_at')->get();
        return response()->json($licenses);
    }
}