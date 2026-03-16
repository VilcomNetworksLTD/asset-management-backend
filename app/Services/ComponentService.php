<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Http\Request;

class ComponentService
{
    /**
     * Get all components (latest first)
     */
    public function all()
    {
        return Component::latest()->get();
    }

    /**
     * Paginated list with filters
     */
    public function list(Request $request)
    {
        $query = Component::query();

        // Only active (non-deleted)
        $query->whereNull('deleted_at');

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

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new component
     */
    public function create(array $data)
    {
        if (!isset($data['remaining_qty'])) {
            $data['remaining_qty'] = $data['total_qty'];
        }

        return Component::create($data);
    }

    /**
     * Update an existing component
     */
    public function update(int $id, array $data)
    {
        $component = Component::findOrFail($id);
        $component->update($data);

        return $component->fresh();
    }

    /**
     * Soft delete a component
     */
    public function delete(int $id)
    {
        $component = Component::findOrFail($id);
        $component->delete(); // Soft delete now

        return $component;
    }
}
