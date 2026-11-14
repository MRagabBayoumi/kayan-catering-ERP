<?php

namespace App\Http\Controllers;

use App\Models\BarcodeSetting;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Services\BarcodeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPrintController extends Controller
{
    /**
     * Print thermal barcode label for a single product.
     */
    public function printProductLabel(Request $request, Product $product, BarcodeGeneratorService $barcodeService): View
    {
        $barcodeSettings = BarcodeSetting::getSettings();
        $quantity = max(1, min(500, (int) $request->input('quantity', 1)));

        $widthMm = (int) $barcodeSettings->label_width_mm;
        $heightMm = (int) $barcodeSettings->label_height_mm;

        $size = $request->input('size');
        if ($size && preg_match('/^(\d+)x(\d+)$/', $size, $matches)) {
            $widthMm = (int) $matches[1];
            $heightMm = (int) $matches[2];
        }

        $barcodeValue = $barcodeService->getBarcodeValue($product);
        $svg = $barcodeService->generateSvg($barcodeValue);
        $labelSvg = $barcodeService->generateCompleteLabelSvg(
            $product->name,
            $barcodeValue,
            $product->sku,
            $widthMm,
            $heightMm,
            $barcodeSettings,
            (float) $product->cost_price
        );

        return view('products.print-label', [
            'title' => 'طباعة ملصق باركود: '.$product->name,
            'items' => [
                [
                    'name' => $product->name,
                    'barcode_value' => $barcodeValue,
                    'svg' => $svg,
                    'label_svg' => $labelSvg,
                    'quantity' => $quantity,
                ],
            ],
            'totalQuantity' => $quantity,
            'autoPrint' => $request->boolean('autoprint', false),
            'widthMm' => $widthMm,
            'heightMm' => $heightMm,
            'size' => $widthMm.'x'.$heightMm,
            'barcodeSettings' => $barcodeSettings,
        ]);
    }

    /**
     * Print thermal barcode label for a specific catering warehouse batch.
     */
    public function printBatchLabel(Request $request, InventoryBatch $batch, BarcodeGeneratorService $barcodeService): View
    {
        $barcodeSettings = BarcodeSetting::getSettings();
        $quantity = max(1, min(500, (int) $request->input('quantity', 1)));

        $widthMm = (int) $barcodeSettings->label_width_mm;
        $heightMm = (int) $barcodeSettings->label_height_mm;

        $size = $request->input('size');
        if ($size && preg_match('/^(\d+)x(\d+)$/', $size, $matches)) {
            $widthMm = (int) $matches[1];
            $heightMm = (int) $matches[2];
        }

        $batch->loadMissing('product', 'warehouse');
        $labelSvg = $barcodeService->generateBatchLabelSvg($batch, $widthMm, $heightMm, $barcodeSettings);

        return view('products.print-label', [
            'title' => 'طباعة ملصق تشغيلة: '.$batch->batch_number,
            'items' => [
                [
                    'name' => ($batch->product?->name ?? 'تشغيلة').' ('.$batch->batch_number.')',
                    'barcode_value' => $batch->batch_number,
                    'label_svg' => $labelSvg,
                    'quantity' => $quantity,
                ],
            ],
            'totalQuantity' => $quantity,
            'autoPrint' => $request->boolean('autoprint', false),
            'widthMm' => $widthMm,
            'heightMm' => $heightMm,
            'size' => $widthMm.'x'.$heightMm,
            'barcodeSettings' => $barcodeSettings,
        ]);
    }
}
