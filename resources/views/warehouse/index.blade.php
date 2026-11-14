@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إدارة المخازن وتشغيلات الـ FIFO" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'التشغيلات والباتشات' => route('warehouse.index')]">
        <x-slot:actions>
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="truck" size="16" />
                <span>إذن تحميل سيارة</span>
            </a>
            @endcan
            @can('perform-blind-audit')
            <a href="{{ route('warehouse.audits.create') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clipboard-check" size="16" />
                <span>جلسة جرد أعمى</span>
            </a>
            @endcan
            <a href="{{ route('warehouse.settlements.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="receipt" size="16" />
                <span>تسويات المناديب</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الباتشات:</span>
                <strong>{{ $batches->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <!-- Fast Status KPI Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">تشغيلات الشراء النشطة</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="layers" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $batches->total() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">أرصدة قابلة للصرف الفوري</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المخازن ومستودعات التبريد</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="warehouse" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ count($warehouses) }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مخزن رئيسي ومخازن سيارات</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">أصناف قريبة الانتهاء</span>
                <span class="kpi-icon text-rose-600 bg-rose-50 dark:bg-rose-950/40"><x-icon name="alert-triangle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-rose-600">
                {{ $batches->filter(fn($b) => $b->expiry_date && \Carbon\Carbon::parse($b->expiry_date)->lte(\Carbon\Carbon::now()->addDays(5)))->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-rose-600 font-medium">أولوية الصرف الفوري FIFO</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">آلية تسعير المخزون</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base font-bold text-emerald-600">Strict FIFO</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">الوارد أولاً يصرف أولاً</div>
        </div>
    </div>

    <!-- Warehouse Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('warehouse.index') }}" class="erp-btn {{ !$selectedWarehouseId ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
            <span>كافة المخازن</span>
        </a>
        @foreach($warehouses as $wh)
            <a href="{{ route('warehouse.index', ['warehouse_id' => $wh->id]) }}" class="erp-btn {{ $selectedWarehouseId == $wh->id ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
                <span>{{ $wh->name }}</span>
            </a>
        @endforeach
    </div>

    <!-- Active FIFO Batches Table Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="layers" size="16" class="text-[var(--primary)]" />
                <span>سجل الباتشات النشطة (مرتبة بالأقدم تاريخاً وفقاً لمعيار FIFO)</span>
            </div>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم التشغيلة (Batch)</th>
                            <th>الصنف الغذائي</th>
                            <th>المخزن</th>
                            <th>سعر شراء الوحدة</th>
                            <th>الكمية المتبقية</th>
                            <th>تاريخ الدخول</th>
                            <th>تاريخ الصلاحية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $b)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)] text-xs">{{ $b->batch_number }}</td>
                                <td class="font-bold text-[var(--text-main)]">{{ $b->product->name ?? 'صنف' }}</td>
                                <td>{{ $b->warehouse->name ?? '-' }}</td>
                                <td class="font-numeric font-bold">{{ number_format($b->cost_price, 2) }} ج.م</td>
                                <td class="font-numeric font-extrabold text-[var(--text-main)]">{{ number_format($b->remaining_quantity, 2) }} {{ $b->product->unit ?? 'كجم' }}</td>
                                <td class="font-numeric text-xs text-[var(--text-muted)]">{{ $b->entry_date ?? '-' }}</td>
                                <td>
                                    <span class="erp-badge {{ \Carbon\Carbon::parse($b->expiry_date)->lte(\Carbon\Carbon::now()->addDays(5)) ? 'erp-badge-danger' : 'erp-badge-neutral' }} font-numeric">
                                        {{ $b->expiry_date ?? 'بدون تاريخ' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-[var(--text-muted)] text-xs">
                                    لا توجد تشغيلات مسجلة حالياً
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
