<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Hospital;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WasteRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SiteWasteController extends Controller
{
    /**
     * Display list of waste and spoilage records.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();
        $hospitals = $user->accessibleHospitals();

        $selectedHospitalId = $request->query('hospital_id', $hospitals->first()?->id);
        $selectedCategory = $request->query('category');

        $query = WasteRecord::with(['hospital', 'product', 'reporter', 'approver', 'warehouse'])
            ->latest('waste_date');

        if ($selectedHospitalId) {
            $query->where('hospital_id', $selectedHospitalId);
        }

        if ($selectedCategory) {
            $query->where('waste_category', $selectedCategory);
        }

        $wasteRecords = $query->paginate(15)->withQueryString();

        $products = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('site.waste.index', [
            'title' => 'محاضر وسجل الهدر والتوالف وسواقط الطهي (Waste & Spoilage)',
            'wasteRecords' => $wasteRecords,
            'hospitals' => $hospitals,
            'selectedHospitalId' => $selectedHospitalId,
            'selectedCategory' => $selectedCategory,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store new Waste Record and record inventory/accounting impact.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-site-operations');

        $validated = $request->validate([
            'hospital_id' => ['required', 'exists:hospitals,id'],
            'product_id' => ['required', 'exists:products,id'],
            'waste_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'waste_category' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $hospital = Hospital::with('costCenter')->findOrFail($validated['hospital_id']);
        $product = Product::findOrFail($validated['product_id']);

        $siteWarehouse = Warehouse::where('hospital_id', $hospital->id)
            ->where('type', Warehouse::TYPE_SITE)
            ->first();

        $unitCost = (float) ($validated['unit_cost'] ?? $product->cost_price ?? 10.0);
        $qty = (float) $validated['quantity'];
        $totalCost = $qty * $unitCost;

        $isAbnormal = in_array($validated['waste_category'], [
            WasteRecord::CATEGORY_UNEXPLAINED_SHORTAGE,
            WasteRecord::CATEGORY_TRANSIT_DAMAGE,
            WasteRecord::CATEGORY_EXPIRED_BATCH,
        ]) || $totalCost > 500;

        DB::transaction(function () use ($hospital, $product, $siteWarehouse, $validated, $qty, $unitCost, $totalCost, $isAbnormal) {
            $count = WasteRecord::whereDate('created_at', Carbon::today())->count() + 1;
            $wasteNumber = 'WST-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $waste = WasteRecord::create([
                'waste_number' => $wasteNumber,
                'waste_date' => $validated['waste_date'],
                'warehouse_id' => $siteWarehouse?->id,
                'hospital_id' => $hospital->id,
                'cost_center_id' => $hospital->costCenter?->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit' => $product->unit ?? 'kg',
                'unit_cost' => $unitCost,
                'total_cost_value' => $totalCost,
                'waste_category' => $validated['waste_category'],
                'is_abnormal' => $isAbnormal,
                'reason' => $validated['reason'],
                'reported_by' => auth()->id() ?? 1,
                'status' => WasteRecord::STATUS_APPROVED,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Deduct from Site Warehouse balance if exists
            if ($siteWarehouse) {
                $siteBal = StockBalance::where('product_id', $product->id)
                    ->where('warehouse_id', $siteWarehouse->id)
                    ->first();
                if ($siteBal) {
                    $siteBal->quantity = max(0, $siteBal->quantity - $qty);
                    $siteBal->save();
                }

                StockMovement::create([
                    'movement_number' => 'MOV-WST-'.date('YmdHis').'-'.rand(100, 999),
                    'type' => StockMovement::TYPE_OUT,
                    'product_id' => $product->id,
                    'from_warehouse_id' => $siteWarehouse->id,
                    'to_warehouse_id' => null,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'reference_type' => 'WasteRecord',
                    'reference_id' => $waste->id,
                    'user_id' => auth()->id() ?? 1,
                    'notes' => "إثبات هدر/تالف بالموقع ({$validated['waste_category']}) - محضر {$wasteNumber}",
                ]);
            }

            // Balanced Accounting Entry: Debit Waste Loss (505000), Credit Site Inventory (101500)
            if ($totalCost > 0) {
                $wasteAcc = ChartOfAccount::where('code', '505000')->first();
                $siteInvAcc = ChartOfAccount::where('code', '101500')->first();

                if ($wasteAcc && $siteInvAcc) {
                    $entry = JournalEntry::create([
                        'entry_number' => 'JV-WST-'.date('Ymd').'-'.rand(1000, 9999),
                        'entry_date' => $validated['waste_date'],
                        'description' => "إثبات خسائر هدر وتوالف موقع {$hospital->name} - محضر {$wasteNumber}",
                        'source_type' => 'waste_record',
                        'source_id' => $waste->id,
                        'created_by' => auth()->id() ?? 1,
                        'is_posted' => true,
                    ]);

                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'chart_of_account_id' => $wasteAcc->id,
                        'cost_center_id' => $hospital->costCenter?->id,
                        'account_type' => 'expense',
                        'account_name' => $wasteAcc->name,
                        'debit' => $totalCost,
                        'credit' => 0.00,
                        'notes' => "خسائر هدر {$validated['reason']}",
                    ]);

                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'chart_of_account_id' => $siteInvAcc->id,
                        'account_type' => 'inventory',
                        'account_name' => $siteInvAcc->name,
                        'debit' => 0.00,
                        'credit' => $totalCost,
                        'notes' => "خصم تالف من مخزن الموقع {$siteWarehouse?->name}",
                    ]);
                }
            }
        });

        return redirect()->route('site.waste.index', ['hospital_id' => $hospital->id])
            ->with('status', 'success')
            ->with('message', 'تم تسجيل محضر الهدر والتالف وخصم الكمية من مخزن الموقع وترحيل الأثر المالي لمركز تكلفة المستشفى.');
    }
}
