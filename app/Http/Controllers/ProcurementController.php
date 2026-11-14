<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplyOrder;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    /**
     * Display purchase orders and quotation approvals.
     */
    public function orders(): View
    {
        $orders = SupplyOrder::with(['hospital', 'items.product'])
            ->latest()
            ->paginate(15);

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('procurement.orders.index', [
            'title' => 'طلبات الشراء وعروض الأسعار',
            'moduleName' => 'إدارة المشتريات والتوريدات',
            'orders' => $orders,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    /**
     * Show form for creating a purchase requisition.
     */
    public function createOrder(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('procurement.orders.create', [
            'title' => 'إنشاء طلب شراء احتياج جديد',
            'moduleName' => 'إدارة المشتريات والتوريدات',
            'suppliers' => $suppliers,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store new purchase order.
     */
    public function storeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:50', 'unique:supply_orders,order_number'],
            'hospital_id' => ['nullable', 'exists:hospitals,id'],
            'order_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        SupplyOrder::create([
            'order_number' => $validated['order_number'],
            'hospital_id' => $validated['hospital_id'] ?? Hospital::first()?->id,
            'order_date' => $validated['order_date'],
            'start_date' => $validated['start_date'] ?? $validated['order_date'],
            'end_date' => $validated['end_date'] ?? date('Y-m-d', strtotime('+30 days', strtotime($validated['order_date']))),
            'frequency' => 'daily',
            'status' => $validated['status'],
            'created_by' => auth()->id() ?? 1,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('procurement.orders.index')
            ->with('status', 'success')
            ->with('message', 'تم تسجيل طلب الشراء بنجاح وجارٍ مراجعة عروض الأسعار واعتماد صاحب العمل.');
    }

    /**
     * Suppliers directory and item coding.
     */
    public function suppliers(): View
    {
        $suppliers = Supplier::withCount('paymentSchedules')
            ->orderBy('name')
            ->paginate(15);

        return view('procurement.suppliers.index', [
            'title' => 'دليل وتكويد الموردين المعتمدين',
            'moduleName' => 'إدارة المشتريات والتوريدات',
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function storeSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'current_balance' => ['nullable', 'numeric'],
        ]);

        Supplier::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? 'آجل 30 يوم',
            'current_balance' => $validated['current_balance'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('procurement.suppliers.index')
            ->with('status', 'success')
            ->with('message', 'تم تكويد وإضافة المورد الجديد بنجاح في سجلات المشتريات.');
    }
}
