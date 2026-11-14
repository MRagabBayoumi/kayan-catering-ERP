@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تسويات رحلات المناديب ومخرجات الـ ERP" :breadcrumbs="['إدارة المخازن والتشغيلات' => route('warehouse.index'), 'تسويات المناديب' => route('warehouse.settlements.index')]">
        <x-slot:actions>
            <a href="{{ route('warehouse.settlements.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسوية رحلة جديدة</span>
            </a>
            <a href="{{ route('warehouse.dispatches') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>أذون التحميل والتوزيع</span>
            </a>
            <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>تشغيلات الشراء (FIFO)</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي التسويات:</span>
                <strong>{{ count($settlements) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $warehouseTabs = [
            [
                'label' => 'تشغيلات الشراء والـ FIFO',
                'url' => route('warehouse.index'),
                'icon' => 'package',
                'active' => request()->routeIs('warehouse.index'),
            ],
            [
                'label' => 'أذون التحميل والصرف (السيارات)',
                'url' => route('warehouse.dispatches'),
                'icon' => 'truck',
                'active' => request()->routeIs('warehouse.dispatches*'),
            ],
            [
                'label' => 'الجرد الأعمى (Blind Audit)',
                'url' => route('warehouse.audits.index'),
                'icon' => 'clipboard-check',
                'active' => request()->routeIs('warehouse.audits*'),
            ],
            [
                'label' => 'تسويات رحلات المناديب ومخرجات الـ ERP',
                'url' => route('warehouse.settlements.index'),
                'icon' => 'receipt',
                'active' => request()->routeIs('warehouse.settlements*'),
                'badge' => count($settlements),
                'badge_class' => 'erp-badge-primary',
            ],
        ];
    @endphp
    <x-module-nav :tabs="$warehouseTabs" />

    @if(session('success'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Fast Status KPI Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إجمالي رحلات التسوية</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="receipt" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ count($settlements) }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">رحلات تم مطابقتها واعتمادها</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المسلم للمستشفيات</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-emerald-600">
                {{ number_format(collect($settlements)->sum('total_delivered_qty'), 2) }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">كميات بإشعار استلام الموقع</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المرتجع للمخزن</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="rotate-ccw" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-sky-600">
                {{ number_format(collect($settlements)->sum('total_returned_qty'), 2) }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">أعيد لرصيد المخزن المركزي</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">تكلفة الـ FIFO المصروفة</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="wallet" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ number_format(collect($settlements)->sum('total_cogs_cost'), 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">قيود تكلفة البضاعة المباعة</div>
        </div>
    </div>

    <!-- Settlements Table Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="receipt" size="16" class="text-[var(--primary)]" />
                <span>سجل تسويات عهد سيارات التوريد اليومية ومطابقة إذن التحميل</span>
            </div>
            <a href="{{ route('warehouse.settlements.create') }}" class="erp-btn erp-btn-secondary text-xs">
                <x-icon name="plus" size="12" />
                <span>إجراء تسوية</span>
            </a>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم التسوية</th>
                            <th>المستشفى / الموقع</th>
                            <th>السائق / المندوب</th>
                            <th>محاسب المخزن</th>
                            <th class="text-center">المحمل بالسيارة</th>
                            <th class="text-center">المسلم للمستشفى</th>
                            <th class="text-center">المرتجع للمخزن</th>
                            <th class="text-center">تكلفة FIFO المسلمة</th>
                            <th class="text-center">رقم قيد الـ ERP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $st)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)]">{{ $st->settlement_number }}</td>
                                <td class="font-bold text-[var(--text-main)]">{{ $st->dispatchOrder->hospital->name ?? '-' }}</td>
                                <td>{{ $st->driver->name ?? '-' }}</td>
                                <td>{{ $st->accountant->name ?? '-' }}</td>
                                <td class="font-numeric text-center font-bold">{{ number_format($st->total_dispatched_qty, 2) }}</td>
                                <td class="font-numeric text-center font-bold text-emerald-600">{{ number_format($st->total_delivered_qty, 2) }}</td>
                                <td class="font-numeric text-center font-bold text-sky-600">{{ number_format($st->total_returned_qty, 2) }}</td>
                                <td class="font-numeric text-center font-black text-[var(--text-main)]">{{ number_format($st->total_cogs_cost, 2) }} ج.م</td>
                                <td class="text-center">
                                    <span class="erp-badge erp-badge-info font-numeric font-bold text-xs">
                                        {{ $st->erp_journal_voucher }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-8 text-[var(--text-muted)] text-xs">
                                    لا توجد تسويات مسجلة حالياً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
