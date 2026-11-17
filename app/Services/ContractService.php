<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractMealPrice;
use App\Models\CostCenter;
use App\Models\FeasibilityStudy;
use App\Models\FeasibilityStudyItem;
use App\Models\Hospital;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractService
{
    /**
     * 1. Calculate and store a Feasibility Study for a Tender.
     */
    public function createFeasibilityStudy(array $data, array $costItems, int $userId): FeasibilityStudy
    {
        return DB::transaction(function () use ($data, $costItems, $userId) {
            $count = FeasibilityStudy::whereDate('created_at', Carbon::today())->count() + 1;
            $studyNumber = 'FS-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $expectedMeals = (int) $data['expected_total_meals'];
            $targetPrice = (float) $data['target_selling_price_per_meal'];
            $expectedRevenue = $expectedMeals * $targetPrice;

            $foodCost = 0.00;
            $laborCost = 0.00;
            $transportCost = 0.00;
            $consumablesCost = 0.00;
            $operatingCost = 0.00;

            foreach ($costItems as $item) {
                $itemTotal = ((float) $item['quantity']) * ((float) $item['unit_cost']);
                switch ($item['cost_category'] ?? 'other') {
                    case 'food':
                        $foodCost += $itemTotal;
                        break;
                    case 'labor':
                        $laborCost += $itemTotal;
                        break;
                    case 'transport':
                        $transportCost += $itemTotal;
                        break;
                    case 'consumables':
                        $consumablesCost += $itemTotal;
                        break;
                    default:
                        $operatingCost += $itemTotal;
                        break;
                }
            }

            $totalCost = $foodCost + $laborCost + $transportCost + $consumablesCost + $operatingCost;
            $grossMargin = $expectedRevenue - $totalCost;
            $netMarginPct = $expectedRevenue > 0 ? round(($grossMargin / $expectedRevenue) * 100, 2) : 0.00;

            $costPerMeal = $expectedMeals > 0 ? ($totalCost / $expectedMeals) : 0.00;
            $breakEvenMeals = $targetPrice > 0 ? (int) ceil($totalCost / $targetPrice) : 0;

            $study = FeasibilityStudy::create([
                'study_number' => $studyNumber,
                'tender_id' => $data['tender_id'] ?? null,
                'hospital_name' => $data['hospital_name'],
                'planned_duration_months' => (int) ($data['planned_duration_months'] ?? 12),
                'expected_total_meals' => $expectedMeals,
                'target_selling_price_per_meal' => $targetPrice,
                'expected_revenue' => $expectedRevenue,
                'estimated_food_cost' => $foodCost,
                'estimated_labor_cost' => $laborCost,
                'estimated_transport_cost' => $transportCost,
                'estimated_consumables_cost' => $consumablesCost,
                'estimated_operating_cost' => $operatingCost,
                'estimated_total_cost' => $totalCost,
                'expected_gross_margin' => $grossMargin,
                'expected_net_margin_percentage' => $netMarginPct,
                'break_even_meals' => $breakEvenMeals,
                'status' => FeasibilityStudy::STATUS_APPROVED,
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($costItems as $ci) {
                $qty = (float) $ci['quantity'];
                $unitCost = (float) $ci['unit_cost'];
                FeasibilityStudyItem::create([
                    'feasibility_study_id' => $study->id,
                    'cost_category' => $ci['cost_category'],
                    'item_name' => $ci['item_name'],
                    'quantity' => $qty,
                    'unit' => $ci['unit'] ?? 'unit',
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
                    'notes' => $ci['notes'] ?? null,
                ]);
            }

            return $study->load('items', 'tender');
        });
    }

    /**
     * 2. Activate a Contract and link Cost Center & Meal Prices.
     */
    public function activateContract(array $data, array $mealPrices, int $userId): Contract
    {
        return DB::transaction(function () use ($data, $mealPrices, $userId) {
            $hospital = Hospital::findOrFail($data['hospital_id']);

            // Ensure Cost Center exists for this Hospital
            $costCenter = CostCenter::firstOrCreate(
                [
                    'hospital_id' => $hospital->id,
                ],
                [
                    'code' => 'CC-HOSP-'.$hospital->id,
                    'name' => 'مركز تكلفة: '.$hospital->name,
                    'type' => CostCenter::TYPE_HOSPITAL,
                    'is_active' => true,
                ]
            );

            $count = Contract::whereDate('created_at', Carbon::today())->count() + 1;
            $contractNumber = $data['contract_number'] ?? ('CTR-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT));

            $contract = Contract::create([
                'contract_number' => $contractNumber,
                'hospital_id' => $hospital->id,
                'tender_id' => $data['tender_id'] ?? null,
                'cost_center_id' => $costCenter->id,
                'title' => $data['title'] ?? ('عقد توريد وتغذية نزلاء '.$hospital->name),
                'start_date' => $data['start_date'] ?? Carbon::today(),
                'end_date' => $data['end_date'] ?? Carbon::today()->addYear(),
                'status' => Contract::STATUS_ACTIVE,
                'contract_value' => (float) ($data['contract_value'] ?? 0.00),
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'payment_terms_days' => (int) ($data['payment_terms_days'] ?? 30),
                'penalty_terms' => $data['penalty_terms'] ?? null,
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($mealPrices as $mp) {
                ContractMealPrice::create([
                    'contract_id' => $contract->id,
                    'meal_type_id' => $mp['meal_type_id'] ?? null,
                    'diet_category' => $mp['diet_category'] ?? 'standard',
                    'price_per_meal' => (float) $mp['price_per_meal'],
                    'minimum_daily_quantity' => (int) ($mp['minimum_daily_quantity'] ?? 0),
                    'notes' => $mp['notes'] ?? null,
                ]);
            }

            return $contract->load('mealPrices.mealType', 'hospital', 'costCenter');
        });
    }
}
