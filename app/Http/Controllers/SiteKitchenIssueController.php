<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\SiteKitchenIssue;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SiteKitchenIssueController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();
        $allHospitals = $user->accessibleHospitals();

        $requestedHospitalId = $request->input('hospital_id');
        $hospital = null;

        if ($requestedHospitalId) {
            $hospital = $allHospitals->firstWhere('id', (int) $requestedHospitalId);
            if (! $hospital && ! $user->isAdmin()) {
                abort(403, 'غير مصرح لك بالوصول لبيانات هذا المستشفى.');
            }
        }

        $hospital = $hospital ?? $allHospitals->first();

        $date = $request->input('date', Carbon::today()->toDateString());

        $issues = collect();
        if ($hospital) {
            $issues = SiteKitchenIssue::with(['product', 'issuer'])
                ->where('hospital_id', $hospital->id)
                ->whereDate('issue_date', $date)
                ->orderBy('id', 'desc')
                ->get();
        }

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('site.kitchen_issues.index', compact('hospital', 'allHospitals', 'date', 'issues', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-site-operations');

        $user = auth()->user();
        $targetHospitalId = (int) $request->input('hospital_id');

        if (! $user->hasAccessToHospital($targetHospitalId)) {
            abort(403, 'غير مصرح لك بصرف مواد مطبخ لهذا المستشفى.');
        }

        $hospitalId = $targetHospitalId;

        $request->merge([
            'items' => array_values(array_filter(
                (array) $request->input('items', []),
                fn ($item) => ! empty($item['product_id']) && (float) ($item['quantity_issued'] ?? 0) > 0
            )),
        ]);

        $validated = $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_issued' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $issueNumber = 'KITCHEN-'.date('Ymd').'-'.rand(100, 999);

        DB::transaction(function () use ($hospitalId, $user, $validated, $issueNumber) {
            $hospital = Hospital::with('costCenter')->findOrFail($hospitalId);
            $siteWarehouse = Warehouse::where('hospital_id', $hospitalId)
                ->where('type', Warehouse::TYPE_SITE)
                ->first();

            $totalFoodCost = 0.00;

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity_issued'];
                $prodId = $item['product_id'];

                $issue = SiteKitchenIssue::create([
                    'hospital_id' => $hospitalId,
                    'issue_date' => $validated['issue_date'],
                    'issue_number' => $issueNumber,
                    'product_id' => $prodId,
                    'quantity_issued' => $qty,
                    'unit' => $item['unit'],
                    'issued_by' => $user->id,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Deduct from Hospital Site Warehouse if exists
                if ($siteWarehouse) {
                    $siteBal = StockBalance::where('product_id', $prodId)
                        ->where('warehouse_id', $siteWarehouse->id)
                        ->first();
                    if ($siteBal) {
                        $siteBal->quantity = max(0, $siteBal->quantity - $qty);
                        $siteBal->save();
                    }

                    // Deduct from batches using FEFO
                    $batch = InventoryBatch::where('product_id', $prodId)
                        ->where('warehouse_id', $siteWarehouse->id)
                        ->where('remaining_quantity', '>', 0)
                        ->availableFEFO()
                        ->first();

                    $unitCost = $batch ? (float) $batch->cost_price : (float) (Product::find($prodId)?->cost_price ?? 0);
                    if ($batch) {
                        $batch->remaining_quantity = max(0, $batch->remaining_quantity - $qty);
                        if ($batch->remaining_quantity <= 0.0001) {
                            $batch->is_exhausted = true;
                        }
                        $batch->save();
                    }

                    $lineCost = $qty * $unitCost;
                    $totalFoodCost += $lineCost;

                    StockMovement::create([
                        'movement_number' => 'MOV-KITCHEN-'.date('YmdHis').'-'.rand(100, 999),
                        'type' => StockMovement::TYPE_OUT,
                        'product_id' => $prodId,
                        'from_warehouse_id' => $siteWarehouse->id,
                        'to_warehouse_id' => null,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'reference_type' => 'SiteKitchenIssue',
                        'reference_id' => $issue->id,
                        'user_id' => $user->id,
                        'notes' => "صرف مواد خام لمطبخ إعداد الوجبات - إذن {$issueNumber}",
                    ]);
                }
            }

            // Balanced Accounting Entry: Debit Food COGS (501000), Credit Site Inventory (101500)
            if ($totalFoodCost > 0) {
                $cogsAcc = ChartOfAccount::where('code', '501000')->first();
                $siteInvAcc = ChartOfAccount::where('code', '101500')->first();

                if ($cogsAcc && $siteInvAcc) {
                    $entry = JournalEntry::create([
                        'entry_number' => 'JV-KITCHEN-'.date('Ymd').'-'.rand(1000, 9999),
                        'entry_date' => $validated['issue_date'],
                        'description' => "إثبات تكلفة خامات الأغذية المنصرفة لمطبخ {$hospital->name} - إذن {$issueNumber}",
                        'source_type' => 'kitchen_issue',
                        'source_id' => null,
                        'created_by' => $user->id,
                        'is_posted' => true,
                    ]);

                    // Debit Food Cost (with Hospital Cost Center)
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'chart_of_account_id' => $cogsAcc->id,
                        'cost_center_id' => $hospital->costCenter?->id,
                        'account_type' => 'expense',
                        'account_name' => $cogsAcc->name,
                        'debit' => $totalFoodCost,
                        'credit' => 0.00,
                        'notes' => "تكلفة خامات وجبات مطبخ {$hospital->name}",
                    ]);

                    // Credit Site Inventory
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'chart_of_account_id' => $siteInvAcc->id,
                        'account_type' => 'inventory',
                        'account_name' => $siteInvAcc->name,
                        'debit' => 0.00,
                        'credit' => $totalFoodCost,
                        'notes' => "صرف من مخزن الموقع {$siteWarehouse?->name}",
                    ]);
                }
            }
        });

        return redirect()->route('site.kitchen-issues.index', [
            'hospital_id' => $hospitalId,
            'date' => $validated['issue_date'],
        ])->with('success', 'تم تسجيل إذن صرف الخامات من المخزن الفرعي للمطبخ بنجاح.');
    }
}
