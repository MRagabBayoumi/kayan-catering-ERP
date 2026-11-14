@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="أذون التحميل والتوريد لسيارات المناديب" :breadcrumbs="['إدارة المخازن والتشغيلات' => route('warehouse.index'), 'أذون التحميل' => route('warehouse.dispatches')]">
        <x-slot:actions>
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إصدار إذن تحميل جديد</span>
            </a>
            @endcan
            <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>تشغيلات الشراء (FIFO)</span>
            </a>
            <a href="{{ route('warehouse.settlements.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="receipt" size="16" />
                <span>تسويات عهد المناديب</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الأذون:</span>
                <strong>{{ count($dispatches) }}</strong>
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
                'badge' => count($dispatches),
                'badge_class' => 'erp-badge-primary',
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
                <span class="kpi-label">أذون التحميل الصادرة</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="truck" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ count($dispatches) }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">حركة سيارات التوزيع</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">سيارات في الطريق</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="navigation" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-amber-600">
                {{ collect($dispatches)->whereIn('status', ['prepared', 'in_transit'])->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">بعهدة السائقين قيد التسليم</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">أذون تم تسليمها للمستشفيات</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-emerald-600">
                {{ collect($dispatches)->where('status', 'delivered')->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">استلمها مشرف الموقع</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">أذون تمت تسويتها محاسبياً</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="receipt" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-sky-600">
                {{ collect($dispatches)->where('status', 'settled')->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">أغلقت بالـ ERP</div>
        </div>
    </div>

    <!-- Dispatches Table Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="truck" size="16" class="text-[var(--primary)]" />
                <span>سجل أذون خروج البضاعة من المخزن الرئيسي لسيارات التوريد</span>
            </div>
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-secondary text-xs">
                <x-icon name="plus" size="12" />
                <span>إذن جديد</span>
            </a>
            @endcan
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم الإذن</th>
                            <th>المستشفى المستهدفة</th>
                            <th>المخزن المصدر</th>
                            <th>سيارة المندوب والسائق</th>
                            <th>تاريخ ووقت الخروج</th>
                            <th>حالة الإذن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dispatches as $dsp)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)]">{{ $dsp->dispatch_number }}</td>
                                <td class="font-bold text-[var(--text-main)]">{{ $dsp->hospital->name ?? '-' }}</td>
                                <td>{{ $dsp->sourceWarehouse->name ?? '-' }}</td>
                                <td>
                                    <div class="font-semibold">{{ $dsp->transitWarehouse->name ?? '-' }}</div>
                                    <small class="text-xs text-[var(--text-muted)]">السائق: {{ $dsp->driver->name ?? '-' }}</small>
                                </td>
                                <td class="font-numeric text-xs">{{ $dsp->dispatched_at ? $dsp->dispatched_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>
                                    @if($dsp->status === 'delivered')
                                        <span class="erp-badge erp-badge-success">تم التسليم للمستشفى</span>
                                    @elseif($dsp->status === 'settled')
                                        <span class="erp-badge erp-badge-neutral">تمت التسوية محاسبياً</span>
                                    @else
                                        <span class="erp-badge erp-badge-warning">في الطريق (عهدة سيارة)</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-[var(--text-muted)] text-xs">
                                    لا توجد أذون تحميل مسجلة حالياً
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
