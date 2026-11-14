<?php

namespace App\Services;

use App\Models\DailyCashClosing;
use App\Models\DisbursementOrder;
use App\Models\FinancialNotification;
use App\Models\FinancialVoucher;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ReceiptOrder;
use App\Models\Supplier;
use App\Models\Treasury;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinanceService
{
    /**
     * 1. Create a Disbursement Order (إذن صرف من الإدارة إلى أمين الخزينة).
     */
    public function createDisbursementOrder(array $data, int $userId): DisbursementOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $count = DisbursementOrder::whereDate('created_at', Carbon::today())->count() + 1;
            $orderNumber = 'DISB-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $order = DisbursementOrder::create([
                'order_number' => $orderNumber,
                'order_date' => $data['order_date'] ?? Carbon::today(),
                'amount' => (float) $data['amount'],
                'beneficiary_type' => $data['beneficiary_type'] ?? 'other',
                'supplier_id' => $data['supplier_id'] ?? null,
                'hospital_id' => $data['hospital_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'beneficiary_name' => $data['beneficiary_name'] ?? null,
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => DisbursementOrder::STATUS_PENDING,
                'created_by' => $userId,
            ]);

            // Notify Accountants / Treasurers
            FinancialNotification::create([
                'title' => 'إذن صرف جديد من الإدارة',
                'message' => "تم إصدار إذن صرف رقم {$order->order_number} بمبلغ ".number_format($order->amount, 2)." ج.م لصالح {$order->beneficiary_label}.",
                'type' => 'disbursement_pending',
                'url' => route('finance.disbursements.index'),
            ]);

            return $order;
        });
    }

    /**
     * 2. Execute Disbursement Order by Treasurer (تنفيذ إذن الصرف وخصمه من الخزينة أو البنك).
     */
    public function executeDisbursement(int $orderId, array $data, int $executedBy): DisbursementOrder
    {
        return DB::transaction(function () use ($orderId, $data, $executedBy) {
            $order = DisbursementOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== DisbursementOrder::STATUS_PENDING) {
                throw new InvalidArgumentException('تم تنفيذ أو إلغاء إذن الصرف هذا مسبقاً.');
            }

            $treasury = Treasury::lockForUpdate()->findOrFail($data['treasury_id']);
            $amount = (float) $order->amount;

            if ($treasury->balance < $amount) {
                throw new InvalidArgumentException("رصيد {$treasury->name} الحالي (".number_format($treasury->balance, 2).' ج.م) غير كافٍ لصرف مبلغ '.number_format($amount, 2).' ج.م.');
            }

            // Deduct balance from Treasury / Bank
            $treasury->balance -= $amount;
            $treasury->save();

            // If paying a supplier, reduce supplier's balance
            if ($order->supplier_id) {
                $supplier = Supplier::find($order->supplier_id);
                if ($supplier) {
                    $supplier->current_balance -= $amount;
                    $supplier->save();
                }
            }

            // Create Financial Voucher
            $vchCount = FinancialVoucher::whereDate('created_at', Carbon::today())->count() + 1;
            $voucherNumber = 'VCH-'.date('Ymd').'-'.str_pad((string) $vchCount, 4, '0', STR_PAD_LEFT);

            $voucher = FinancialVoucher::create([
                'voucher_number' => $voucherNumber,
                'type' => FinancialVoucher::TYPE_PAYMENT,
                'treasury_id' => $treasury->id,
                'supplier_id' => $order->supplier_id,
                'hospital_id' => $order->hospital_id,
                'amount' => $amount,
                'purpose' => $order->purpose,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'created_by' => $executedBy,
                'voucher_date' => $data['disbursement_date'] ?? Carbon::today(),
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            // Create Automatic Balanced Journal Entry
            $this->createAutomaticJournalEntry([
                'date' => $voucher->voucher_date,
                'description' => "صرف إذن رقم {$order->order_number} - سند صرف {$voucher->voucher_number} - {$order->purpose}",
                'source_type' => JournalEntry::SOURCE_DISBURSEMENT,
                'source_id' => $order->id,
                'created_by' => $executedBy,
                'lines' => [
                    [
                        'account_type' => $this->resolveBeneficiaryAccountType($order),
                        'account_id' => $order->supplier_id ?? ($order->hospital_id ?? $order->user_id),
                        'account_name' => $order->beneficiary_label,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'notes' => $order->purpose,
                    ],
                    [
                        'account_type' => $treasury->type === 'bank' ? JournalEntryLine::ACCOUNT_BANK : JournalEntryLine::ACCOUNT_TREASURY,
                        'account_id' => $treasury->id,
                        'account_name' => $treasury->name,
                        'debit' => 0.00,
                        'credit' => $amount,
                        'notes' => "خصم من {$treasury->name} (".($data['payment_method'] ?? 'نقدي').')',
                    ],
                ],
            ]);

            // Update Disbursement Order
            $order->update([
                'status' => DisbursementOrder::STATUS_COMPLETED,
                'financial_voucher_id' => $voucher->id,
                'executed_by' => $executedBy,
                'executed_at' => Carbon::now(),
                'execution_notes' => $data['notes'] ?? null,
            ]);

            return $order;
        });
    }

    /**
     * 3. Create Receipt Order (تسجيل وارد بانتظار اعتماد المدير).
     */
    public function createReceiptOrder(array $data, int $userId): ReceiptOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $count = ReceiptOrder::whereDate('created_at', Carbon::today())->count() + 1;
            $orderNumber = 'RCPT-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $receipt = ReceiptOrder::create([
                'order_number' => $orderNumber,
                'order_date' => $data['order_date'] ?? Carbon::today(),
                'amount' => (float) $data['amount'],
                'payer_type' => $data['payer_type'] ?? 'other',
                'hospital_id' => $data['hospital_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'treasury_id' => $data['treasury_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => ReceiptOrder::STATUS_PENDING,
                'created_by' => $userId,
            ]);

            // Notify Managers
            FinancialNotification::create([
                'title' => 'إشعار وارد جديد ينتظر الموافقة',
                'message' => "تم تسجيل وارد جديد رقم {$receipt->order_number} بمبلغ ".number_format($receipt->amount, 2)." ج.م من {$receipt->payer_label}، بانتظار موافقة الإدارة.",
                'type' => 'receipt_pending',
                'url' => route('finance.receipts.index'),
            ]);

            return $receipt;
        });
    }

    /**
     * 4. Review Receipt Order (موافقة أو رفض الوارد من المدير مع ذكر السبب).
     */
    public function reviewReceiptOrder(int $orderId, string $action, ?string $rejectionReason, int $reviewerId): ReceiptOrder
    {
        return DB::transaction(function () use ($orderId, $action, $rejectionReason, $reviewerId) {
            $order = ReceiptOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== ReceiptOrder::STATUS_PENDING) {
                throw new InvalidArgumentException('تم البت في هذا الوارد مسبقاً.');
            }

            if ($action === 'reject') {
                if (empty(trim((string) $rejectionReason))) {
                    throw new InvalidArgumentException('يجب ذكر سبب رفض الوارد بشكل واضح.');
                }

                $order->update([
                    'status' => ReceiptOrder::STATUS_REJECTED,
                    'rejection_reason' => $rejectionReason,
                    'approved_by' => $reviewerId,
                    'approved_at' => Carbon::now(),
                ]);

                FinancialNotification::create([
                    'user_id' => $order->created_by,
                    'title' => 'تم رفض إذن الوارد',
                    'message' => "تم رفض إذن الوارد رقم {$order->order_number} بواسطة الإدارة. السبب: {$rejectionReason}",
                    'type' => 'receipt_rejected',
                    'url' => route('finance.receipts.index'),
                ]);

                return $order;
            }

            // Approval Workflow
            $treasuryId = $order->treasury_id;
            if (! $treasuryId) {
                $treasury = Treasury::first();
                $treasuryId = $treasury->id;
            } else {
                $treasury = Treasury::lockForUpdate()->findOrFail($treasuryId);
            }

            $amount = (float) $order->amount;

            // Increase Treasury / Bank Balance
            $treasury->balance += $amount;
            $treasury->save();

            // Create Financial Voucher
            $vchCount = FinancialVoucher::whereDate('created_at', Carbon::today())->count() + 1;
            $voucherNumber = 'VCH-'.date('Ymd').'-'.str_pad((string) $vchCount, 4, '0', STR_PAD_LEFT);

            $voucher = FinancialVoucher::create([
                'voucher_number' => $voucherNumber,
                'type' => FinancialVoucher::TYPE_RECEIPT,
                'treasury_id' => $treasury->id,
                'supplier_id' => $order->supplier_id,
                'hospital_id' => $order->hospital_id,
                'amount' => $amount,
                'purpose' => $order->purpose,
                'payment_method' => $order->payment_method,
                'created_by' => $reviewerId,
                'voucher_date' => $order->order_date,
                'notes' => $order->notes,
            ]);

            // Automatic Balanced Journal Entry
            $this->createAutomaticJournalEntry([
                'date' => $voucher->voucher_date,
                'description' => "قبض وتوريد إذن رقم {$order->order_number} - سند قبض {$voucher->voucher_number} - {$order->purpose}",
                'source_type' => JournalEntry::SOURCE_RECEIPT,
                'source_id' => $order->id,
                'created_by' => $reviewerId,
                'lines' => [
                    [
                        'account_type' => $treasury->type === 'bank' ? JournalEntryLine::ACCOUNT_BANK : JournalEntryLine::ACCOUNT_TREASURY,
                        'account_id' => $treasury->id,
                        'account_name' => $treasury->name,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'notes' => "إيداع في {$treasury->name}",
                    ],
                    [
                        'account_type' => $this->resolvePayerAccountType($order),
                        'account_id' => $order->hospital_id ?? ($order->supplier_id ?? $order->user_id),
                        'account_name' => $order->payer_label,
                        'debit' => 0.00,
                        'credit' => $amount,
                        'notes' => $order->purpose,
                    ],
                ],
            ]);

            $order->update([
                'status' => ReceiptOrder::STATUS_APPROVED,
                'approved_by' => $reviewerId,
                'approved_at' => Carbon::now(),
                'financial_voucher_id' => $voucher->id,
            ]);

            FinancialNotification::create([
                'user_id' => $order->created_by,
                'title' => 'تمت الموافقة على الوارد وترصيده',
                'message' => "تم اعتماد الوارد رقم {$order->order_number} بمبلغ ".number_format($amount, 2)." ج.م وترصيده في {$treasury->name}.",
                'type' => 'receipt_approved',
                'url' => route('finance.receipts.index'),
            ]);

            return $order;
        });
    }

    /**
     * 5. Submit Daily Closing by Treasurer/Accountant (تقديم إقفال اليومية مع الجرد الفعلي والعجز).
     */
    public function submitDailyClosing(string $date, float $actualCashCount, ?string $notes, int $closedBy): DailyCashClosing
    {
        return DB::transaction(function () use ($date, $actualCashCount, $notes, $closedBy) {
            $dayVouchers = FinancialVoucher::whereDate('voucher_date', $date)->get();

            $totalInflow = (float) $dayVouchers->where('type', FinancialVoucher::TYPE_RECEIPT)->sum('amount');
            $totalOutflow = (float) $dayVouchers->where('type', FinancialVoucher::TYPE_PAYMENT)->sum('amount');

            $siteSpent = (float) $dayVouchers->filter(function ($v) {
                return $v->type === FinancialVoucher::TYPE_PAYMENT && ! empty($v->hospital_id);
            })->sum('amount');

            $supplierPayments = (float) $dayVouchers->filter(function ($v) {
                return $v->type === FinancialVoucher::TYPE_PAYMENT && ! empty($v->supplier_id);
            })->sum('amount');

            $currentTreasuryBalance = (float) Treasury::sum('balance');
            $openingBalance = $currentTreasuryBalance - $totalInflow + $totalOutflow;
            $expectedClosingBalance = $currentTreasuryBalance;

            // Difference: actual cash count minus theoretical/expected closing balance
            // Negative = Deficit (عجز), Positive = Surplus (فائض)
            $difference = $actualCashCount - $expectedClosingBalance;

            $dateFormatted = Carbon::parse($date)->toDateString();
            $closing = DailyCashClosing::whereDate('closing_date', $dateFormatted)->first();

            $attributes = [
                'opening_balance' => max(0, $openingBalance),
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'closing_balance' => $expectedClosingBalance,
                'actual_cash_count' => $actualCashCount,
                'difference' => $difference,
                'site_custody_spent' => $siteSpent,
                'supplier_payments' => $supplierPayments,
                'status' => DailyCashClosing::STATUS_PENDING_APPROVAL,
                'notes' => $notes ?: 'تم تقديم محضر التقفيل اليومي ومطابقة الخزائن بانتظار اعتماد الإدارة',
                'closed_by' => $closedBy,
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ];

            if ($closing) {
                $closing->update($attributes);
            } else {
                $attributes['closing_date'] = $dateFormatted;
                $closing = DailyCashClosing::create($attributes);
            }

            // Notify Admin / System Manager
            $diffText = $difference < 0
                ? 'عجز مالي قدره '.number_format(abs($difference), 2).' ج.م'
                : ($difference > 0 ? 'فائض مالي قدره '.number_format($difference, 2).' ج.م' : 'مطابقة تامة');

            FinancialNotification::create([
                'title' => "تقفيل يومية {$dateFormatted} بانتظار الاعتماد",
                'message' => "تم تقديم إقفال اليومية ليوم {$dateFormatted} بواسطة ".auth()->user()?->name.' برصيد دفتري '.number_format($expectedClosingBalance, 2).' ج.م، وعد فعلي '.number_format($actualCashCount, 2)." ج.م ({$diffText}).",
                'type' => 'closing_pending',
                'url' => route('finance.closing.index', ['date' => $dateFormatted]),
            ]);

            return $closing;
        });
    }

    /**
     * 6. Review Daily Closing by Admin (موافقة أو رفض التقفيل مع ذكر السبب ومعرفة العجز).
     */
    public function reviewDailyClosing(int $closingId, string $action, ?string $rejectionReason, int $reviewerId): DailyCashClosing
    {
        return DB::transaction(function () use ($closingId, $action, $rejectionReason, $reviewerId) {
            $closing = DailyCashClosing::lockForUpdate()->findOrFail($closingId);

            if ($action === 'reject') {
                if (empty(trim((string) $rejectionReason))) {
                    throw new InvalidArgumentException('يجب توضيح سبب رفض تقفيل اليومية.');
                }

                $closing->update([
                    'status' => DailyCashClosing::STATUS_REJECTED,
                    'rejection_reason' => $rejectionReason,
                    'approved_by' => $reviewerId,
                    'approved_at' => Carbon::now(),
                ]);

                FinancialNotification::create([
                    'user_id' => $closing->closed_by,
                    'title' => "تم رفض إقفال يومية {$closing->closing_date->toDateString()}",
                    'message' => "رفضت الإدارة إقفال يومية {$closing->closing_date->toDateString()}. السبب: {$rejectionReason}",
                    'type' => 'closing_rejected',
                    'url' => route('finance.closing.index', ['date' => $closing->closing_date->toDateString()]),
                ]);

                return $closing;
            }

            // Approve
            $closing->update([
                'status' => DailyCashClosing::STATUS_APPROVED,
                'approved_by' => $reviewerId,
                'approved_at' => Carbon::now(),
                'rejection_reason' => null,
            ]);

            // If there is deficit, create automatic adjusting entry for cash shortage
            if ($closing->hasDeficit()) {
                $deficitAmount = abs((float) $closing->difference);
                $centralTreasury = Treasury::where('type', 'cashbox')->first() ?? Treasury::first();

                if ($centralTreasury) {
                    $this->createAutomaticJournalEntry([
                        'date' => $closing->closing_date,
                        'description' => "تسوية عجز الخزينة عن إقفال يومية {$closing->closing_date->toDateString()}",
                        'source_type' => JournalEntry::SOURCE_CLOSING,
                        'source_id' => $closing->id,
                        'created_by' => $reviewerId,
                        'lines' => [
                            [
                                'account_type' => JournalEntryLine::ACCOUNT_CASH_SHORTAGE,
                                'account_id' => null,
                                'account_name' => 'حساب عجز وفروقات الخزينة (المصروفات)',
                                'debit' => $deficitAmount,
                                'credit' => 0.00,
                                'notes' => "عجز تقفيل يومية {$closing->closing_date->toDateString()}",
                            ],
                            [
                                'account_type' => JournalEntryLine::ACCOUNT_TREASURY,
                                'account_id' => $centralTreasury->id,
                                'account_name' => $centralTreasury->name,
                                'debit' => 0.00,
                                'credit' => $deficitAmount,
                                'notes' => 'تسوية العد الفعلي للخزينة',
                            ],
                        ],
                    ]);
                }
            }

            FinancialNotification::create([
                'user_id' => $closing->closed_by,
                'title' => "تم اعتماد إقفال يومية {$closing->closing_date->toDateString()}",
                'message' => "اعتمدت الإدارة إقفال يومية {$closing->closing_date->toDateString()} بنجاح.",
                'type' => 'closing_approved',
                'url' => route('finance.closing.index', ['date' => $closing->closing_date->toDateString()]),
            ]);

            return $closing;
        });
    }

    /**
     * 7. Automatic Journal Entry Creation (سحب القيود اليومية أوتوماتيك).
     */
    public function createAutomaticJournalEntry(array $data): JournalEntry
    {
        $count = JournalEntry::whereDate('created_at', Carbon::today())->count() + 1;
        $entryNumber = 'JE-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        $entry = JournalEntry::create([
            'entry_number' => $entryNumber,
            'entry_date' => $data['date'] ?? Carbon::today(),
            'description' => $data['description'],
            'source_type' => $data['source_type'] ?? JournalEntry::SOURCE_MANUAL,
            'source_id' => $data['source_id'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
            'is_posted' => true,
        ]);

        foreach ($data['lines'] as $line) {
            $entry->lines()->create([
                'account_type' => $line['account_type'],
                'account_id' => $line['account_id'] ?? null,
                'account_name' => $line['account_name'],
                'debit' => (float) ($line['debit'] ?? 0.00),
                'credit' => (float) ($line['credit'] ?? 0.00),
                'notes' => $line['notes'] ?? null,
            ]);
        }

        return $entry;
    }

    /**
     * 8. General Ledger Report (دفتر الأستاذ العام لكل الحسابات).
     */
    public function getGeneralLedger(?string $accountType = null, ?int $accountId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth()->toDateString();
        $endDate = $endDate ?: Carbon::now()->endOfMonth()->toDateString();

        $query = JournalEntryLine::query()
            ->with('journalEntry')
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('entry_date', [$startDate, $endDate]);
            });

        if ($accountType) {
            $query->where('account_type', $accountType);
        }

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $lines = $query->orderBy('created_at', 'asc')->get();

        // Calculate opening balance before startDate
        $openingDebitQuery = JournalEntryLine::whereHas('journalEntry', function ($q) use ($startDate) {
            $q->where('entry_date', '<', $startDate);
        });
        if ($accountType) {
            $openingDebitQuery->where('account_type', $accountType);
        }
        if ($accountId) {
            $openingDebitQuery->where('account_id', $accountId);
        }

        $openingDebit = (float) $openingDebitQuery->sum('debit');
        $openingCredit = (float) $openingDebitQuery->sum('credit');
        $openingBalance = $openingDebit - $openingCredit;

        $runningBalance = $openingBalance;
        $movements = [];

        foreach ($lines as $line) {
            $runningBalance += ((float) $line->debit - (float) $line->credit);
            $movements[] = [
                'date' => $line->journalEntry->entry_date->toDateString(),
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->journalEntry->description,
                'account_name' => $line->account_name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'running_balance' => $runningBalance,
                'notes' => $line->notes,
            ];
        }

        return [
            'account_type' => $accountType,
            'account_id' => $accountId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'movements' => $movements,
            'total_debit' => (float) $lines->sum('debit'),
            'total_credit' => (float) $lines->sum('credit'),
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * 9. Monthly Itemized Breakdown by Entity (تفريدة لكل شخص أو جهة أخذت أو وردت أموال طول الشهر).
     */
    public function getMonthlyBreakdown(int $year, int $month, ?string $entityType = null, ?int $entityId = null): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $vouchersQuery = FinancialVoucher::with(['treasury', 'supplier', 'hospital', 'creator'])
            ->whereBetween('voucher_date', [$startDate, $endDate]);

        if ($entityType === 'supplier') {
            $vouchersQuery->whereNotNull('supplier_id');
            if ($entityId) {
                $vouchersQuery->where('supplier_id', $entityId);
            }
        } elseif ($entityType === 'hospital') {
            $vouchersQuery->whereNotNull('hospital_id');
            if ($entityId) {
                $vouchersQuery->where('hospital_id', $entityId);
            }
        }

        $vouchers = $vouchersQuery->orderBy('voucher_date', 'asc')->get();

        // Entity summaries
        $entities = [];

        foreach ($vouchers as $v) {
            $key = $v->supplier_id
                ? 'supplier_'.$v->supplier_id
                : ($v->hospital_id
                    ? 'hospital_'.$v->hospital_id
                    : 'user_'.($v->created_by ?: 'gen'));

            $name = $v->supplier?->name ?? ($v->hospital?->name ?? ($v->creator?->name ?? 'مصروفات عامة'));
            $category = $v->supplier_id ? 'مورد' : ($v->hospital_id ? 'موقع مستشفى' : 'موظف / نثريات');

            if (! isset($entities[$key])) {
                $entities[$key] = [
                    'key' => $key,
                    'name' => $name,
                    'category' => $category,
                    'total_disbursed' => 0.00, // فلوس أخذها (صادر)
                    'total_received' => 0.00,  // فلوس وردها (وارد)
                    'transactions' => [],
                ];
            }

            $amount = (float) $v->amount;
            if ($v->type === FinancialVoucher::TYPE_PAYMENT) {
                $entities[$key]['total_disbursed'] += $amount;
            } elseif ($v->type === FinancialVoucher::TYPE_RECEIPT) {
                $entities[$key]['total_received'] += $amount;
            }

            $entities[$key]['transactions'][] = [
                'date' => $v->voucher_date->toDateString(),
                'voucher_number' => $v->voucher_number,
                'type' => $v->type,
                'type_label' => $v->type === FinancialVoucher::TYPE_PAYMENT ? 'صرف (أخذ)' : 'قبض (ورّد)',
                'treasury_name' => $v->treasury->name,
                'amount' => $amount,
                'purpose' => $v->purpose,
                'payment_method' => $v->payment_method,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'entities' => array_values($entities),
            'grand_total_disbursed' => (float) $vouchers->where('type', FinancialVoucher::TYPE_PAYMENT)->sum('amount'),
            'grand_total_received' => (float) $vouchers->where('type', FinancialVoucher::TYPE_RECEIPT)->sum('amount'),
        ];
    }

    /**
     * 10. Custodies and Suppliers Settlements (ترصيد الموردين والعهد).
     */
    public function getCustodiesAndSettlements(): array
    {
        $suppliers = Supplier::with('paymentSchedules')
            ->orderBy('current_balance', 'desc')
            ->get();

        $siteCustodies = Treasury::with('hospital')
            ->where('type', 'site_custody')
            ->get();

        $custodySummary = [];
        foreach ($siteCustodies as $custody) {
            $totalDisbursed = (float) FinancialVoucher::where('treasury_id', $custody->id)
                ->where('type', FinancialVoucher::TYPE_RECEIPT) // Custody replenishment
                ->sum('amount');

            $totalSpent = (float) FinancialVoucher::where('treasury_id', $custody->id)
                ->where('type', FinancialVoucher::TYPE_PAYMENT) // Custody expenses
                ->sum('amount');

            $custodySummary[] = [
                'id' => $custody->id,
                'name' => $custody->name,
                'hospital_name' => $custody->hospital?->name ?? 'غير محدد',
                'current_balance' => (float) $custody->balance,
                'total_replenished' => $totalDisbursed,
                'total_spent' => $totalSpent,
            ];
        }

        return [
            'suppliers' => $suppliers,
            'custodies' => $custodySummary,
        ];
    }

    /**
     * Helper to resolve account type for journal lines.
     */
    protected function resolveBeneficiaryAccountType(DisbursementOrder $order): string
    {
        return match ($order->beneficiary_type) {
            DisbursementOrder::BENEFICIARY_SUPPLIER => JournalEntryLine::ACCOUNT_SUPPLIER,
            DisbursementOrder::BENEFICIARY_HOSPITAL => JournalEntryLine::ACCOUNT_HOSPITAL_CUSTODY,
            DisbursementOrder::BENEFICIARY_USER => JournalEntryLine::ACCOUNT_EXPENSE,
            default => JournalEntryLine::ACCOUNT_EXPENSE,
        };
    }

    /**
     * Helper to resolve payer account type for receipt journal lines.
     */
    protected function resolvePayerAccountType(ReceiptOrder $order): string
    {
        return match ($order->payer_type) {
            ReceiptOrder::PAYER_HOSPITAL => JournalEntryLine::ACCOUNT_REVENUE,
            ReceiptOrder::PAYER_SUPPLIER => JournalEntryLine::ACCOUNT_SUPPLIER,
            ReceiptOrder::PAYER_USER => JournalEntryLine::ACCOUNT_HOSPITAL_CUSTODY,
            default => JournalEntryLine::ACCOUNT_REVENUE,
        };
    }
}
