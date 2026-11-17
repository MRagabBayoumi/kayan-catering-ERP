<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\ConsumptionLog;
use App\Models\Contract;
use App\Models\DailySiteCensus;
use App\Models\DisbursementOrder;
use App\Models\FinancialVoucher;
use App\Models\Hospital;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SiteKitchenIssue;
use App\Models\VehicleTripDestination;
use App\Models\WasteRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CostCenterService
{
    /**
     * Calculate comprehensive Hospital Cost Center Profitability and P&L.
     */
    public function getHospitalProfitability(int $hospitalId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $hospital = Hospital::with(['activeContract.mealPrices.mealType', 'costCenter'])->findOrFail($hospitalId);

        $startDate = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        // 1. Calculate Revenue from Served Meals & Active Contract
        $activeContract = $hospital->activeContract ?? Contract::where('hospital_id', $hospitalId)->latest()->first();

        // Get total patient meals from DailySiteCensus in period
        $censuses = DailySiteCensus::where('hospital_id', $hospitalId)
            ->whereDate('census_date', '>=', $startDate->toDateString())
            ->whereDate('census_date', '<=', $endDate->toDateString())
            ->get();

        $totalPatientsServed = (int) $censuses->sum('total_patient_count');

        // Estimate revenue: if contract has meal prices, calculate by meal types, or default average contract meal price
        $avgMealPrice = 85.00;
        if ($activeContract && $activeContract->mealPrices()->count() > 0) {
            $avgMealPrice = (float) $activeContract->mealPrices()->avg('price_per_meal');
        }

        // Each patient receives 3 meals (breakfast, lunch, dinner)
        $totalMealsCount = $totalPatientsServed * 3;
        $totalGrossRevenue = $totalMealsCount * $avgMealPrice;

        // Check if formal revenue entries exist in journal lines for this hospital cost center
        $postedJournalRevenue = (float) JournalEntryLine::where('cost_center_id', $hospital->costCenter?->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereDate('entry_date', '>=', $startDate->toDateString())
                    ->whereDate('entry_date', '<=', $endDate->toDateString());
            })
            ->where('credit', '>', 0)
            ->where(function ($q) {
                $q->where('account_type', 'revenue')
                    ->orWhereHas('chartOfAccount', fn ($ca) => $ca->where('code', '401000'));
            })
            ->sum('credit');

        if ($postedJournalRevenue > 0) {
            $totalGrossRevenue = $postedJournalRevenue;
        }

        // 2. Direct Food Cost from Consumption Logs & Kitchen Issues
        $consumptionLogs = ConsumptionLog::where('hospital_id', $hospitalId)
            ->whereDate('consumption_date', '>=', $startDate->toDateString())
            ->whereDate('consumption_date', '<=', $endDate->toDateString())
            ->get();

        $actualFoodCost = (float) $consumptionLogs->sum('actual_fifo_cost');
        if ($actualFoodCost <= 0.00) {
            // Fallback to kitchen issues cost
            $kitchenIssues = SiteKitchenIssue::with('product')
                ->where('hospital_id', $hospitalId)
                ->whereDate('issue_date', '>=', $startDate->toDateString())
                ->whereDate('issue_date', '<=', $endDate->toDateString())
                ->get();

            foreach ($kitchenIssues as $ki) {
                $cost = (float) ($ki->product->cost_price ?? 0.00);
                $actualFoodCost += ((float) $ki->quantity_issued) * $cost;
            }
        }

        // 3. Allocated Logistics & Fleet Transportation Costs
        $allocatedFleetCosts = (float) VehicleTripDestination::where('hospital_id', $hospitalId)
            ->whereHas('trip', function ($q) use ($startDate, $endDate) {
                $q->whereDate('trip_date', '>=', $startDate->toDateString())
                    ->whereDate('trip_date', '<=', $endDate->toDateString());
            })
            ->sum('allocated_amount');

        // 4. Site Cash Expenses & Bread Petty Cash (from Vouchers & Disbursements)
        $siteExpenses = (float) FinancialVoucher::where('hospital_id', $hospitalId)
            ->where('type', FinancialVoucher::TYPE_PAYMENT)
            ->whereDate('voucher_date', '>=', $startDate->toDateString())
            ->whereDate('voucher_date', '<=', $endDate->toDateString())
            ->sum('amount');

        $disbursedSiteExpenses = (float) DisbursementOrder::where('hospital_id', $hospitalId)
            ->where('status', DisbursementOrder::STATUS_COMPLETED)
            ->whereDate('order_date', '>=', $startDate->toDateString())
            ->whereDate('order_date', '<=', $endDate->toDateString())
            ->sum('amount');

        $totalSiteExpenses = max($siteExpenses, $disbursedSiteExpenses);

        // 5. Waste & Spoilage Cost
        $wasteRecords = WasteRecord::where('hospital_id', $hospitalId)
            ->whereDate('waste_date', '>=', $startDate->toDateString())
            ->whereDate('waste_date', '<=', $endDate->toDateString())
            ->get();

        $totalWasteCost = (float) $wasteRecords->sum('total_cost_value');

        // 6. Estimated Site Kitchen Labor (Allocated from contract or base 15% of food cost)
        $estimatedLaborCost = round($totalGrossRevenue * 0.12, 2);

        // 7. Totals & Profitability Margins
        $totalOperatingCost = $actualFoodCost + $estimatedLaborCost + $allocatedFleetCosts + $totalSiteExpenses + $totalWasteCost;
        $netContribution = $totalGrossRevenue - $totalOperatingCost;
        $profitMarginPercentage = $totalGrossRevenue > 0 ? round(($netContribution / $totalGrossRevenue) * 100, 2) : 0.00;
        $foodCostPercentage = $totalGrossRevenue > 0 ? round(($actualFoodCost / $totalGrossRevenue) * 100, 2) : 0.00;

        return [
            'hospital' => $hospital,
            'contract' => $activeContract,
            'period' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'kpis' => [
                'total_patient_census' => $totalPatientsServed,
                'total_meals_count' => $totalMealsCount,
                'avg_meal_price' => $avgMealPrice,
                'total_revenue' => $totalGrossRevenue,
                'food_cost' => $actualFoodCost,
                'labor_cost' => $estimatedLaborCost,
                'fleet_cost' => $allocatedFleetCosts,
                'site_expenses' => $totalSiteExpenses,
                'waste_cost' => $totalWasteCost,
                'total_cost' => $totalOperatingCost,
                'net_profit' => $netContribution,
                'profit_margin_pct' => $profitMarginPercentage,
                'food_cost_pct' => $foodCostPercentage,
            ],
            'drilldown' => [
                'censuses_count' => $censuses->count(),
                'consumption_logs' => $consumptionLogs->take(10),
                'waste_records' => $wasteRecords->take(10),
            ],
        ];
    }

    /**
     * Recognize and record contractual revenue for hospital meals into Journal Entries and Cost Center.
     */
    public function recognizeHospitalRevenue(
        int $hospitalId,
        string $date,
        int $mealCount,
        ?float $overridePricePerMeal = null,
        int $userId = 1,
        ?string $notes = null
    ): JournalEntry {
        return DB::transaction(function () use ($hospitalId, $date, $mealCount, $overridePricePerMeal, $userId, $notes) {
            $hospital = Hospital::with(['activeContract.mealPrices', 'costCenter'])->findOrFail($hospitalId);
            $contract = $hospital->activeContract ?? Contract::where('hospital_id', $hospitalId)->latest()->first();

            $pricePerMeal = 85.00;
            if ($overridePricePerMeal !== null && $overridePricePerMeal > 0) {
                $pricePerMeal = (float) $overridePricePerMeal;
            } elseif ($contract && $contract->mealPrices()->count() > 0) {
                $pricePerMeal = (float) $contract->mealPrices()->avg('price_per_meal');
            }

            $totalRevenue = $mealCount * $pricePerMeal;

            $arAccount = ChartOfAccount::where('code', '101600')->first();
            $revAccount = ChartOfAccount::where('code', '401000')->first();

            $entryNumber = 'JV-REV-'.date('Ymd', strtotime($date)).'-HOSP'.$hospitalId.'-'.rand(100, 999);

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $date,
                'description' => "إثبات استحقاق إيراد وجبات تغذية مستشفى {$hospital->name} - بعدد {$mealCount} وجبة",
                'source_type' => 'hospital_revenue',
                'source_id' => $contract?->id,
                'created_by' => $userId,
                'is_posted' => true,
            ]);

            // Debit Accounts Receivable (101600)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $arAccount?->id,
                'account_type' => 'hospital',
                'account_id' => $hospital->id,
                'account_name' => "مستشفى {$hospital->name}",
                'cost_center_id' => $hospital->costCenter?->id,
                'debit' => $totalRevenue,
                'credit' => 0.00,
                'notes' => $notes ?? "استحقاق تغذية عن يوم {$date}",
            ]);

            // Credit Catering Revenue (401000)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $revAccount?->id,
                'account_type' => 'revenue',
                'account_name' => $revAccount?->name ?? 'إيرادات عقود تغذية المستشفيات',
                'cost_center_id' => $hospital->costCenter?->id,
                'debit' => 0.00,
                'credit' => $totalRevenue,
                'notes' => "إيراد تعاقدي {$mealCount} وجبة × {$pricePerMeal} ج.م",
            ]);

            return $entry;
        });
    }
}
