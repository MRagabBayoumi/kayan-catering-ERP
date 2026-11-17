<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Hospital;
use App\Services\CostCenterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CostCenterProfitabilityController extends Controller
{
    public function __construct(
        protected CostCenterService $costCenterService
    ) {}

    /**
     * Hospital Cost Center Profitability & P&L Dashboard.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-finance');

        $hospitals = Hospital::where('is_active', true)->orderBy('name')->get();
        $selectedHospitalId = $request->query('hospital_id', $hospitals->first()?->id);

        $fromDate = $request->query('from_date', Carbon::now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', Carbon::now()->endOfMonth()->toDateString());

        $profitabilityData = null;
        if ($selectedHospitalId) {
            $profitabilityData = $this->costCenterService->getHospitalProfitability(
                (int) $selectedHospitalId,
                $fromDate,
                $toDate
            );
        }

        $allCostCenters = CostCenter::with('hospital')->get();

        return view('finance.profitability.index', [
            'title' => 'تحليل ربحية المستشفيات ومراكز التكلفة (Contract Profitability)',
            'hospitals' => $hospitals,
            'selectedHospitalId' => $selectedHospitalId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'profitability' => $profitabilityData,
            'costCenters' => $allCostCenters,
        ]);
    }
}
