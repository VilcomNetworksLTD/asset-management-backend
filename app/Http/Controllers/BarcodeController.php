<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Http\Response;

class BarcodeController extends Controller
{
    /**
     * Generate barcode image.
     * Handles both numeric IDs and string barcodes (e.g., VNL/CAT/001)
     */
    public function image($id)
    {
        // 1. Decode the ID in case slashes were URL-encoded (%2F)
        $decodedId = urldecode($id);

        // 2. Try to find asset by primary ID first
        $asset = Asset::find($decodedId);

        // 3. If not found by ID, search by the barcode string
        if (!$asset) {
            $asset = Asset::where('barcode', $decodedId)->first();
        }

        // 4. Final check if asset exists and has barcode data
        if (!$asset) {
            abort(404, 'Asset not found');
        }
        
        if (empty($asset->barcode)) {
            abort(404, 'Barcode string is empty for this asset');
        }

        try {
            $generator = new BarcodeGeneratorPNG();
            
            // Generate Code 128 barcode
            // Width Factor (3), Height (100 pixels)
            $image = $generator->getBarcode(
                $asset->barcode, 
                $generator::TYPE_CODE_128, 
                3, 
                100
            );

            // Return with explicit headers to force browser recognition
            return response($image)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                
        } catch (\Exception $e) {
            abort(500, 'Error generating barcode: ' . $e->getMessage());
        }
    }
}