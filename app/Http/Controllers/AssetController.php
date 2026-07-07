<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetConsumable;
use App\Models\Category;
use App\Models\Consumable;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetService;
use App\Services\AssetImportService;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetController extends Controller
{
    protected $assetService;

    protected $barcodeService;
    protected $assetImportService;

    public function __construct(AssetService $assetService, BarcodeService $barcodeService, AssetImportService $assetImportService)
    {
        $this->assetService = $assetService;
        $this->barcodeService = $barcodeService;
        $this->assetImportService = $assetImportService;
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Asset store request', $request->all());

        if ($request->input('location_id') === '') {
            $request->merge(['location_id' => null]);
        }

        try {
            $data = $request->validate([
                'Asset_Name' => 'required|string|max:255',
                'system_name' => 'nullable|string|max:255',
                'Serial_No' => 'nullable|string|max:255|unique:assets,Serial_No',
                'category_id' => 'required|exists:categories,id',
                'location_id' => 'nullable|exists:locations,id',
                'Supplier_ID' => 'nullable|integer|exists:suppliers,id',
                'Status_ID' => 'nullable|integer|exists:statuses,id',
                'Employee_ID' => 'nullable|integer|exists:users,id',
                'Price' => 'nullable|numeric|min:0',
                'Purchase_Date' => 'nullable|date',
                'warranty_expiry' => 'nullable|date',
                'warranty_image' => 'nullable|image|max:10240',
                'custom_attributes' => 'nullable|array',
            ]);
        } catch (ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation failed', $e->errors());

            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        }

        if (isset($data['category_id']) && isset($data['custom_attributes'])) {
            $this->validateCategoryFields($request, $data['category_id']);
        }

        if ($request->hasFile('warranty_image')) {
            $data['warranty_image_path'] = $request->file('warranty_image')->store('warranty_images', 'public');
        }

        $data['created_by'] = Auth::id();

        $asset = $this->assetService->store($data);

        return response()->json($asset->load(['status', 'supplier', 'user', 'category', 'locationModel']), 201);
    }

    /**
     * Get assets for HOD's department staff.
     */
    public function hodDepartmentAssets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $role = strtolower($user->role ?? '');

        if (!$user || !in_array($role, ['hod', 'manager'])) {
            return response()->json(['error' => 'Unauthorized. Only HODs and Managers can access this page.'], 403);
        }

        if (! $user->department_id) {
            return response()->json(['error' => 'You are not assigned to a department. Please contact your administrator.'], 403);
        }

        $department = Department::find($user->department_id);
        $departmentName = $department ? $department->name : 'Department';

        $assets = Asset::with(['status:id,Status_Name', 'category:id,name', 'locationModel:id,name', 'user:id,name,email'])
            ->whereIn('Employee_ID', function ($query) use ($user) {
                $query->select('id')
                    ->from('users')
                    ->where('department_id', $user->department_id)
                    ->where('id', '!=', $user->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'assets' => $assets,
            'department_name' => $departmentName,
        ]);
    }

    /**
     * Get assets for Manager's department staff.
     */
    public function managerDepartmentAssets(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user || strtolower($user->role) !== 'manager') {
            return response()->json(['error' => 'Unauthorized. Only Managers can access this page.'], 403);
        }

        if (! $user->department_id) {
            return response()->json(['error' => 'You are not assigned to a department. Please contact your administrator.'], 403);
        }

        $department = Department::find($user->department_id);
        $departmentName = $department ? $department->name : 'Department';

        $assets = Asset::with(['status:id,Status_Name', 'category:id,name', 'locationModel:id,name', 'user:id,name,email'])
            ->whereIn('Employee_ID', function ($query) use ($user) {
                $query->select('id')
                    ->from('users')
                    ->where('department_id', $user->department_id)
                    ->where('id', '!=', $user->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'assets' => $assets,
            'department_name' => $departmentName,
        ]);
    }

    /**
     * Get staff and their assets for HOD's department.
     */
    public function hodStaffAssets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $role = strtolower($user->role ?? '');

        if (!$user || !in_array($role, ['hod', 'manager'])) {
            return response()->json(['error' => 'Unauthorized. Only HODs and Managers can access this page.'], 403);
        }

        if (! $user->department_id) {
            return response()->json(['error' => 'You are not assigned to a department. Please contact your administrator.'], 403);
        }

        $department = Department::find($user->department_id);
        $departmentName = $department ? $department->name : 'Department';

        $staff = User::where('department_id', $user->department_id)
            ->where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->get();

        if ($staff->isEmpty()) {
            app(\App\Services\SafetikaService::class)->syncUsers();
            $staff = User::where('department_id', $user->department_id)
                ->where('id', '!=', $user->id)
                ->select('id', 'name', 'email')
                ->get();
        }

        $staff = $staff->map(function ($employee) {
                $assets = Asset::where('Employee_ID', $employee->id)
                    ->with(['status:id,Status_Name', 'category:id,name'])
                    ->get();
                $employee->assets = $assets;

                return $employee;
            });

        return response()->json([
            'department_name' => $departmentName,
            'staff' => $staff,
        ]);
    }

    /**
     * Get staff and their assets for Manager's department.
     */
    public function managerStaffAssets(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user || strtolower($user->role) !== 'manager') {
            return response()->json(['error' => 'Unauthorized. Only Managers can access this page.'], 403);
        }

        if (! $user->department_id) {
            return response()->json(['error' => 'You are not assigned to a department. Please contact your administrator.'], 403);
        }

        \Illuminate\Support\Facades\Log::info('Manager staff assets request', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'department_id' => $user->department_id,
        ]);

        $department = Department::find($user->department_id);
        $departmentName = $department ? $department->name : 'Department';

        $staff = User::where('department_id', $user->department_id)
            ->where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->get();

        if ($staff->isEmpty()) {
            app(\App\Services\SafetikaService::class)->syncUsers();
            $staff = User::where('department_id', $user->department_id)
                ->where('id', '!=', $user->id)
                ->select('id', 'name', 'email')
                ->get();
        }

        $staff = $staff->map(function ($employee) {
                $assets = Asset::where('Employee_ID', $employee->id)
                    ->with(['status:id,Status_Name', 'category:id,name'])
                    ->get();
                $employee->assets = $assets;

                return $employee;
            });

        \Illuminate\Support\Facades\Log::info('Staff found', [
            'count' => $staff->count(),
            'department_id' => $user->department_id,
        ]);

        return response()->json([
            'department_name' => $departmentName,
            'staff' => $staff,
        ]);
    }

    /**
     * Get assets created by the current HOD.
     */
    public function hodCreatedAssets(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user || strtolower($user->role) !== 'hod') {
            return response()->json(['error' => 'Unauthorized. Only HODs can access this page.'], 403);
        }

        $query = Asset::with(['status', 'category', 'locationModel', 'user'])
            ->where('created_by', $user->id);

        $perPage = $request->integer('per_page', 15);
        $assets = $query->latest()->paginate($perPage);

        return response()->json($assets);
    }

    /**
     * Basic index for dashboard/summary views.
     */
    public function index(): JsonResponse
    {
        $assets = Asset::with(['status', 'supplier', 'user', 'category', 'locationModel'])
            ->where(function ($q) {
                // Show assets not assigned to anyone
                $q->whereNull('Employee_ID')
                    ->orWhere('Employee_ID', 0)
                  // OR show assets that are NOT currently 'Deployed' or 'Assigned'
                    ->orWhereHas('status', function ($sq) {
                        $sq->whereNotIn('Status_Name', ['Deployed', 'Assigned', 'In Use']);
                    });
            })
            ->latest()
            ->get();

        return response()->json($assets);
    }

    /**
     * Paginated list (Powers the main table and Search).
     */
    public function list(Request $request): JsonResponse
    {
        $query = Asset::with(['status', 'supplier', 'user', 'category', 'locationModel']);

        // Filter by 'Available' if requested (for tickets/transfers)
        if ($request->boolean('available')) {
            $query->where(function ($q) {
                $q->whereNull('Employee_ID')
                    ->orWhere('Employee_ID', 0)
                    ->orWhereHas('status', function ($sq) {
                        $sq->whereNotIn('Status_Name', ['Deployed', 'Assigned', 'In Use']);
                    });
            });
        }

        if ($search = $request->string('search')->toString()) {
            $assetTagId = null;
            if (preg_match('/^AST-?0*(\d+)$/i', $search, $matches)) {
                $assetTagId = (int) $matches[1];
            }

            $query->where(function ($q) use ($search, $assetTagId) {
                $q->where('Asset_Name', 'like', "%{$search}%")
                    ->orWhere('system_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('Serial_No', 'like', "%{$search}%")
                    ->orWhere('Asset_Category', 'like', "%{$search}%") // Link legacy string categories
                    ->orWhereHas('category', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    });

                if ($assetTagId) {
                    $q->orWhere('id', $assetTagId);
                }
            });
        }

        // --- ORIGINAL HOD RESTRICTION INTACT ---
        $user = Auth::user();
        if ($user && $user->role === 'hod') {
            $query->whereHas('category', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        if ($category = $request->input('category')) {
            if (is_numeric($category)) {
                $query->where('category_id', $category);
            } else {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', 'like', "%{$category}%");
                });
            }
        }

        if ($locationId = $request->input('location')) {
            $query->where('location_id', $locationId);
        }

        if ($statusId = $request->input('status')) {
            $query->where('Status_ID', $statusId);
        }

        $perPage = $request->integer('per_page', 15);
        $result = $query->latest()->paginate($perPage);

        // Include schemas for dynamic frontend columns
        if ($categoryId = $request->input('category')) {
            $category = Category::find($categoryId);
            $data = $result->toArray();
            $data['category_schema'] = $category;

            return response()->json($data);
        }

        if ($locationId = $request->input('location')) {
            $location = Location::find($locationId);
            $data = $result->toArray();
            $data['location_schema'] = $location;

            return response()->json($data);
        }

        return response()->json($result);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:20480',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        try {
            $category = Category::findOrFail($request->integer('category_id'));
            $summary = $this->assetImportService->import($request->file('file'), $category);

            return response()->json([
                'message' => "Imported {$summary['imported']} assets.",
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($request->input('location_id') === '') {
            $request->merge(['location_id' => null]);
        }

        $data = $request->validate([
            'Asset_Name' => 'sometimes|required|string|max:255',
            'system_name' => 'nullable|string|max:255',
            'Serial_No' => 'nullable|string|max:255|unique:assets,Serial_No,'.$id,
            'category_id' => 'sometimes|required|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'Supplier_ID' => 'sometimes|required|integer|exists:suppliers,id',
            'Price' => 'nullable|numeric|min:0',
            'Purchase_Date' => 'nullable|date',
            'warranty_image' => 'nullable|image|max:10240',
            'custom_attributes' => 'nullable|array',
            'accessories' => 'nullable|array',
            'accessories.*.pivot_id' => 'nullable|integer',
            'accessories.*.accessory_id' => 'required|integer|exists:accessories,id',
            'accessories.*.new_accessory_id' => 'required|integer|exists:accessories,id',
            'accessories.*.quantity' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('warranty_image')) {
            $data['warranty_image_path'] = $request->file('warranty_image')->store('warranty_images', 'public');
        }

        if (array_key_exists('system_name', $data) && filled($data['system_name'])) {
            $data['Asset_Name'] = $data['system_name'];
        }

        $currentAsset = Asset::findOrFail($id);
        $categoryId = $data['category_id'] ?? $currentAsset->category_id;

        if ($categoryId && isset($data['custom_attributes'])) {
            $this->validateCategoryFields($request, $categoryId);
        }

        // Handle accessory change logic — use pivot_id for precision (fixes duplicate accessory bug)
        if (isset($data['accessories']) && $currentAsset->Employee_ID) {
            $user = User::find($currentAsset->Employee_ID);
            if ($user) {
                foreach ($data['accessories'] as $accData) {
                    $pivotId      = $accData['pivot_id'] ?? null;
                    $oldAccId     = $accData['accessory_id'];
                    $newAccId     = $accData['new_accessory_id'];
                    $quantity     = intval($accData['quantity']);

                    // Look up the SPECIFIC pivot row by its id
                    $pivotQuery = DB::table('accessory_user')
                        ->where('user_id', $user->id)
                        ->where('accessory_id', $oldAccId)
                        ->where('asset_id', $currentAsset->id)
                        ->whereNull('returned_at');

                    // If we have a precise pivot id, target only that row
                    if ($pivotId) {
                        $pivotQuery->where('id', $pivotId);
                    }

                    $oldPivot = $pivotQuery->first();

                    if (!$oldPivot) {
                        continue;
                    }

                    if ($oldAccId != $newAccId) {
                        // ── SWAP: different accessory chosen ──────────────────────────
                        $oldAccessory = \App\Models\Accessory::find($oldAccId);
                        if ($oldAccessory) {
                            $oldAccessory->increment('remaining_qty', $oldPivot->quantity);
                        }

                        $newAccessory = \App\Models\Accessory::findOrFail($newAccId);
                        if ($newAccessory->remaining_qty < $quantity) {
                            return response()->json(['message' => "Not enough stock for {$newAccessory->name}"], 400);
                        }
                        $newAccessory->decrement('remaining_qty', $quantity);

                        // Delete ONLY the specific pivot row
                        DB::table('accessory_user')->where('id', $oldPivot->id)->delete();

                        // Attach the new accessory
                        $user->accessories()->attach($newAccId, [
                            'quantity' => $quantity,
                            'asset_id' => $currentAsset->id
                        ]);

                        \App\Models\ActivityLog::create([
                            'Employee_ID' => Auth::id(),
                            'user_name'   => Auth::user()->name ?? 'System',
                            'action'      => 'Changed Accessory',
                            'target_type' => 'Accessory',
                            'target_name' => $newAccessory->name,
                            'details'     => "Swapped accessory for user {$user->name} on Asset ID {$currentAsset->id} from {$oldAccessory->name} to {$newAccessory->name} with quantity {$quantity}.",
                            'asset_id'    => $currentAsset->id,
                        ]);
                    } else {
                        // ── SAME ACCESSORY: quantity-only change ──────────────────────
                        $diff = $quantity - $oldPivot->quantity;
                        if ($diff !== 0) {
                            $accessory = \App\Models\Accessory::findOrFail($oldAccId);
                            if ($diff > 0) {
                                if ($accessory->remaining_qty < $diff) {
                                    return response()->json(['message' => "Not enough stock for {$accessory->name}"], 400);
                                }
                                $accessory->decrement('remaining_qty', $diff);
                            } else {
                                $accessory->increment('remaining_qty', abs($diff));
                            }

                            // Update ONLY the specific pivot row by its id
                            DB::table('accessory_user')
                                ->where('id', $oldPivot->id)
                                ->update(['quantity' => $quantity]);

                            \App\Models\ActivityLog::create([
                                'Employee_ID' => Auth::id(),
                                'user_name'   => Auth::user()->name ?? 'System',
                                'action'      => 'Changed Accessory Qty',
                                'target_type' => 'Accessory',
                                'target_name' => $accessory->name,
                                'details'     => "Changed qty for user {$user->name} on Asset ID {$currentAsset->id} for {$accessory->name} from {$oldPivot->quantity} to {$quantity}.",
                                'asset_id'    => $currentAsset->id,
                            ]);
                        }
                    }
                }
            }
        }

        $asset = $this->assetService->updateAsset($id, $data);

        return response()->json($asset->load(['status', 'supplier', 'user', 'category', 'locationModel']));
    }

    public function show($id): JsonResponse
    {
        $asset = Asset::with([
            'user' => fn ($q) => $q->with(['accessories' => fn ($aq) => $aq->wherePivotNull('returned_at')->wherePivot('asset_id', $id)]),
            'supplier',
            'status',
            'category',
            'activeToners',
            'locationModel',
            'activityLogs' => fn ($q) => $q->with('user')->latest(),
        ])->findOrFail($id);

        return response()->json($asset);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->assetService->deleteAsset($id);

        return response()->json(['message' => 'Asset deleted successfully']);
    }

    public function assign(Request $request, $id): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $this->assetService->assignAsset($id, $request->user_id);

        return response()->json(['message' => 'Asset assigned successfully']);
    }

    public function showBarcodeImage($id)
    {
        try {
            $decodedId = urldecode((string) $id);

            // Check by Primary ID first, then by the barcode content
            $asset = Asset::find($decodedId);

            if (! $asset) {
                $asset = Asset::where('barcode', $decodedId)->first();
            }
            if (! $asset) {
                \Log::warning('Barcode image requested for non-existent asset', ['id' => $id]);
                abort(404, 'Asset not found');
            }
            if (! $asset->barcode) {
                \Log::warning('Barcode image requested but asset has no barcode', ['asset_id' => $asset->id]);

                return response()->json(['error' => 'No barcode generated'], 404);
            }

            $svg = $this->barcodeService->generateBarcodeImage($asset->barcode);

            // Validate SVG was generated properly
            if (empty($svg) || strpos($svg, '<svg') === false) {
                \Log::error('Barcode SVG generation failed', ['asset_id' => $asset->id, 'barcode' => $asset->barcode]);

                return response()->json(['error' => 'Failed to generate barcode image'], 500);
            }

            return response($svg)->header('Content-Type', 'image/svg+xml');
        } catch (\Exception $e) {
            \Log::error('Barcode image generation exception', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to generate barcode image'], 500);
        }
    }

    public function findAssetByBarcode($barcode_content): JsonResponse
    {
        $barcode = urldecode($barcode_content);

        $asset = Asset::with(['status', 'supplier', 'user', 'category', 'locationModel'])
            ->where('barcode', $barcode)
            ->firstOrFail();

        return response()->json($asset);
    }

    public function replaceToner(Request $request, $printerId): JsonResponse
    {
        $data = $request->validate([
            'consumable_id' => 'required|exists:consumables,id',
            'color' => 'required|string',
        ]);

        return DB::transaction(function () use ($printerId, $data) {
            $printer = Asset::findOrFail($printerId);
            $consumable = Consumable::findOrFail($data['consumable_id']);
            $colorStock = $consumable->colorStocks()->where('color', $data['color'])->first();

            if (! $colorStock || $colorStock->in_stock < 1) {
                return response()->json(['message' => "Not enough {$data['color']} ink in stock."], 422);
            }

            AssetConsumable::where('asset_id', $printerId)
                ->where('color', $data['color'])
                ->whereNull('depleted_at')
                ->update(['depleted_at' => now()]);

            AssetConsumable::create([
                'asset_id' => $printer->id,
                'consumable_id' => $consumable->id,
                'color' => $data['color'],
                'installed_at' => now(),
                'installed_by' => Auth::id(),
            ]);

            $colorStock->decrement('in_stock', 1);

            return response()->json(['message' => "{$data['color']} toner replaced successfully!"]);
        });
    }

    public function uploadEvidence(Request $request, $id)
    {
        $request->validate(['image' => 'required|image|max:5120']);
        $asset = Asset::findOrFail($id);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('evidence', 'public');
            $asset->update(['evidence_image' => $path]);

            return response()->json(['path' => $path]);
        }

        return response()->json(['error' => 'failed'], 400);
    }

    private function validateCategoryFields(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        if (! $category || empty($category->fields)) {
            return;
        }

        $rules = [];
        $attributeNames = [];
        foreach ($category->fields as $field) {
            $key = $field['name'] ?? null;
            if (!$key) continue;
            $label = $field['label'] ?? $key;
            $type = $field['type'] ?? 'text';
            $required = filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $fieldRules = $required ? ['required'] : ['nullable'];

            switch ($type) {
                case 'number':
                case 'checkbox':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'ip_address':
                    $fieldRules[] = 'ip';
                    break;
                case 'mac_address':
                    $fieldRules[] = 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';
                    break;
                case 'image':
                case 'file':
                    // Files are sent as base64 data URLs, treat as string
                    $fieldRules[] = 'string';
                    break;
                case 'select':
                    if (! empty($field['options'])) {
                        $options = is_array($field['options']) ? $field['options'] : explode(',', $field['options']);
                        $fieldRules[] = 'in:'.implode(',', array_map('trim', $options));
                    }
                    break;
                default:
                    $fieldRules[] = 'string';
            }

            $rules["custom_attributes.$key"] = $fieldRules;
            $attributeNames["custom_attributes.$key"] = $label;
        }
        $request->validate($rules, [], $attributeNames);
    }

    private function validateLocationFields(Request $request, $locationId)
    {
        $location = Location::find($locationId);
        if (! $location || empty($location->fields)) {
            return;
        }

        $rules = [];
        $attributeNames = [];
        foreach ($location->fields as $field) {
            $key = $field['name'] ?? null;
            if (!$key) continue;
            $label = $field['label'] ?? $key;
            $required = filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $rules["custom_attributes.$key"] = $required ? ['required'] : ['nullable'];
            $attributeNames["custom_attributes.$key"] = $label;
        }
        $request->validate($rules, [], $attributeNames);
    }
}
