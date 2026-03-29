<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * GET /api/suppliers
     * For dropdown population
     */
    public function index(): JsonResponse
    {
        try {
            return response()->json(
                Supplier::select('id', 'Supplier_Name')
                    ->orderBy('Supplier_Name')
                    ->get()
            );
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error fetching suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/suppliers/list
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $query = Supplier::query()->withTrashed()->with('status');

            if ($search = $request->input('search')) {
                $query->where('Supplier_Name', 'like', "%{$search}%")
                      ->orWhere('Location', 'like', "%{$search}%")
                      ->orWhere('Contact', 'like', "%{$search}%");
            }

            $perPage = $request->integer('per_page', 10);

            return response()->json(
                $query->latest()->paginate($perPage)
            );

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error fetching supplier list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/suppliers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'Supplier_Name' => 'required|string|max:255|unique:suppliers,Supplier_Name',
            'Location'      => 'nullable|string|max:255',
            'Contact'       => 'nullable|string|max:255',
        ]);

        try {
            $supplier = Supplier::create($validated);
            return response()->json($supplier, 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * GET /api/suppliers/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            return response()->json(Supplier::findOrFail($id));
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Supplier not found'
            ], 404);
        }
    }

    /**
     * PUT /api/suppliers/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'Supplier_Name' => "sometimes|required|string|max:255|unique:suppliers,Supplier_Name,{$id}",
            'Location'      => 'nullable|string|max:255',
            'Contact'       => 'nullable|string|max:255',
        ]);

        try {
            $supplier->update($validated);
            return response()->json($supplier);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * DELETE /api/suppliers/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            return response()->json([
                'message' => 'Supplier deleted successfully.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cannot delete: ' . $e->getMessage()
            ], 422);
        }
    }
}