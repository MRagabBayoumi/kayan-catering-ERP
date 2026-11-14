<?php

namespace App\Http\Controllers;

use App\Models\FinancialVoucher;
use App\Models\Hospital;
use App\Models\Treasury;
use App\Models\User;
use App\Models\VehicleDispatchTrip;
use App\Models\VehicleTripDestination;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VehicleDispatchTripController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-vehicle-trips');

        $trips = VehicleDispatchTrip::with(['driver', 'creator', 'destinations.hospital'])
            ->orderBy('trip_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $totalFuelThisMonth = VehicleDispatchTrip::whereMonth('trip_date', Carbon::now()->month)
            ->whereYear('trip_date', Carbon::now()->year)
            ->sum('fuel_expense');

        $totalTripsThisMonth = VehicleDispatchTrip::whereMonth('trip_date', Carbon::now()->month)
            ->whereYear('trip_date', Carbon::now()->year)
            ->count();

        return view('warehouse.trips.index', compact('trips', 'totalFuelThisMonth', 'totalTripsThisMonth'));
    }

    public function create(): View
    {
        Gate::authorize('manage-vehicle-trips');

        $drivers = User::where('role', User::ROLE_DRIVER)->orWhere('role', User::ROLE_STOREKEEPER)->get();
        $hospitals = Hospital::where('is_active', true)->orderBy('name')->get();
        $treasuries = Treasury::all();

        return view('warehouse.trips.create', compact('drivers', 'hospitals', 'treasuries'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-vehicle-trips');

        $validated = $request->validate([
            'vehicle_plate' => 'required|string|max:50',
            'driver_id' => 'nullable|exists:users,id',
            'trip_date' => 'required|date',
            'fuel_expense' => 'required|numeric|min:0',
            'toll_and_other_expense' => 'nullable|numeric|min:0',
            'hospital_ids' => 'required|array|min:1',
            'hospital_ids.*' => 'required|exists:hospitals,id',
            'treasury_id' => 'nullable|exists:treasuries,id',
            'notes' => 'nullable|string',
        ]);

        $fuelExpense = (float) $validated['fuel_expense'];
        $tollExpense = (float) ($validated['toll_and_other_expense'] ?? 0.00);
        $totalTripExpense = $fuelExpense + $tollExpense;

        $hospitalIds = $validated['hospital_ids'];
        $hospitalCount = count($hospitalIds);
        $allocatedPerHospital = $hospitalCount > 0 ? round($totalTripExpense / $hospitalCount, 2) : 0.00;

        DB::transaction(function () use ($validated, $totalTripExpense, $fuelExpense, $tollExpense, $hospitalIds, $allocatedPerHospital) {
            $tripNumber = 'TRIP-'.date('Ymd').'-'.str_pad((string) (VehicleDispatchTrip::whereDate('created_at', Carbon::today())->count() + 1), 3, '0', STR_PAD_LEFT);

            $voucherId = null;
            if (! empty($validated['treasury_id']) && $totalTripExpense > 0) {
                $treasury = Treasury::find($validated['treasury_id']);
                if ($treasury) {
                    $voucher = FinancialVoucher::create([
                        'voucher_number' => 'VCH-FUEL-'.date('Ymd').'-'.rand(100, 999),
                        'type' => FinancialVoucher::TYPE_PAYMENT,
                        'treasury_id' => $treasury->id,
                        'amount' => $totalTripExpense,
                        'purpose' => 'تصفية عهدة تفويل سيارة توزيع رقم '.$validated['vehicle_plate'].' لنقل بضائع لـ '.count($hospitalIds).' مواقع',
                        'payment_method' => 'cash',
                        'created_by' => auth()->id(),
                        'voucher_date' => $validated['trip_date'],
                    ]);

                    $treasury->decrement('balance', $totalTripExpense);
                    $voucherId = $voucher->id;
                }
            }

            $trip = VehicleDispatchTrip::create([
                'trip_number' => $tripNumber,
                'vehicle_plate' => $validated['vehicle_plate'],
                'driver_id' => $validated['driver_id'] ?? null,
                'trip_date' => $validated['trip_date'],
                'fuel_expense' => $fuelExpense,
                'toll_and_other_expense' => $tollExpense,
                'total_trip_expense' => $totalTripExpense,
                'financial_voucher_id' => $voucherId,
                'created_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($hospitalIds as $hospId) {
                VehicleTripDestination::create([
                    'vehicle_dispatch_trip_id' => $trip->id,
                    'hospital_id' => $hospId,
                    'allocated_amount' => $allocatedPerHospital,
                    'notes' => 'تحميل نصيب الموقع من تفويل ونقل الرحلة بالتساوي',
                ]);
            }
        });

        return redirect()->route('warehouse.trips.index')->with('success', 'تم تسجيل رحلة سيارة التوزيع وتصفية مبلغ الوقود وتوزيعه بالتساوي على المواقع المستفيدة.');
    }
}
