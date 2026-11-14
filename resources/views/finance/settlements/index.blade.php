@extends('layouts.app')

@section('title', 'ترصيد الموردين وعهد المواقع')

@section('control_panel')
    <x-control-panel title="ترصيد وتسوية حسابات الموردين وعهد المواقع (Settlements)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'ترصيد الموردين والعهد' => route('finance.settlements.index')]">
        <x-slot:actions>
            <a href="{{ route('finance.disbursements.index') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إصدار إذن صرف / دفعة</span>
            </a>
            <a href="{{ route('finance.ledger.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book-open" size="16" />
                <span>دفتر الأستاذ العام</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>الموردين:</span>
                <strong>{{ count($suppliers) }}</strong>
                <span class="mx-1">|</span>
                <span>العهد:</span>
                <strong>{{ count($custodies) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <x-finance-nav />

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="erp-card p-4 border-r-4 border-r-rose-500">
            <span class="text-xs text-slate-500 font-semibold">إجمالي مستحقات الموردين القائمة (دائنون)</span>
            <div class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400 mt-1">
                {{ number_format($suppliers->sum('current_balance'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">إجمالي المديونية الواجبة السداد للموردين المعتمدين</span>
        </div>

        <div class="erp-card p-4 border-r-4 border-r-amber-500">
            <span class="text-xs text-slate-500 font-semibold">إجمالي السيولة النقدية بالعهد (مواقع المستشفيات)</span>
            <div class="text-2xl font-mono font-bold text-amber-600 dark:text-amber-400 mt-1">
                {{ number_format(collect($custodies)->sum('current_balance'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">مبالغ عهد نقدية طرف مشرفي المواقع لشراء الخبز والكايزر</span>
        </div>
    </div>

    <!-- Section 1: Site Custodies Settlement (ترصيد عهد المواقع) -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <x-icon name="wallet" size="18" class="text-amber-600" />
                    <span>ترصيد وتسوية عهد المواقع والمستشفيات (Site Petty Cash)</span>
                </h3>
                <span class="text-xs text-slate-400">متابعة المنصرف والمتبقي نقداً بكل موقع مستشفى</span>
            </div>
            <span class="erp-badge erp-badge-warning font-numeric">{{ count($custodies) }} عهدة</span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table text-xs">
                <thead>
                    <tr>
                        <th>اسم العهدة</th>
                        <th>موقع المستشفى</th>
                        <th class="text-left font-mono">الرصيد المتبقي بالعهدة</th>
                        <th class="text-left font-mono">إجمالي التغذية (المستلم)</th>
                        <th class="text-left font-mono">إجمالي المنصرف بمستندات</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($custodies as $c)
                        <tr>
                            <td class="font-bold text-slate-800 dark:text-slate-100">{{ $c['name'] }}</td>
                            <td>{{ $c['hospital_name'] }}</td>
                            <td class="font-mono text-left font-bold text-amber-600 dark:text-amber-400 text-sm">
                                {{ number_format($c['current_balance'], 2) }} ج.م
                            </td>
                            <td class="font-mono text-left text-slate-600 dark:text-slate-300">
                                {{ number_format($c['total_replenished'], 2) }} ج.م
                            </td>
                            <td class="font-mono text-left text-rose-600 dark:text-rose-400 font-bold">
                                {{ number_format($c['total_spent'], 2) }} ج.م
                            </td>
                            <td>
                                @if($c['current_balance'] < 1000)
                                    <span class="erp-badge erp-badge-danger">تتطلب استعاضة عاجلة</span>
                                @else
                                    <span class="erp-badge erp-badge-success">رصيد كافٍ</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('finance.ledger.index', ['account_type' => 'hospital_custody', 'account_id' => $c['id']]) }}" 
                                   class="erp-btn erp-btn-secondary text-xs py-1 px-2.5">
                                    <x-icon name="book-open" size="14" />
                                    <span>كشف الحساب</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400">
                                لا توجد عهد مواقع مسجلة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Suppliers Balances & Settlements (ترصيد الموردين) -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <x-icon name="factory" size="18" class="text-rose-600" />
                    <span>ترصيد حسابات الموردين والأرصدة المستحقة (Suppliers Ledger)</span>
                </h3>
                <span class="text-xs text-slate-400">إجمالي المشتريات الآجلة والمدفوعات والمتبقي</span>
            </div>
            <span class="erp-badge erp-badge-danger font-numeric">{{ count($suppliers) }} مورد</span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table text-xs">
                <thead>
                    <tr>
                        <th>كود المورد</th>
                        <th>اسم المورد</th>
                        <th>رقم الهاتف / المسؤول</th>
                        <th class="text-left font-mono">الرصيد المستحق القائم</th>
                        <th class="text-left font-mono">الحد الائتماني</th>
                        <th>شروط السداد</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $supplier->code }}</td>
                            <td class="font-bold text-slate-800 dark:text-slate-100">{{ $supplier->name }}</td>
                            <td>
                                <div>{{ $supplier->contact_person ?: '-' }}</div>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $supplier->phone }}</span>
                            </td>
                            <td class="font-mono text-left font-bold text-rose-600 dark:text-rose-400 text-sm">
                                {{ number_format($supplier->current_balance, 2) }} ج.م
                            </td>
                            <td class="font-mono text-left text-slate-500">
                                {{ number_format($supplier->credit_limit, 2) }} ج.م
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-neutral text-[10px] font-mono">
                                    {{ $supplier->payment_terms_days }} يوم
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('finance.ledger.index', ['account_type' => 'supplier', 'account_id' => $supplier->id]) }}" 
                                       class="erp-btn erp-btn-secondary text-xs py-1 px-2.5">
                                        <x-icon name="book-open" size="14" />
                                        <span>الأستاذ</span>
                                    </a>
                                    <a href="{{ route('finance.disbursements.index') }}" 
                                       class="erp-btn erp-btn-primary text-xs py-1 px-2.5">
                                        <x-icon name="wallet" size="14" />
                                        <span>سداد دفعة</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400">
                                لا يوجد موردين مسجلين بالمنظومة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
