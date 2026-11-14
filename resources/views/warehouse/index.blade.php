@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إدارة المخازن وتشغيلات الـ FIFO / FEFO" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'التشغيلات والباتشات' => route('warehouse.index')]">
        <x-slot:actions>
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="truck" size="16" />
                <span>إذن تحميل وصرف</span>
            </a>
            @endcan
            <a href="{{ route('warehouse.expiry') }}" class="erp-btn erp-btn-secondary text-amber-700 dark:text-amber-400">
                <x-icon name="alert-triangle" size="16" />
                <span>محرك الصلاحيات وFEFO</span>
            </a>
            <form action="{{ route('warehouse.sync-barcodes') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="erp-btn erp-btn-secondary" title="استيراد وتحديث الباركود من قاعدة بيانات KAYAN ERP">
                    <x-icon name="barcode" size="16" />
                    <span>مزامنة باركود ERP</span>
                </button>
            </form>
            @can('perform-blind-audit')
            <a href="{{ route('warehouse.audits.create') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clipboard-check" size="16" />
                <span>جلسة جرد أعمى</span>
            </a>
            @endcan
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
    @php
        $warehouseTabs = [
            [
                'label' => 'تشغيلات الشراء والـ FIFO',
                'url' => route('warehouse.index'),
                'icon' => 'package',
                'active' => request()->routeIs('warehouse.index'),
                'badge' => $batches->total(),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'الصلاحيات وتنبيهات FEFO',
                'url' => route('warehouse.expiry'),
                'icon' => 'alert-triangle',
                'active' => request()->routeIs('warehouse.expiry*'),
                'badge' => $criticalBatchesCount > 0 ? $criticalBatchesCount : null,
                'badge_class' => 'erp-badge-danger',
            ],
            [
                'label' => 'أذون التحميل والصرف (السيارات والمطابخ)',
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
            ],
        ];
    @endphp
    <x-module-nav :tabs="$warehouseTabs" />

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
                {{ $criticalBatchesCount }}
            </div>
            <div class="kpi-note font-numeric text-xs text-rose-600 font-medium">
                <a href="{{ route('warehouse.expiry', ['status' => 'critical']) }}" class="underline hover:text-rose-700">فحص تشغيلات FEFO الحرجة</a>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">أصناف بباركود ERP</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="barcode" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-bold text-emerald-600">{{ $barcodedProductsCount }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مزامنة من KAYAN ERP</div>
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
                            <th>الصنف الغذائي والباركود</th>
                            <th>المخزن</th>
                            <th>سعر شراء الوحدة</th>
                            <th>الكمية المتبقية</th>
                            <th>تاريخ الاستلام</th>
                            <th>الصلاحية وFEFO</th>
                            <th class="text-center">طباعة الباركود</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $b)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)] text-xs">
                                    {{ $b->batch_number }}
                                </td>
                                <td>
                                    <div class="font-bold text-[var(--text-main)]">{{ $b->product->name ?? 'صنف' }}</div>
                                    <div class="text-[11px] text-[var(--text-muted)] font-mono flex items-center gap-2 mt-0.5">
                                        <span>SKU: {{ $b->product->sku ?? '-' }}</span>
                                        @if(!empty($b->product->barcode))
                                            <span>·</span>
                                            <span class="inline-flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                                <x-icon name="barcode" size="12" />
                                                {{ $b->product->barcode }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $b->warehouse->name ?? '-' }}</td>
                                <td class="font-numeric font-bold">{{ number_format($b->cost_price, 2) }} ج.م</td>
                                <td class="font-numeric font-extrabold text-[var(--text-main)]">{{ number_format($b->remaining_quantity, 2) }} {{ $b->product->unit ?? 'كجم' }}</td>
                                <td class="font-numeric text-xs text-[var(--text-muted)]">{{ $b->received_at ? $b->received_at->format('Y-m-d') : '-' }}</td>
                                <td>
                                    @php
                                        $days = $b->days_until_expiry;
                                        $badgeClass = match($b->expiry_status) {
                                            'expired' => 'erp-badge-danger',
                                            'critical' => 'erp-badge-danger font-bold',
                                            'warning' => 'erp-badge-warning',
                                            'safe' => 'erp-badge-success',
                                            default => 'erp-badge-neutral',
                                        };
                                        $label = match($b->expiry_status) {
                                            'expired' => 'منتهي (' . abs($days) . ' يوم)',
                                            'critical' => 'حرج (' . $days . ' يوم)',
                                            'warning' => 'تحذير (' . $days . ' يوم)',
                                            'safe' => 'آمن (' . $days . ' يوم)',
                                            default => 'غير محدد',
                                        };
                                    @endphp
                                    @if($b->expiry_date)
                                        <div class="flex flex-col gap-1 items-start">
                                            <span class="erp-badge {{ $badgeClass }} font-numeric text-[11px]">
                                                {{ $label }}
                                            </span>
                                            <span class="text-[10px] text-[var(--text-muted)] font-mono">
                                                {{ $b->expiry_date->format('Y-m-d') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="erp-badge erp-badge-neutral text-xs">بدون تاريخ</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('warehouse.batches.print-label', $b->id) }}" target="_blank" class="erp-btn erp-btn-secondary text-[11px] px-2 py-1" title="طباعة ملصق حراري لباركود وبيانات التشغيلة">
                                            <x-icon name="printer" size="13" class="text-blue-600" />
                                            <span>ملصق التشغيلة</span>
                                        </a>
                                        @if($b->product)
                                            <a href="{{ route('products.print-label', $b->product->id) }}" target="_blank" class="erp-btn erp-btn-secondary text-[11px] px-2 py-1" title="طباعة باركود الصنف">
                                                <x-icon name="barcode" size="13" class="text-slate-600" />
                                                <span>باركود الصنف</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-[var(--text-muted)] text-xs">
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
