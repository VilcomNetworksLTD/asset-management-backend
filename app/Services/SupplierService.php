<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Exception;

class SupplierService
{
    
    public function getForDropdown(): Collection
    {
       
        return Supplier::select('id', 'Supplier_Name')
            ->orderBy('Supplier_Name', 'asc')
            ->get();
    }

    
    public function getPaginatedList(array $filters, int $perPage): LengthAwarePaginator
{
    $query = Supplier::withTrashed(); // include deleted suppliers

    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('Supplier_Name', 'like', "%{$search}%")
              ->orWhere('Location', 'like', "%{$search}%")
              ->orWhere('Contact', 'like', "%{$search}%");
        });
    }

    return $query->latest('id')->paginate($perPage);
}

        
    
    public function createSupplier(array $data): Supplier
    {
        return Supplier::create($data);
    }

    /**
     * Update an existing supplier
     */
    public function updateSupplier(int $id, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);
        return $supplier;
    }

    /**
     * Delete a supplier
     */
    public function deleteSupplier(int $id): bool
    {
        $supplier = Supplier::findOrFail($id);
        
        // Safety Check: Check if relationship exists and if assets are linked
        // This prevents deleting a supplier that still has equipment assigned to it
        if (method_exists($supplier, 'assets') && $supplier->assets()->exists()) {
            throw new Exception("This supplier is linked to existing assets and cannot be deleted.");
        }

        return $supplier->delete();
    }
}