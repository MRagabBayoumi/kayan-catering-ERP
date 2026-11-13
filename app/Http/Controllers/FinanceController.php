<?php

namespace App\Http\Controllers;

use App\Models\DailyCashClosing;
use App\Models\FinancialVoucher;
use App\Models\Hospital;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    /**
     * 1. Suppliers Accounts & Payment Schedule.
     */
    public function suppliers(): View
    {
        Gate::authorize('manage-settlements');

        $suppliers = Supplier::with('paymentSchedules')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('finance.suppliers.index', compact('suppliers'));
    }

    /**
     * 2. Treasuries, Banks & Site Petty Cash.
     */
    public function treasury(): View
    {
        Gate::authorize('manage-settlements');

        $treasuries = Treasury::with('hospital')->get();
        $vouchers = FinancialVoucher::with(['treasury', 'supplier', 'hospital', 'creator'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        $suppliers = Supplier::all();
        $hospitals = Hospital::where('is_active', true)->get();

        return view('finance.treasury.index', compact('treasuries', 'vouchers', 'suppliers', 'hospitals'));
    }

    /**
     * Store Financial Voucher.
     */
    public function storeVoucher(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'type' => 'required|in:payment_voucher,receipt_voucher,bank_transfer',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|max:255',
            'payment_method' => 'required|string',
            'voucher_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->financeService->createVoucher($validated);

        return redirect()->route('finance.treasury.index')->with('success', 'تم تسجيل السند المالي وتحديث رصيد الخزينة/العهدة بنجاح.');
    }

    /**
     * 3. Daily Cash Closing for Owner.
     */
    public function closing(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $date = $request->input('date', Carbon::today()->toDateString());
        $closing = DailyCashClosing::whereDate('closing_date', $date)->first();
        $vouchers = FinancialVoucher::whereDate('voucher_date', $date)->with(['treasury', 'supplier', 'hospital'])->get();
        $treasuries = Treasury::all();

        return view('finance.closing.index', compact('date', 'closing', 'vouchers', 'treasuries'));
    }

    /**
     * Process Daily Cash Closing.
     */
    public function storeClosing(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $date = $request->input('date', Carbon::today()->toDateString());
        $this->financeService->generateDailyClosing($date, auth()->id());

        return redirect()->route('finance.closing.index', ['date' => $date])->with('success', 'تم تقفيل اليومية المالية بنجاح.');
    }
}
