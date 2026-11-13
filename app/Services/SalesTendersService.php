<?php

namespace App\Services;

use App\Models\CompetitorPrice;
use App\Models\Hospital;
use App\Models\Tender;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesTendersService
{
    /**
     * 1. Register a new Tender in the Sales Department.
     */
    public function createTender(array $data): Tender
    {
        $tenderNumber = $data['tender_number'] ?? 'TND-'.date('Y').'-'.str_pad((string) (Tender::count() + 1), 4, '0', STR_PAD_LEFT);

        return Tender::create([
            'tender_number' => $tenderNumber,
            'title' => $data['title'],
            'hospital_id' => $data['hospital_id'] ?? null,
            'entity_name' => $data['entity_name'],
            'announcement_date' => $data['announcement_date'] ?? Carbon::today(),
            'envelope_opening_date' => $data['envelope_opening_date'] ?? null,
            'bid_bond_amount' => $data['bid_bond_amount'] ?? 0.00,
            'final_guarantee_amount' => $data['final_guarantee_amount'] ?? 0.00,
            'booklet_cost' => $data['booklet_cost'] ?? 0.00,
            'status' => $data['status'] ?? Tender::STATUS_UNDER_STUDY,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * 2. Archive Competitor Prices for Next Year's Tenders Reference.
     */
    public function archiveCompetitorPrice(int $tenderId, array $priceData): CompetitorPrice
    {
        $ourPrice = isset($priceData['our_bid_price']) ? (float) $priceData['our_bid_price'] : null;
        $compPrice = (float) $priceData['competitor_price'];

        $variancePct = null;
        if ($ourPrice && $ourPrice > 0) {
            $variancePct = round((($compPrice - $ourPrice) / $ourPrice) * 100, 2);
        }

        return CompetitorPrice::create([
            'tender_id' => $tenderId,
            'competitor_name' => $priceData['competitor_name'],
            'item_or_meal_name' => $priceData['item_or_meal_name'],
            'our_bid_price' => $ourPrice,
            'competitor_price' => $compPrice,
            'variance_percentage' => $variancePct,
            'tender_year' => $priceData['tender_year'] ?? (int) date('Y'),
            'was_awarded' => $priceData['was_awarded'] ?? false,
            'notes' => $priceData['notes'] ?? null,
        ]);
    }

    /**
     * 3. Award Tender -> Automatically Setup Hospital & Site Warehouse.
     */
    public function awardTender(int $tenderId, array $contractDetails): Hospital
    {
        return DB::transaction(function () use ($tenderId, $contractDetails) {
            $tender = Tender::findOrFail($tenderId);
            $tender->status = Tender::STATUS_AWARDED;
            $tender->save();

            $hospitalCode = $contractDetails['hospital_code'] ?? 'HOSP-'.str_pad((string) (Hospital::count() + 1), 3, '0', STR_PAD_LEFT);

            $hospital = Hospital::create([
                'code' => $hospitalCode,
                'name' => $tender->entity_name,
                'contract_number' => $contractDetails['contract_number'] ?? 'CONTR-'.$tender->tender_number,
                'contact_person' => $contractDetails['contact_person'] ?? null,
                'phone' => $contractDetails['phone'] ?? null,
                'address' => $contractDetails['address'] ?? null,
                'daily_patient_capacity' => $contractDetails['daily_patient_capacity'] ?? 100,
                'default_buffer_percentage' => $contractDetails['default_buffer_percentage'] ?? 5.00,
                'is_active' => true,
                'contract_status' => Hospital::CONTRACT_ACTIVE,
                'contract_start_date' => $contractDetails['start_date'] ?? Carbon::today(),
                'contract_end_date' => $contractDetails['end_date'] ?? Carbon::today()->addYear(),
            ]);

            $tender->hospital_id = $hospital->id;
            $tender->save();

            // Automatically create a Sub-Warehouse for this hospital site
            Warehouse::create([
                'code' => 'WH-'.$hospitalCode,
                'name' => 'مخزن موقع '.$hospital->name,
                'type' => Warehouse::TYPE_MAIN,
                'location' => 'موقع مستشفى '.$hospital->name,
                'is_active' => true,
                'is_default' => false,
                'notes' => 'مخزن فرعي تابع لإدارة الموقع والمطبخ بالمستشفى',
            ]);

            return $hospital;
        });
    }

    /**
     * 4. Terminate a Hospital Contract (Sales Department).
     */
    public function terminateContract(int $hospitalId, string $reason): Hospital
    {
        return DB::transaction(function () use ($hospitalId, $reason) {
            $hospital = Hospital::findOrFail($hospitalId);
            $hospital->contract_status = Hospital::CONTRACT_TERMINATED;
            $hospital->is_active = false;
            $hospital->terminated_at = Carbon::now();
            $hospital->termination_reason = $reason;
            $hospital->save();

            return $hospital;
        });
    }
}
