<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    // GET /api/assets
    public function index()
    {
        return response()->json(Asset::all());
    }

    // POST /api/assets
    public function store(Request $request)
    {
        // Normalize incoming payload to model field names so API accepts
        // both legacy names (asset_name, serial_no, etc.) and simpler
        // client names (name, serial_number, category_id).
        // Prefer the framework-parsed input, but fall back to raw JSON body
        $input = $request->all();
        if (empty($input)) {
            $raw = $request->getContent();
            if (empty($raw)) {
                $raw = file_get_contents('php://input');
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        $data = [];
        $data['asset_name'] = $input['asset_name'] ?? $input['name'] ?? null;
        // allow either asset_category (string) or category_id (int)
        $data['asset_category'] = $input['asset_category'] ?? ($input['category'] ?? ($input['category_id'] ?? null));
        $data['serial_no'] = $input['serial_no'] ?? $input['serial_number'] ?? null;
        $data['supplier_id'] = $input['supplier_id'] ?? null;
        $data['status_id'] = $input['status_id'] ?? null;
        $data['price'] = $input['price'] ?? null;
        $data['employee_id'] = $input['employee_id'] ?? null;
        $data['warranty_details'] = $input['warranty_details'] ?? null;
        $data['license_info'] = $input['license_info'] ?? null;

        // Debug removed; proceed with validation

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'asset_name' => 'required|string',
            'asset_category' => 'required',
            'serial_no' => 'required|string|unique:assets,serial_no',
            'supplier_id' => 'required|integer',
            'status_id' => 'required|integer',
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $asset = Asset::create($validator->validated());

        return response()->json($asset, 201);
    }

    // GET /api/assets/{id}
    public function show($id)
    {
        return response()->json(Asset::findOrFail($id));
    }

    // PUT /api/assets/{id}
    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $asset->update($request->all());

        return response()->json($asset);
    }

    // DELETE /api/assets/{id}
    public function destroy($id)
    {
        Asset::destroy($id);

        return response()->json(['message' => 'Asset deleted']);
    }
}
