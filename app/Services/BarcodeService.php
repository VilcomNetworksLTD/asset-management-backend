<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    /**
     * Generates a unique barcode string (e.g., VNL/Category/0024)
     * * @param int $assetId
     * @param string $category
     * @return string
     */
    public function generateUniqueBarcodeContent($assetId, $category): string
    {
        // Sanitize category name: remove spaces and special characters that could break URL structures
        $category = preg_replace('/[^A-Za-z0-9]/', '', $category);

        // Format: VNL/CategoryName/0001 (Padded to 4 digits as per your logic)
        return 'VNL/' . $category . '/' . str_pad($assetId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Renders an SVG image of the barcode from the string content.
     * Use this for embedding directly into Blade templates for high-quality printing.
     * * @param string $content
     * @return string
     */
    public function generateBarcodeImage($content): string
    {
        // Ensure content isn't empty to avoid generator exceptions
        if (empty($content)) {
            return '';
        }

        $generator = new BarcodeGeneratorSVG();
        
        // TYPE_CODE_128 is a standard high-density barcode format 
        // 2: width of the bars
        // 60: height in pixels
        // The SVG output is best for printing as it won't pixelate when scaled
        return $generator->getBarcode($content, $generator::TYPE_CODE_128, 2, 60);
    }
}