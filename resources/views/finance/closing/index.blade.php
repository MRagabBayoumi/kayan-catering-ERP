@extends('layouts.app')

@section('title', 'اليومية والتقفيل لصاحب العمل')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">اليومية والتقفيل المالي لصاحب العمل (Daily Closing)</h1>
            <p class="text-xs text-slate-500 mt-1">تقرير الإقفال اليومي الشامل للوارد والمصروف وسداد الموردين ومصروفات عهد المواقع</p>
        </div>
        <form action="{{ route('finance.closing.index') }}" method="GET" class="flex gap-2">
            <input type="date" name="date" class="erp-form-control text-sm" value="{{ $date }}">
            <button type="submit" class="erp-btn erp-btn-secondary">
                <x-icon name="search" size="14" />
                <span>عرض اليومية</span>
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Daily Financial Closing KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المقبوضات (الوارد)</span>
            <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                {{ number_format($closing?->total_inflow ?? $vouchers->where('type', 'receipt_voucher')->sum('amount'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">تحصيلات من الجهات والمستشفيات</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المدفوعات (المصروف)</span>
            <div class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400 mt-1">
                {{ number_format($closing?->total_outflow ?? $vouchers->where('type', 'payment_voucher')->sum('amount'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">مصروفات وتشغيل وسداد</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">منصرف عهد المواقع (خبز/كايزر/ثلج)</span>
            <div class="text-2xl font-mono font-bold text-amber-600 dark:text-amber-400 mt-1">
                {{ number_format($closing?->site_custody_spent ?? $vouchers->where('type', 'payment_voucher')->whereNotNull('hospital_id')->sum('amount'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">مشتريات نقدية يومية بالمستشفيات</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">سدادات الموردين اليومية</span>
            <div class="text-2xl font-mono font-bold text-primary mt-1">
                {{ number_format($closing?->supplier_payments ?? $vouchers->where('type', 'payment_voucher')->whereNotNull('supplier_id')->sum('amount'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">شيكات ودفعات الموردين الآجل</span>
        </div>
    </div>

    <!-- Status & Action Box -->
    <div class="erp-card p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="font-bold text-slate-800 dark:text-slate-100">
                حالة إقفال اليومية ليوم: <span class="font-mono text-primary">{{ $date }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                @if($closing)
                    ✅ تم تقفيل اليومية ومراجعة الأرصدة بواسطة: {{ $closing->closedByUser->name ?? 'مدير النظام' }}
                @else
                    ⚠️ اليومية مفتوحة ولم يتم اعتماد التقفيل النهائي لصاحب العمل بعد.
                @endif
            </p>
        </div>

        <form action="{{ route('finance.closing.store') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <button type="submit" class="erp-btn erp-btn-primary">
                <x-icon name="check-circle" size="16" />
                <span>اعتماد وتقفيل اليومية لصاحب العمل</span>
            </button>
        </form>
    </div>

    <!-- Detail Table of Today's Transactions -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-bold text-slate-800 dark:text-slate-100">حركات وسندات يوم {{ $date }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم السند</th>
                        <th>النوع</th>
                        <th>الخزينة / العهدة</th>
                        <th>الجهة أو المورد</th>
                        <th>المبلغ</th>
                        <th>البيان والغرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $vch)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $vch->voucher_number }}</td>
                            <td>
                                @if($vch->type === 'payment_voucher')
                                    <span class="erp-badge erp-badge-danger">صرف</span>
                                @elseif($vch->type === 'receipt_voucher')
                                    <span class="erp-badge erp-badge-success">قبض</span>
                                @else
                                    <span class="erp-badge erp-badge-info">تحويل</span>
                                @endif
                            </td>
                            <td>{{ $vch->treasury->name }}</td>
                            <td>
                                {{ $vch->hospital?->name ?? ($vch->supplier?->name ?? 'المركز الرئيسي') }}
                            </td>
                            <td class="font-mono font-bold {{ $vch->type === 'payment_voucher' ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $vch->type === 'payment_voucher' ? '-' : '+' }}{{ number_format($vch->amount, 2) }} ج.م
                            </td>
                            <td>{{ $vch->purpose }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">
                                لا توجد حركات مالية مسجلة في هذا اليوم.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
