<?php

namespace Tests\Feature;

use App\Models\DailyCashClosing;
use App\Models\DisbursementOrder;
use App\Models\FinancialVoucher;
use App\Models\Hospital;
use App\Models\JournalEntry;
use App\Models\ReceiptOrder;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceManagementComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $accountant;

    protected Treasury $centralCashbox;

    protected Treasury $bankAccount;

    protected Supplier $supplier;

    protected Hospital $hospital;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->accountant = User::factory()->create([
            'role' => User::ROLE_ACCOUNTANT,
        ]);

        $this->centralCashbox = Treasury::create([
            'name' => 'الخزينة المركزية',
            'type' => 'cashbox',
            'balance' => 50000.00,
        ]);

        $this->bankAccount = Treasury::create([
            'name' => 'البنك الأهلي المصري',
            'type' => 'bank',
            'balance' => 150000.00,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST-01',
            'name' => 'مورد اللحوم الطازجة',
            'phone' => '01000000000',
            'current_balance' => 20000.00,
            'credit_limit' => 100000.00,
            'payment_terms_days' => 15,
        ]);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الدقي التخصصي',
            'code' => 'HOSP-TEST-01',
            'contract_status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Test Requirement 1 & 2: Real-time disbursement order creation by Manager and execution by Treasurer.
     */
    public function test_disbursement_order_lifecycle_from_manager_to_treasurer(): void
    {
        // 1. Manager issues disbursement order
        $response = $this->actingAs($this->admin)->post(route('finance.disbursements.store'), [
            'order_date' => Carbon::today()->toDateString(),
            'amount' => 5000.00,
            'beneficiary_type' => 'supplier',
            'supplier_id' => $this->supplier->id,
            'purpose' => 'سداد دفعة توريد لحوم أسبوعية',
            'notes' => 'صرف شيك أو نقدي بمعرفة أمين الخزنة',
        ]);

        $response->assertRedirect(route('finance.disbursements.index'));
        $order = DisbursementOrder::first();
        $this->assertNotNull($order);
        $this->assertEquals(DisbursementOrder::STATUS_PENDING, $order->status);
        $this->assertEquals(5000.00, (float) $order->amount);

        // Verify it appears on treasurer's screen
        $screenResponse = $this->actingAs($this->accountant)->get(route('finance.disbursements.index'));
        $screenResponse->assertOk();
        $screenResponse->assertSee($order->order_number);
        $screenResponse->assertSee('بانتظار الصرف');

        // 2. Treasurer opens execution view
        $execView = $this->actingAs($this->accountant)->get(route('finance.disbursements.execute.view', $order->id));
        $execView->assertOk();
        $execView->assertSee('الخزينة المركزية');
        $execView->assertSee('البنك الأهلي المصري');

        // 3. Treasurer executes disbursement from Central Cashbox
        $execResponse = $this->actingAs($this->accountant)->post(route('finance.disbursements.execute', $order->id), [
            'treasury_id' => $this->centralCashbox->id,
            'disbursement_date' => Carbon::today()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'تم استلام المبلغ نقداً بواسطة مندوب المورد إبراهيم',
        ]);

        $execResponse->assertRedirect(route('finance.disbursements.index'));

        // Assert balance deducted
        $this->assertEquals(45000.00, (float) $this->centralCashbox->fresh()->balance);

        // Assert supplier balance reduced
        $this->assertEquals(15000.00, (float) $this->supplier->fresh()->current_balance);

        // Assert status updated to completed
        $order->refresh();
        $this->assertEquals(DisbursementOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->financial_voucher_id);
        $this->assertEquals($this->accountant->id, $order->executed_by);

        // Assert automatic balanced journal entry created
        $journalEntry = JournalEntry::where('source_type', JournalEntry::SOURCE_DISBURSEMENT)->first();
        $this->assertNotNull($journalEntry);
        $this->assertTrue($journalEntry->isBalanced());
        $this->assertEquals(5000.00, $journalEntry->total_debit);
        $this->assertEquals(5000.00, $journalEntry->total_credit);
    }

    /**
     * Test Requirement 3: Daily cash closing with actual count, deficit calculation, and manager approval/rejection.
     */
    public function test_daily_closing_deficit_and_approval_workflow(): void
    {
        $today = Carbon::today()->toDateString();

        // Submit daily closing with deficit (Actual count 48000 vs theoretical balance 200000)
        $response = $this->actingAs($this->accountant)->post(route('finance.closing.store'), [
            'date' => $today,
            'actual_cash_count' => 195000.00,
            'notes' => 'يوجد عجز نقدي بقيمة 5000 ج.م بانتظار مراجعة الإدارة',
        ]);

        $response->assertRedirect(route('finance.closing.index', ['date' => $today]));

        $closing = DailyCashClosing::whereDate('closing_date', $today)->first();
        $this->assertNotNull($closing);
        $this->assertEquals(DailyCashClosing::STATUS_PENDING_APPROVAL, $closing->status);
        $this->assertTrue($closing->hasDeficit());
        $this->assertEquals(-5000.00, (float) $closing->difference);

        // Manager reviews and approves closing
        $reviewResponse = $this->actingAs($this->admin)->post(route('finance.closing.review', $closing->id), [
            'action' => 'approve',
        ]);

        $reviewResponse->assertRedirect(route('finance.closing.index', ['date' => $today]));
        $closing->refresh();
        $this->assertEquals(DailyCashClosing::STATUS_APPROVED, $closing->status);
        $this->assertEquals($this->admin->id, $closing->approved_by);

        // Assert adjusting journal entry created for deficit
        $deficitEntry = JournalEntry::where('source_type', JournalEntry::SOURCE_CLOSING)->first();
        $this->assertNotNull($deficitEntry);
        $this->assertEquals(5000.00, $deficitEntry->total_debit);
        $this->assertEquals(5000.00, $deficitEntry->total_credit);
    }

    /**
     * Test Requirement 8: Receipt orders approval & rejection with reason and balance settlement.
     */
    public function test_receipt_order_approval_and_rejection(): void
    {
        // 1. Submit receipt order from hospital collection
        $postResponse = $this->actingAs($this->accountant)->post(route('finance.receipts.store'), [
            'order_date' => Carbon::today()->toDateString(),
            'amount' => 25000.00,
            'payer_type' => 'hospital',
            'hospital_id' => $this->hospital->id,
            'treasury_id' => $this->bankAccount->id,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TRX-987654',
            'purpose' => 'تحصيل شيك مستخلص توريد وجبات للمستشفى',
            'notes' => 'تم التحويل لحساب الشركة بالبنك الأهلي',
        ]);

        $postResponse->assertRedirect(route('finance.receipts.index'));
        $receipt = ReceiptOrder::first();
        $this->assertNotNull($receipt);
        $this->assertEquals(ReceiptOrder::STATUS_PENDING, $receipt->status);

        // 2. Manager approves receipt
        $approveResponse = $this->actingAs($this->admin)->post(route('finance.receipts.review', $receipt->id), [
            'action' => 'approve',
        ]);

        $approveResponse->assertRedirect(route('finance.receipts.index'));
        $receipt->refresh();
        $this->assertEquals(ReceiptOrder::STATUS_APPROVED, $receipt->status);

        // Bank balance increased by 25,000
        $this->assertEquals(175000.00, (float) $this->bankAccount->fresh()->balance);

        // Financial voucher and journal entry created
        $voucher = FinancialVoucher::where('type', FinancialVoucher::TYPE_RECEIPT)->first();
        $this->assertNotNull($voucher);
        $this->assertEquals(25000.00, (float) $voucher->amount);

        // 3. Test Rejection flow for another receipt
        $rcpt2 = ReceiptOrder::create([
            'order_number' => 'RCPT-TEST-02',
            'order_date' => Carbon::today(),
            'amount' => 10000.00,
            'payer_type' => 'other',
            'payer_name' => 'جهة مجهولة',
            'treasury_id' => $this->centralCashbox->id,
            'payment_method' => 'cash',
            'purpose' => 'مبلغ غير مدقق',
            'status' => ReceiptOrder::STATUS_PENDING,
            'created_by' => $this->accountant->id,
        ]);

        $rejectResponse = $this->actingAs($this->admin)->post(route('finance.receipts.review', $rcpt2->id), [
            'action' => 'reject',
            'rejection_reason' => 'عدم وجود مستند توريد رسمي مختوم',
        ]);

        $rejectResponse->assertRedirect(route('finance.receipts.index'));
        $rcpt2->refresh();
        $this->assertEquals(ReceiptOrder::STATUS_REJECTED, $rcpt2->status);
        $this->assertEquals('عدم وجود مستند توريد رسمي مختوم', $rcpt2->rejection_reason);
    }

    /**
     * Test Requirement 4, 5, 6 & 7: General Ledger, Settlements, and Monthly Itemized Breakdown.
     */
    public function test_general_ledger_settlements_and_monthly_breakdown(): void
    {
        // Create transactions
        $this->actingAs($this->admin)->post(route('finance.disbursements.store'), [
            'order_date' => Carbon::today()->toDateString(),
            'amount' => 4000.00,
            'beneficiary_type' => 'supplier',
            'supplier_id' => $this->supplier->id,
            'purpose' => 'سداد دفعة توريد خضروات',
        ]);
        $order = DisbursementOrder::first();
        $this->actingAs($this->accountant)->post(route('finance.disbursements.execute', $order->id), [
            'treasury_id' => $this->centralCashbox->id,
            'disbursement_date' => Carbon::today()->toDateString(),
            'payment_method' => 'cash',
        ]);

        // 1. General Ledger View
        $ledgerResponse = $this->actingAs($this->accountant)->get(route('finance.ledger.index', [
            'account_type' => 'supplier',
        ]));
        $ledgerResponse->assertOk();
        $ledgerResponse->assertSee($this->supplier->name);

        // 2. Settlements View
        $settlementsResponse = $this->actingAs($this->accountant)->get(route('finance.settlements.index'));
        $settlementsResponse->assertOk();
        $settlementsResponse->assertSee('ترصيد وتسوية حسابات الموردين وعهد المواقع');

        // 3. Monthly Breakdown View
        $breakdownResponse = $this->actingAs($this->accountant)->get(route('finance.breakdown.index', [
            'year' => Carbon::now()->year,
            'month' => Carbon::now()->month,
        ]));
        $breakdownResponse->assertOk();
        $breakdownResponse->assertSee($this->supplier->name);
        $breakdownResponse->assertSee('4,000.00');
    }
}
