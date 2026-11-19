@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="محرك الصلاحيات وتنبيهات FEFO الذكية" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'محرك الصلاحيات وFEFO' => route('warehouse.expiry')]">
        <x-slot:actions>
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="truck" size="16" />
                <span>إذن صرف عاجل (FEFO)</span>
            </a>
            @endcan
            <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>سجل الباتشات العام</span>
            </a>
            <form action="{{ route('warehouse.sync-barcodes') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="erp-btn erp-btn-secondary" title="استيراد وتحديث الباركود من قاعدة بيانات KAYAN ERP">
                    <x-icon name="barcode" size="16" />
                    <span>مزامنة باركود ERP</span>
                </button>
            </form>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-danger font-numeric px-2.5 py-1 text-xs">
                <span>تشغيلات حرجة:</span>
                <strong>{{ $kpis['critical_count'] }}</strong>
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
                'label' => 'الصلاحيات وتنبيهات FEFO',
                'url' => route('warehouse.expiry'),
                'icon' => 'alert-triangle',
                'active' => request()->routeIs('warehouse.expiry*'),
                'badge' => $kpis['critical_count'] > 0 ? $kpis['critical_count'] : null,
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

    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 rounded-xl text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-icon name="check-circle" size="16" class="text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 rounded-xl text-xs font-semibold flex items-center gap-2">
            <x-icon name="alert-circle" size="16" class="text-rose-600" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Expiry Intelligence KPI Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. Critical Batches (< 7 days) -->
        <div class="kpi-card border-l-4 border-l-rose-500">
            <div class="kpi-header mb-1">
                <span class="kpi-label">تشغيلات حرجة (خلال 7 أيام)</span>
                <span class="kpi-icon text-rose-600 bg-rose-50 dark:bg-rose-950/40"><x-icon name="alert-triangle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-rose-600">{{ $kpis['critical_count'] }}</div>
            <div class="kpi-note font-numeric text-xs text-rose-600 font-bold flex items-center justify-between">
                <span>قيمة الباتشات الحرجة:</span>
                <span>{{ number_format($kpis['critical_value'], 2) }} ج.م</span>
            </div>
        </div>

        <!-- 2. Warning Batches (8 - 30 days) -->
        <div class="kpi-card border-l-4 border-l-amber-500">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إنذار مبكر (8 - 30 يوماً)</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="clock" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-amber-600">{{ $kpis['warning_count'] }}</div>
            <div class="kpi-note font-numeric text-xs text-amber-600 font-bold flex items-center justify-between">
                <span>قيمة الباتشات:</span>
                <span>{{ number_format($kpis['warning_value'], 2) }} ج.م</span>
            </div>
        </div>

        <!-- 3. Total Capital at Risk -->
        <div class="kpi-card border-l-4 border-l-orange-500">
            <div class="kpi-header mb-1">
                <span class="kpi-label">رأس المال المعرض لمخاطر الهدر</span>
                <span class="kpi-icon text-orange-600 bg-orange-50 dark:bg-orange-950/40"><x-icon name="dollar-sign" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-orange-600">{{ number_format($kpis['at_risk_value'], 2) }} ج.م</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">إجمالي التشغيلات قريبة الانتهاء</div>
        </div>

        <!-- 4. Expired Batches -->
        <div class="kpi-card border-l-4 border-l-slate-400">
            <div class="kpi-header mb-1">
                <span class="kpi-label">تشغيلات منتهية (تتطلب تسوية)</span>
                <span class="kpi-icon text-slate-600 bg-slate-50 dark:bg-slate-900"><x-icon name="slash" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-slate-700 dark:text-slate-300">{{ $kpis['expired_count'] }}</div>
            <div class="kpi-note font-numeric text-xs text-slate-500 flex items-center justify-between">
                <span>خسائر منتهية:</span>
                <span>{{ number_format($kpis['expired_value'], 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="erp-card p-4">
        <form action="{{ route('warehouse.expiry') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Search -->
            <div class="md:col-span-5">
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                        <x-icon name="search" size="15" />
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="ابحث برقم الباتش، اسم الصنف، الكود SKU، أو الباركود..." class="erp-form-control pr-9 text-xs">
                </div>
            </div>

            <!-- Warehouse Filter -->
            <div class="md:col-span-3">
                <select name="warehouse_id" class="erp-form-control text-xs">
                    <option value="">كافة المخازن ومستودعات التبريد</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $selectedWarehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Expiry Status Filter -->
            <div class="md:col-span-2">
                <select name="status" class="erp-form-control text-xs">
                    <option value="all" {{ $selectedStatus == 'all' ? 'selected' : '' }}>كل الحالات</option>
                    <option value="critical" {{ $selectedStatus == 'critical' ? 'selected' : '' }}>حرج (خلال 7 أيام)</option>
                    <option value="warning" {{ $selectedStatus == 'warning' ? 'selected' : '' }}>تحذير مبكر (8-30 يوم)</option>
                    <option value="expired" {{ $selectedStatus == 'expired' ? 'selected' : '' }}>منتهي الصلاحية</option>
                    <option value="safe" {{ $selectedStatus == 'safe' ? 'selected' : '' }}>آمن (> 30 يوم)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="erp-btn erp-btn-primary flex-1 justify-center text-xs">
                    <x-icon name="filter" size="14" />
                    <span>تصفية</span>
                </button>
                <a href="{{ route('warehouse.expiry') }}" class="erp-btn erp-btn-secondary text-xs" title="إعادة تعيين">
                    <x-icon name="rotate-ccw" size="14" />
                </a>
            </div>
        </form>
    </div>

    <!-- FEFO Batches Table -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-icon name="alert-triangle" size="16" class="text-amber-500" />
                    <span>جدول أولويات الصرف FEFO (مرتبة بالأقرب انتهاءً أولاً لتفادي الهدر)</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] font-numeric">
                    عدد النتائج: {{ $batches->total() }}
                </span>
            </div>
        </div>

        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم الباتش (FEFO)</th>
                            <th>الصنف والباركود</th>
                            <th>المخزن وظروف التخزين</th>
                            <th>الرصيد المتبقي</th>
                            <th>تكلفة الوحدة والإجمالي</th>
                            <th>تاريخ الصلاحية</th>
                            <th>الأيام المتبقية وحالة FEFO</th>
                            <th class="text-center">إجراء الاستهلاك الذكي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            @php
                                $days = $batch->days_until_expiry;
                                $status = $batch->expiry_status;
                                $badgeClass = match($status) {
                                    'expired' => 'erp-badge-danger font-extrabold',
                                    'critical' => 'erp-badge-danger font-bold animate-pulse',
                                    'warning' => 'erp-badge-warning font-semibold',
                                    'safe' => 'erp-badge-success',
                                    default => 'erp-badge-neutral',
                                };
                                $statusLabel = match($status) {
                                    'expired' => 'منتهي منذ ' . abs($days) . ' يوم',
                                    'critical' => 'حرج: متبقي ' . $days . ' أيام فقط!',
                                    'warning' => 'تحذير: متبقي ' . $days . ' يوماً',
                                    'safe' => 'آمن: متبقي ' . $days . ' يوماً',
                                    default => 'بدون تاريخ صلاحية',
                                };
                            @endphp
                            <tr class="{{ $status === 'critical' ? 'bg-rose-50/30 dark:bg-rose-950/10' : '' }}">
                                <td class="font-numeric font-bold text-xs text-[var(--primary)]">
                                    {{ $batch->batch_number }}
                                </td>
                                <td>
                                    <div class="font-bold text-[var(--text-main)]">{{ $batch->product->name ?? 'صنف' }}</div>
                                    <div class="text-[11px] text-[var(--text-muted)] font-mono flex items-center gap-2 mt-0.5">
                                        <span>SKU: {{ $batch->product->sku ?? '-' }}</span>
                                        @if(!empty($batch->product->barcode))
                                            <span>·</span>
                                            <span class="inline-flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                                <x-icon name="barcode" size="12" />
                                                {{ $batch->product->barcode }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium text-xs">{{ $batch->warehouse->name ?? '-' }}</div>
                                    <span class="text-[10px] text-[var(--text-muted)] inline-flex items-center gap-1">
                                        @if($batch->product?->storage_type === 'chilled')
                                            <x-icon name="snowflake" size="12" class="text-sky-500" />
                                            <span>تبريد (+4°C)</span>
                                        @elseif($batch->product?->storage_type === 'frozen')
                                            <x-icon name="freeze" size="12" class="text-indigo-500" />
                                            <span>تجميد (-18°C)</span>
                                        @else
                                            <x-icon name="package" size="12" class="text-amber-500" />
                                            <span>جاف ومعتدل</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="font-numeric font-extrabold text-sm text-[var(--text-main)]">
                                        {{ number_format($batch->remaining_quantity, 2) }} {{ $batch->product->unit ?? 'كجم' }}
                                    </div>
                                    <span class="text-[10px] text-[var(--text-muted)]">من أصل {{ number_format($batch->initial_quantity, 2) }}</span>
                                </td>
                                <td>
                                    <div class="font-numeric font-bold text-xs">{{ number_format($batch->cost_price, 2) }} ج.م/وحدة</div>
                                    <div class="font-numeric text-[11px] text-[var(--text-muted)] font-semibold">
                                        الإجمالي: {{ number_format($batch->total_value, 2) }} ج.م
                                    </div>
                                </td>
                                <td>
                                    @if($batch->expiry_date)
                                        <div class="font-numeric text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ $batch->expiry_date->format('Y-m-d') }}
                                        </div>
                                        <span class="text-[10px] text-[var(--text-muted)] font-numeric">
                                            إنتاج: {{ $batch->production_date ? $batch->production_date->format('Y-m-d') : '-' }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[var(--text-muted)]">غير مسجل</span>
                                    @endif
                                </td>
                                <td>
                                    @if($batch->expiry_date)
                                        <div class="flex flex-col gap-1 items-start">
                                            <span class="erp-badge {{ $badgeClass }} text-[11px]">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="erp-badge erp-badge-neutral text-xs">غير محدد</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('warehouse.batches.print-label', $batch->id) }}" target="_blank" class="erp-btn erp-btn-secondary text-xs px-2.5 py-1" title="طباعة ملصق باركود وبيانات التشغيلة">
                                            <x-icon name="printer" size="14" class="text-blue-600" />
                                            <span>طباعة ملصق</span>
                                        </a>
                                        <button type="button" class="erp-btn erp-btn-secondary text-xs px-2.5 py-1" data-action="suggest-batch-consumption" data-batch-id="{{ $batch->id }}" title="اقتراح وجبات للمطبخ لاستهلاك هذه التشغيلة فوراً">
                                            <x-icon name="utensils" size="14" class="text-indigo-600" />
                                            <span>وجبات استهلاك</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12 text-[var(--text-muted)] text-xs">
                                    لا توجد تشغيلات تطابق شروط البحث الحالية.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="p-4 border-t border-[var(--border-light)]">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Kitchen Consumption Suggestions for Batch -->
<div id="batchSuggestionModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600">
                    <x-icon name="utensils" size="18" />
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">مقترحات استهلاك التشغيلة بالمطبخ (FEFO Kitchen Yield)</h3>
                    <p class="text-[11px] text-slate-500" id="modalBatchSubtitle">جاري تحميل بيانات الباتش...</p>
                </div>
            </div>
            <button type="button" id="closeModalBtn" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <x-icon name="x" size="18" />
            </button>
        </div>

        <div id="modalLoadingState" class="py-8 text-center text-xs text-slate-500">
            جاري فحص المقننات الغذائية والوصفات المرتبطة بهذا الصنف...
        </div>

        <div id="modalContentState" class="hidden space-y-3">
            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 rounded-xl text-xs flex items-center justify-between">
                <span>الرصيد المتبقي المتاح في هذه التشغيلة:</span>
                <strong class="font-mono text-sm" id="modalRemainingQty">0.00</strong>
            </div>

            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>الوصفة / الوجبة</th>
                            <th>نوع الوجبة</th>
                            <th>المستشفى</th>
                            <th>المقنن للوجبة</th>
                            <th>الوجبات الممكن إعدادها</th>
                        </tr>
                    </thead>
                    <tbody id="modalSuggestionsTbody">
                        <!-- Loaded dynamically via warehouse.js -->
                    </tbody>
                </table>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-primary text-xs">
                    <x-icon name="truck" size="14" />
                    <span>إصدار إذن صرف وتحميل للوجبة الآن</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
