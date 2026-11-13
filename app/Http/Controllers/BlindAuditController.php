<?php

namespace App\Http\Controllers;

use App\Models\BlindStockAudit;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\CateringLogisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BlindAuditController extends Controller
{
    public function __construct(
        protected CateringLogisticsService $logisticsService
    ) {}

    /**
     * List all blind audits.
     */
    public function index(): View
    {
        Gate::authorize('manage-warehouse');

        $audits = BlindStockAudit::with('warehouse', 'storekeeper', 'accountant')
            ->latest()
            ->paginate(15);

        return view('warehouse.audits.index', [
            'title' => 'جلسات الجرد اليومي الأعمى',
            'audits' => $audits,
        ]);
    }

    /**
     * Blind stock-taking entry form (Storekeeper counts on shelf without seeing book balance).
     */
    public function create(): View
    {
        Gate::authorize('perform-blind-audit');

        $warehouses = Warehouse::where('type', '!=', Warehouse::TYPE_VAN_TRANSIT)
            ->where('is_active', true)
            ->get();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('warehouse.audits.create', [
            'title' => 'تسجيل جلسة جرد أعمى للمخزن',
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    /**
     * Store blind audit items.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('perform-blind-audit');

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.physical_count' => 'required|numeric|min:0',
            'items.*.discrepancy_reason' => 'nullable|string',
        ]);

        $audit = $this->logisticsService->submitBlindAudit(
            $validated['warehouse_id'],
            auth()->id(),
            $validated['items'],
            $validated['notes'] ?? null
        );

        return redirect()->route('warehouse.audits.show', $audit->id)
            ->with('success', "تم بنجاح تسجيل محضر الجرد الأعمى رقم [{$audit->audit_number}] وإرساله للمحاسب للمطابقة.");
    }

    /**
     * Accountant Review & Discrepancy Reconciliation Screen.
     */
    public function show(int $id): View
    {
        Gate::authorize('manage-warehouse');

        $audit = BlindStockAudit::with('warehouse', 'storekeeper', 'accountant', 'items.product')
            ->findOrFail($id);

        return view('warehouse.audits.show', [
            'title' => 'محضر مطابقة الجرد رقم '.$audit->audit_number,
            'audit' => $audit,
        ]);
    }
}
