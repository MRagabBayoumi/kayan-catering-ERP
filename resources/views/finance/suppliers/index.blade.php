@extends('layouts.app')

@section('title', 'حسابات الموردين وجدولة السداد')

@section('control_panel')
    <x-control-panel title="حسابات الموردين والآجل وجدولة السداد (الحسابات والمالية)" :breadcrumbs="['الحسابات والمالية' => route('finance.suppliers.index'), 'حسابات الموردين' => route('finance.suppliers.index')]">
        <x-slot:actions>
            <a href="{{ route('finance.treasury.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="wallet" size="16" />
                <span>الخزينة وعهد المواقع</span>
            </a>
            <a href="{{ route('finance.closing.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="pie-chart" size="16" />
                <span>التقفيل واليومية</span>
            </a>
            <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-bag" size="16" />
                <span>طلبات الشراء</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الموردين:</span>
                <strong>{{ count($suppliers) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <x-finance-nav />

    <!-- Fast Status KPI Ribbon (Finance & Suppliers Overview) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إجمالي الموردين المعتمدين</span>
                <span class="kpi-icon text-purple-600 bg-purple-50 dark:bg-purple-950/40"><x-icon name="truck" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $suppliers->total() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">موردي اللحوم والدواجن والأغذية</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إجمالي أرصدة الموردين</span>
                <span class="kpi-icon text-rose-600 bg-rose-50 dark:bg-rose-950/40"><x-icon name="wallet" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ number_format((float) $suppliers->sum('current_balance'), 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مستحقات آجلة قيد الجدولة والسداد</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">موردين عليهم مستحقات</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="credit-card" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ $suppliers->where('current_balance', '>', 0)->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">موردين بأرصدة دائنة نشطة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">جدولة السداد المعلقة</span>
                <span class="kpi-icon text-blue-600 bg-blue-50 dark:bg-blue-950/40"><x-icon name="calendar" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ $suppliers->sum(fn ($s) => $s->paymentSchedules?->where('status', 'pending')->count() ?? 0) }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">دفعات مجدولة بانتظار الإذن والصرف</div>
        </div>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="erp-card">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>كود المورد</th>
                        <th>اسم المورد والنشاط</th>
                        <th>المسؤول ورقم الهاتف</th>
                        <th>الرصيد الحالي المستحق</th>
                        <th>الحد الائتماني</th>
                        <th>فترة الائتمان</th>
                        <th>جدولة السداد القادمة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $sup)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $sup->code }}</td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $sup->name }}</td>
                            <td>
                                <div>{{ $sup->contact_person ?? '-' }}</div>
                                <div class="text-xs font-mono text-slate-500">{{ $sup->phone ?? '-' }}</div>
                            </td>
                            <td class="font-mono font-bold text-rose-600 dark:text-rose-400 text-base">
                                {{ number_format($sup->current_balance, 2) }} ج.م
                            </td>
                            <td class="font-mono">{{ number_format($sup->credit_limit, 2) }} ج.م</td>
                            <td>
                                <span class="erp-badge erp-badge-info">{{ $sup->payment_terms_days }} يوم</span>
                            </td>
                            <td>
                                <div class="space-y-1">
                                    @foreach($sup->paymentSchedules as $sch)
                                        <div class="text-xs flex items-center justify-between gap-2 p-1.5 bg-slate-50 dark:bg-slate-800 rounded">
                                            <span class="font-mono text-slate-500">{{ $sch->due_date->format('Y-m-d') }}:</span>
                                            <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ number_format($sch->amount, 2) }} ج.م</span>
                                            <span class="erp-badge erp-badge-warning erp-badge-sm">{{ $sch->status }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                لا يوجد موردين مسجلين حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>
@endsection
