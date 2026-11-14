<?php

namespace App\Http\Controllers;

use App\Models\DailyCashClosing;
use App\Models\DisbursementOrder;
use App\Models\FinancialVoucher;
use App\Models\Hospital;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ReceiptOrder;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Models\User;
use App\Services\FinanceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    /**
     * 1. Real-time Disbursement Orders Screen (شاشة أذونات الصرف اللحظية).
     */
    public function disbursements(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $status = $request->query('status', 'all');
        $query = DisbursementOrder::with(['supplier', 'hospital', 'user', 'creator', 'executor', 'financialVoucher'])
            ->orderBy('id', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20);

        $pendingCount = DisbursementOrder::where('status', DisbursementOrder::STATUS_PENDING)->count();
        $completedToday = DisbursementOrder::where('status', DisbursementOrder::STATUS_COMPLETED)
            ->whereDate('executed_at', Carbon::today())
            ->count();
        $disbursedTodaySum = (float) DisbursementOrder::where('status', DisbursementOrder::STATUS_COMPLETED)
            ->whereDate('executed_at', Carbon::today())
            ->sum('amount');

        $suppliers = Supplier::all();
        $hospitals = Hospital::where('is_active', true)->get();
        $users = User::all();

        return view('finance.disbursements.index', compact(
            'orders',
            'status',
            'pendingCount',
            'completedToday',
            'disbursedTodaySum',
            'suppliers',
            'hospitals',
            'users'
        ));
    }

    /**
     * Store new Disbursement Order by Manager.
     */
    public function storeDisbursement(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $validated = $request->validate([
            'order_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'beneficiary_type' => 'required|in:supplier,hospital,user,other',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'user_id' => 'nullable|exists:users,id',
            'beneficiary_name' => 'nullable|string|max:255',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->financeService->createDisbursementOrder($validated, auth()->id());

        return redirect()->route('finance.disbursements.index')
            ->with('success', 'تم إصدار إذن الصرف بنجاح وإرساله فورياً إلى شاشة أمين الخزنة.');
    }

    /**
     * 2. Treasurer Disbursement Execution Screen (صفحة تنفيذ الصرف واختيار الخزينة/البنك).
     */
    public function executeDisbursementView(int $id): View
    {
        Gate::authorize('manage-settlements');

        $order = DisbursementOrder::with(['supplier', 'hospital', 'user', 'creator'])->findOrFail($id);

        if (! $order->isPending()) {
            return redirect()->route('finance.disbursements.index')
                ->with('error', 'تم تنفيذ أو إغلاق إذن الصرف هذا مسبقاً.');
        }

        $treasuries = Treasury::all();

        return view('finance.disbursements.execute', compact('order', 'treasuries'));
    }

    /**
     * Execute Disbursement by Treasurer.
     */
    public function executeDisbursement(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'disbursement_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,check',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->financeService->executeDisbursement($id, $validated, auth()->id());

            return redirect()->route('finance.disbursements.index')
                ->with('success', 'تم صرف إذن الصرف بنجاح، وخصم المبلغ من الحساب، وتوليد القيد المحاسبي وسند الصرف تلقائياً.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 3. Receipts & Inflows Screen (الوارد وترصيده واشعار المدير).
     */
    public function receipts(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $status = $request->query('status', 'all');
        $query = ReceiptOrder::with(['hospital', 'supplier', 'user', 'treasury', 'creator', 'approver', 'financialVoucher'])
            ->orderBy('id', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $receipts = $query->paginate(20);

        $pendingCount = ReceiptOrder::where('status', ReceiptOrder::STATUS_PENDING)->count();
        $approvedTodaySum = (float) ReceiptOrder::where('status', ReceiptOrder::STATUS_APPROVED)
            ->whereDate('approved_at', Carbon::today())
            ->sum('amount');

        $treasuries = Treasury::all();
        $hospitals = Hospital::where('is_active', true)->get();
        $suppliers = Supplier::all();
        $users = User::all();

        return view('finance.receipts.index', compact(
            'receipts',
            'status',
            'pendingCount',
            'approvedTodaySum',
            'treasuries',
            'hospitals',
            'suppliers',
            'users'
        ));
    }

    /**
     * Store new Receipt Order.
     */
    public function storeReceipt(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $validated = $request->validate([
            'order_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payer_type' => 'required|in:hospital,supplier,user,other',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'user_id' => 'nullable|exists:users,id',
            'payer_name' => 'nullable|string|max:255',
            'treasury_id' => 'required|exists:treasuries,id',
            'payment_method' => 'required|string|in:cash,bank_transfer,check',
            'reference_number' => 'nullable|string|max:255',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->financeService->createReceiptOrder($validated, auth()->id());

        return redirect()->route('finance.receipts.index')
            ->with('success', 'تم تسجيل إذن الوارد وإرسال إشعار فوري للمدير للمراجعة والاعتماد.');
    }

    /**
     * Review Receipt Order (موافقة أو رفض الوارد من المدير مع ذكر السبب).
     */
    public function reviewReceipt(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('manage-closing');

        $action = $request->input('action');
        $reason = $request->input('rejection_reason');

        if (! in_array($action, ['approve', 'reject'])) {
            return back()->with('error', 'إجراء غير صحيح.');
        }

        try {
            $this->financeService->reviewReceiptOrder($id, $action, $reason, auth()->id());

            $msg = $action === 'approve'
                ? 'تمت الموافقة على الوارد وترصيد المبلغ في الخزينة/البنك وإنشاء القيد المحاسبي بنجاح.'
                : 'تم رفض الوارد وتسجيل سبب الرفض وإشعار مقدم الطلب.';

            return redirect()->route('finance.receipts.index')->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 4. Daily Cash Closing & Print Screen (صفحة تقفيل اليوم + الطباعة وإشعار المدير والعجز).
     */
    public function closing(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $date = $request->input('date', Carbon::today()->toDateString());
        $closing = DailyCashClosing::whereDate('closing_date', $date)->with(['closedByUser', 'approvedByUser'])->first();
        $vouchers = FinancialVoucher::whereDate('voucher_date', $date)->with(['treasury', 'supplier', 'hospital', 'creator'])->get();
        $treasuries = Treasury::all();

        // Theoretical closing balance
        $currentTreasuryBalance = (float) Treasury::sum('balance');
        $totalInflow = (float) $vouchers->where('type', FinancialVoucher::TYPE_RECEIPT)->sum('amount');
        $totalOutflow = (float) $vouchers->where('type', FinancialVoucher::TYPE_PAYMENT)->sum('amount');
        $expectedBalance = $closing ? (float) $closing->closing_balance : $currentTreasuryBalance;

        return view('finance.closing.index', compact('date', 'closing', 'vouchers', 'treasuries', 'expectedBalance', 'totalInflow', 'totalOutflow'));
    }

    /**
     * Store / Submit Daily Closing with Actual Cash Count.
     */
    public function storeClosing(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $request->validate([
            'date' => 'required|date',
            'actual_cash_count' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $date = $request->input('date');
        $actualCashCount = (float) $request->input('actual_cash_count');
        $notes = $request->input('notes');

        $closing = $this->financeService->submitDailyClosing($date, $actualCashCount, $notes, auth()->id());

        $statusMsg = $closing->hasDeficit()
            ? 'تم تقديم التقفيل مع إثبات عجز قدره '.number_format(abs($closing->difference), 2).' ج.م، وتم إشعار المدير للمراجعة والاعتماد.'
            : ($closing->hasSurplus()
                ? 'تم تقديم التقفيل مع إثبات فائض قدره '.number_format($closing->difference, 2).' ج.م، وتم إشعار المدير للمراجعة والاعتماد.'
                : 'تم تقديم التقفيل بمطابقة تامة مع الدفاتر وتم إشعار المدير للاعتماد.');

        return redirect()->route('finance.closing.index', ['date' => $date])->with('success', $statusMsg);
    }

    /**
     * Review Daily Closing (موافقة أو رفض الإقفال من المدير مع ذكر السبب).
     */
    public function reviewClosing(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('manage-closing');

        $action = $request->input('action');
        $reason = $request->input('rejection_reason');

        if (! in_array($action, ['approve', 'reject'])) {
            return back()->with('error', 'إجراء غير صحيح.');
        }

        try {
            $closing = $this->financeService->reviewDailyClosing($id, $action, $reason, auth()->id());

            $msg = $action === 'approve'
                ? 'تم اعتماد إقفال اليومية رسمياً من المدير وترحيل فروقات العجز إن وجدت إلى القيود اليومية.'
                : 'تم رفض إقفال اليومية وتسجيل أسباب الرفض وإشعار أمين الخزينة.';

            return redirect()->route('finance.closing.index', ['date' => $closing->closing_date->toDateString()])->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 5. General Ledger Screen (دفتر الأستاذ العام لكل الحسابات).
     */
    public function ledger(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $accountType = $request->query('account_type');
        $accountId = $request->query('account_id') ? (int) $request->query('account_id') : null;
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $ledgerData = $this->financeService->getGeneralLedger($accountType, $accountId, $startDate, $endDate);

        $treasuries = Treasury::all();
        $suppliers = Supplier::all();
        $hospitals = Hospital::where('is_active', true)->get();

        return view('finance.ledger.index', compact('ledgerData', 'treasuries', 'suppliers', 'hospitals', 'startDate', 'endDate', 'accountType', 'accountId'));
    }

    /**
     * 6. Automated Journal Entries Screen (سحب قيود اليومية أوتوماتيك).
     */
    public function journalEntries(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $query = JournalEntry::with(['lines', 'creator'])->orderBy('entry_date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('entry_date', [$request->query('start_date'), $request->query('end_date')]);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(20);

        return view('finance.journal.index', compact('entries'));
    }

    /**
     * 7. Custody and Suppliers Settlements (ترصيد كل مورد أو جهة أخذت عهدة).
     */
    public function settlements(): View
    {
        Gate::authorize('manage-settlements');

        $data = $this->financeService->getCustodiesAndSettlements();

        return view('finance.settlements.index', [
            'suppliers' => $data['suppliers'],
            'custodies' => $data['custodies'],
        ]);
    }

    /**
     * 8. Monthly Itemized Breakdown by Entity (تفريدة لكل شخص أو جهة أخذت فلوس طول الشهر أو توريد أي فلوس من أي شخص).
     */
    public function monthlyBreakdown(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $year = (int) $request->query('year', Carbon::now()->year);
        $month = (int) $request->query('month', Carbon::now()->month);
        $entityType = $request->query('entity_type');
        $entityId = $request->query('entity_id') ? (int) $request->query('entity_id') : null;

        $breakdown = $this->financeService->getMonthlyBreakdown($year, $month, $entityType, $entityId);

        $suppliers = Supplier::all();
        $hospitals = Hospital::where('is_active', true)->get();

        return view('finance.breakdown.index', compact('breakdown', 'year', 'month', 'suppliers', 'hospitals', 'entityType', 'entityId'));
    }

    /**
     * Suppliers Accounts & Payment Schedule.
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
     * Treasuries, Banks & Site Petty Cash.
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

        FinancialVoucher::create([
            'voucher_number' => 'VOUCH-'.strtoupper(uniqid()),
            'type' => $validated['type'],
            'treasury_id' => $validated['treasury_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'hospital_id' => $validated['hospital_id'] ?? null,
            'amount' => $validated['amount'],
            'purpose' => $validated['purpose'],
            'payment_method' => $validated['payment_method'],
            'voucher_date' => $validated['voucher_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->financeService->createAutomaticJournalEntry([
            'date' => $validated['voucher_date'],
            'description' => "سند مالي مباشر: {$validated['purpose']}",
            'source_type' => JournalEntry::SOURCE_MANUAL,
            'source_id' => null,
            'created_by' => auth()->id(),
            'lines' => [
                [
                    'account_type' => $validated['type'] === 'payment_voucher' ? JournalEntryLine::ACCOUNT_EXPENSE : JournalEntryLine::ACCOUNT_TREASURY,
                    'account_id' => $validated['treasury_id'],
                    'account_name' => $validated['type'] === 'payment_voucher' ? 'مصروفات وسدادات نقدية' : 'الخزينة النقدية',
                    'debit' => (float) $validated['amount'],
                    'credit' => 0.00,
                    'notes' => $validated['purpose'],
                ],
                [
                    'account_type' => $validated['type'] === 'payment_voucher' ? JournalEntryLine::ACCOUNT_TREASURY : JournalEntryLine::ACCOUNT_REVENUE,
                    'account_id' => $validated['treasury_id'],
                    'account_name' => $validated['type'] === 'payment_voucher' ? 'الخزينة النقدية' : 'إيرادات وتحصيلات',
                    'debit' => 0.00,
                    'credit' => (float) $validated['amount'],
                    'notes' => $validated['purpose'],
                ],
            ],
        ]);

        $this->financeService->createDisbursementOrder([
            'order_date' => $validated['voucher_date'],
            'amount' => $validated['amount'],
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
        ], auth()->id());

        return redirect()->route('finance.treasury.index')->with('success', 'تم تسجيل السند المالي وتحديث الأرصدة بنجاح.');
    }
}
