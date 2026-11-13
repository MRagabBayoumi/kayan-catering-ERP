<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Warehouse;
use App\Services\SalesTendersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function __construct(protected SalesTendersService $salesService) {}

    /**
     * List Hospitals & Contracts.
     */
    public function index(): View
    {
        Gate::authorize('manage-sales');

        $hospitals = Hospital::with('siteWarehouse')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.hospitals.index', compact('hospitals'));
    }

    /**
     * Create Hospital Form.
     */
    public function create(): View
    {
        Gate::authorize('manage-sales');

        return view('sales.hospitals.create');
    }

    /**
     * Store Hospital & Auto-create Site Warehouse.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-sales');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contract_number' => 'required|string|max:100|unique:hospitals,contract_number',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'daily_patient_capacity' => 'required|integer|min:1',
            'meal_price_breakfast' => 'required|numeric|min:0',
            'meal_price_lunch' => 'required|numeric|min:0',
            'meal_price_dinner' => 'required|numeric|min:0',
            'contract_start_date' => 'required|date',
            'contract_end_date' => 'required|date|after:contract_start_date',
        ]);

        $hospitalCode = 'HOSP-'.str_pad((string) (Hospital::count() + 1), 3, '0', STR_PAD_LEFT);

        $hospital = Hospital::create([
            'code' => $hospitalCode,
            'name' => $validated['name'],
            'contract_number' => $validated['contract_number'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'daily_patient_capacity' => $validated['daily_patient_capacity'],
            'meal_price_breakfast' => $validated['meal_price_breakfast'],
            'meal_price_lunch' => $validated['meal_price_lunch'],
            'meal_price_dinner' => $validated['meal_price_dinner'],
            'contract_status' => Hospital::CONTRACT_ACTIVE,
            'contract_start_date' => $validated['contract_start_date'],
            'contract_end_date' => $validated['contract_end_date'],
            'is_active' => true,
        ]);

        // Automatically create dedicated site warehouse for hospital kitchen
        Warehouse::create([
            'code' => 'WH-SITE-'.$hospital->code,
            'name' => 'مخزن مطبخ موقع '.$hospital->name,
            'type' => Warehouse::TYPE_SITE,
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);

        return redirect()->route('sales.hospitals.index')->with('success', 'تم إضافة المستشفى وتأسيس مخزن الموقع الخاص بها بنجاح.');
    }

    /**
     * Terminate Hospital Contract.
     */
    public function terminate(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('manage-sales');

        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $this->salesService->terminateContract($id, $validated['reason']);

        return redirect()->route('sales.hospitals.index')->with('success', 'تم إنهاء التعاقد وأرشفة الجهة بنجاح.');
    }
}
