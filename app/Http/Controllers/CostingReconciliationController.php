<?php

namespace App\Http\Controllers;

use App\Models\DailySiteCensus;
use App\Models\DietScheduleItem;
use App\Models\Hospital;
use App\Models\SiteKitchenIssue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CostingReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-costing');

        $hospitals = Hospital::where('is_active', true)->orderBy('name')->get();
        $hospitalId = $request->input('hospital_id', $hospitals->first()?->id);
        $hospital = $hospitals->firstWhere('id', (int) $hospitalId) ?? $hospitals->first();

        $date = $request->input('date', Carbon::today()->toDateString());
        $carbonDate = Carbon::parse($date);

        // 1. Fetch Daily Site Census (أعداد الموقع واختيار الشيف)
        $census = null;
        if ($hospital) {
            $census = DailySiteCensus::with(['items.dietSchedule', 'siteManager'])
                ->where('hospital_id', $hospital->id)
                ->whereDate('census_date', $date)
                ->first();
        }

        // 2. Fetch Actual Kitchen Issues from Site Warehouse
        $actualIssues = collect();
        if ($hospital) {
            $actualIssues = SiteKitchenIssue::with('product')
                ->where('hospital_id', $hospital->id)
                ->whereDate('issue_date', $date)
                ->get();
        }

        // 3. Calculate Standard Expected Requirements
        // Map category type based on site manager's choice:
        // protein_choice: meat -> 'protein_meat', poultry -> 'protein_poultry'
        // carb_choice: rice_vermicelli -> 'carb_rice_vermicelli', plain_rice -> 'carb_plain_rice', pasta -> 'carb_pasta'
        // veg_choice: cooked_veg -> 'veg_cooked', beans/cowpea -> 'veg_dry_legume'
        $activeCategories = ['daily_fixed'];
        if ($census) {
            $activeCategories[] = $census->protein_choice === 'meat' ? 'protein_meat' : 'protein_poultry';
            $activeCategories[] = match ($census->carb_choice) {
                'rice_vermicelli' => 'carb_rice_vermicelli',
                'plain_rice' => 'carb_plain_rice',
                'pasta' => 'carb_pasta',
                default => 'carb_rice_vermicelli',
            };
            $activeCategories[] = $census->veg_choice === 'cooked_veg' ? 'veg_cooked' : 'veg_dry_legume';
        }

        // Group actual quantities by product_id
        $actualGrouped = $actualIssues->groupBy('product_id')->map(function ($rows) {
            return [
                'total_qty' => $rows->sum('quantity_issued'),
                'unit' => $rows->first()->unit,
                'product' => $rows->first()->product,
            ];
        });

        // Calculate standard requirements per product across all census items
        $reconciliationRows = [];
        $totalStandardCost = 0.00;
        $totalActualCost = 0.00;
        $totalOverWasteCost = 0.00;
        $totalShortageCost = 0.00;

        if ($census && $census->items->isNotEmpty()) {
            // Find schedule items matching active categories
            $scheduleItems = DietScheduleItem::with('product')
                ->whereIn('category_type', $activeCategories)
                ->get()
                ->groupBy('product_id');

            foreach ($scheduleItems as $productId => $items) {
                $sampleItem = $items->first();
                $product = $sampleItem->product;
                $costPrice = (float) ($product->cost_price ?? 0.00);

                // Compute total expected quantity: sum over all census items
                $standardQty = 0.00;
                foreach ($census->items as $censusItem) {
                    $itemDef = $items->firstWhere('diet_schedule_id', $censusItem->diet_schedule_id)
                        ?? $sampleItem; // Fallback to general item definition

                    $portion = (float) $itemDef->portion_quantity;
                    $standardQty += ($portion * $censusItem->patient_count);
                }

                $actualData = $actualGrouped->get($productId);
                $actualQty = $actualData ? (float) $actualData['total_qty'] : 0.00;
                $unit = $sampleItem->unit ?? ($actualData['unit'] ?? 'kg');

                $varianceQty = $actualQty - $standardQty;
                $varianceCost = $varianceQty * $costPrice;

                $standardCost = $standardQty * $costPrice;
                $actualCost = $actualQty * $costPrice;

                $totalStandardCost += $standardCost;
                $totalActualCost += $actualCost;

                // Status Evaluation:
                // Over-consumption: actual > standard by > 2%
                // Shortage: actual < standard by > 2%
                // Normal: within +/- 2%
                $status = 'normal';
                $pct = $standardQty > 0 ? round(($varianceQty / $standardQty) * 100, 1) : 0;

                if ($varianceQty > 0.02 * $standardQty && $actualQty > 0) {
                    $status = 'waste_alert';
                    $totalOverWasteCost += $varianceCost;
                } elseif ($varianceQty < -0.02 * $standardQty) {
                    $status = 'shortage_alert';
                    $totalShortageCost += abs($varianceCost);
                }

                $reconciliationRows[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'category_type' => $sampleItem->category_type,
                    'unit' => $unit,
                    'cost_price' => $costPrice,
                    'standard_qty' => round($standardQty, 3),
                    'actual_qty' => round($actualQty, 3),
                    'variance_qty' => round($varianceQty, 3),
                    'variance_pct' => $pct,
                    'variance_cost' => round($varianceCost, 2),
                    'status' => $status,
                ];

                // Remove processed from actualGrouped
                $actualGrouped->forget($productId);
            }

            // Any items issued by kitchen that were not in standard definition (بند صرف زائد تماماً)
            foreach ($actualGrouped as $productId => $unmatched) {
                $product = $unmatched['product'];
                $costPrice = (float) ($product->cost_price ?? 0.00);
                $actualQty = (float) $unmatched['total_qty'];
                $varianceCost = $actualQty * $costPrice;

                $totalActualCost += $varianceCost;
                $totalOverWasteCost += $varianceCost;

                $reconciliationRows[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name.' (منصرف غير مدرج بالمقنن)',
                    'category_type' => 'extra_unplanned',
                    'unit' => $unmatched['unit'],
                    'cost_price' => $costPrice,
                    'standard_qty' => 0.00,
                    'actual_qty' => round($actualQty, 3),
                    'variance_qty' => round($actualQty, 3),
                    'variance_pct' => 100.0,
                    'variance_cost' => round($varianceCost, 2),
                    'status' => 'waste_alert',
                ];
            }
        }

        return view('costing.reconciliation.index', compact(
            'hospitals',
            'hospital',
            'date',
            'carbonDate',
            'census',
            'reconciliationRows',
            'totalStandardCost',
            'totalActualCost',
            'totalOverWasteCost',
            'totalShortageCost'
        ));
    }
}
