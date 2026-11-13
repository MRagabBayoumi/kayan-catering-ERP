<?php

namespace App\Services;

use App\Models\DailyCashClosing;
use App\Models\FinancialVoucher;
use App\Models\Supplier;
use App\Models\Treasury;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Create a financial voucher (صرف عهدة، سداد مورد، قبض مستشفى، تحويل بنكي).
     */
    public function createVoucher(array $data): FinancialVoucher
    {
        return DB::transaction(function () use ($data) {
            $count = FinancialVoucher::whereDate('created_at', Carbon::today())->count() + 1;
            $voucherNumber = 'VCH-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $treasury = Treasury::findOrFail($data['treasury_id']);
            $amount = (float) $data['amount'];
            $type = $data['type'];

            // Adjust treasury balance
            if ($type === FinancialVoucher::TYPE_PAYMENT) {
                $treasury->balance -= $amount;
            } elseif ($type === FinancialVoucher::TYPE_RECEIPT) {
                $treasury->balance += $amount;
            }
            $treasury->save();

            // If paying a supplier, reduce supplier's credit balance
            if (! empty($data['supplier_id']) && $type === FinancialVoucher::TYPE_PAYMENT) {
                $supplier = Supplier::findOrFail($data['supplier_id']);
                $supplier->current_balance -= $amount;
                $supplier->save();
            }

            return FinancialVoucher::create([
                'voucher_number' => $voucherNumber,
                'type' => $type,
                'treasury_id' => $treasury->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'hospital_id' => $data['hospital_id'] ?? null,
                'amount' => $amount,
                'purpose' => $data['purpose'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'created_by' => $data['created_by'] ?? auth()->id(),
                'voucher_date' => $data['voucher_date'] ?? Carbon::today(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Compute and create Daily Cash Closing for the owner.
     */
    public function generateDailyClosing(string $date, int $userId): DailyCashClosing
    {
        return DB::transaction(function () use ($date, $userId) {
            $dayVouchers = FinancialVoucher::whereDate('voucher_date', $date)->get();

            $totalInflow = $dayVouchers->where('type', FinancialVoucher::TYPE_RECEIPT)->sum('amount');
            $totalOutflow = $dayVouchers->where('type', FinancialVoucher::TYPE_PAYMENT)->sum('amount');

            // Site custody expenses (e.g. bread, kaizer, ice)
            $siteSpent = $dayVouchers->filter(function ($v) {
                return $v->type === FinancialVoucher::TYPE_PAYMENT && ! empty($v->hospital_id);
            })->sum('amount');

            // Supplier payments
            $supplierPayments = $dayVouchers->filter(function ($v) {
                return $v->type === FinancialVoucher::TYPE_PAYMENT && ! empty($v->supplier_id);
            })->sum('amount');

            // Current total treasuries balance
            $currentTreasuryBalance = Treasury::sum('balance');
            $openingBalance = $currentTreasuryBalance - $totalInflow + $totalOutflow;

            $dateFormatted = Carbon::parse($date)->toDateString();
            $closing = DailyCashClosing::whereDate('closing_date', $dateFormatted)->first();

            $attributes = [
                'opening_balance' => max(0, $openingBalance),
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'closing_balance' => $currentTreasuryBalance,
                'site_custody_spent' => $siteSpent,
                'supplier_payments' => $supplierPayments,
                'status' => 'closed',
                'notes' => 'تم الإغلاق اليومي والمطابقة مع الخزن وعهد المواقع',
                'closed_by' => $userId,
            ];

            if ($closing) {
                $closing->update($attributes);

                return $closing;
            }

            return DailyCashClosing::create(array_merge($attributes, [
                'closing_date' => $dateFormatted,
            ]));
        });
    }
}
