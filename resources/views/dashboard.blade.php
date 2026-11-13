@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="منظومة كيان لإعاشة وتغذية المستشفيات" :breadcrumbs="[]">
        <x-slot:actions>
            <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-primary">
                <x-icon name="layers" size="16" />
                <span>تشغيلات الشراء (FIFO)</span>
            </a>
            <a href="{{ route('warehouse.dispatches.create') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>إذن صرف سيارة جديد</span>
            </a>
            <a href="{{ route('costing.daily-order') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calculator" size="16" />
                <span>حساب طلبية المطبخ (المقنن)</span>
            </a>
            <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="building-2" size="16" />
                <span>دليل المستشفيات</span>
            </a>
            <button type="button" data-action="open-ai-copilot" class="erp-btn erp-btn-secondary text-[var(--primary)] font-bold">
                <x-icon name="sparkles" size="16" />
                <span>Kayan AI Copilot</span>
            </button>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="calendar" size="14" />
                <span>{{ date('Y-m-d') }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <!-- Fast Financial & Operational KPI Ribbon (Kayan ERP Standard) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. المستشفيات والجهات المتعاقدة -->
        <a href="{{ route('sales.hospitals.index') }}" class="kpi-card flex flex-col justify-between hover:border-[var(--primary)] transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المستشفيات والجهات المتعاقدة</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="building-2" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-2xl font-black text-[var(--text-main)]">
                {{ number_format($hospitals_count ?? 0) }} <span class="text-xs font-normal text-[var(--text-muted)]">مستشفى وجهة</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)] flex items-center justify-between">
                <span class="text-emerald-600 font-medium">عقود سارية ونشطة</span>
                <span class="text-[var(--primary)] text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>دليل المستشفيات</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>

        <!-- 2. طاقة الوجبات اليومية المجهزة -->
        <a href="{{ route('costing.daily-order') }}" class="kpi-card flex flex-col justify-between hover:border-[var(--primary)] transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label">طاقة الوجبات اليومية المجهزة</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="utensils" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-2xl font-black text-[var(--text-main)]">
                {{ number_format($total_patient_capacity ?? 0) }} <span class="text-xs font-normal text-[var(--text-muted)]">وجبة يومياً</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)] flex items-center justify-between">
                <span>تغذية ونزلاء المستشفيات</span>
                <span class="text-[var(--primary)] text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>حساب المقنن</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>

        <!-- 3. تشغيلات الشراء النشطة (FIFO) -->
        <a href="{{ route('warehouse.index') }}" class="kpi-card flex flex-col justify-between hover:border-[var(--primary)] transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label">تشغيلات الشراء النشطة (FIFO)</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="layers" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-2xl font-black text-[var(--text-main)]">
                {{ number_format($active_batches_count ?? 0) }} <span class="text-xs font-normal text-[var(--text-muted)]">تشغيلة بالمخازن</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)] flex items-center justify-between">
                <span class="text-amber-600 font-medium">مراقبة تواريخ الصلاحية</span>
                <span class="text-[var(--primary)] text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>جدول الباتشات</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>

        <!-- 4. أذون الصرف قيد التوريد -->
        <a href="{{ route('warehouse.dispatches') }}" class="kpi-card flex flex-col justify-between hover:border-[var(--primary)] transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label">أذون الصرف قيد التوريد</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="truck" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-2xl font-black text-[var(--text-main)]">
                {{ number_format($pending_dispatches_count ?? 0) }} <span class="text-xs font-normal text-[var(--text-muted)]">إذن تحميل بالسيارات</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)] flex items-center justify-between">
                <span class="text-indigo-600 font-medium">بعهدة سيارات التوزيع</span>
                <span class="text-[var(--primary)] text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>متابعة النقل</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>
    </div>

    <!-- Two Main Operational Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- 1. أحدث أذون التحميل والتوريد للمستشفيات -->
        <div class="erp-card mb-0">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="truck" size="16" class="text-[var(--primary)]" />
                    <span>أحدث أذون التحميل والتوريد للمستشفيات</span>
                </div>
                <a href="{{ route('warehouse.dispatches') }}" class="erp-btn erp-btn-secondary text-xs">
                    <span>عرض الكل</span>
                    <x-icon name="arrow-left" size="12" />
                </a>
            </div>
            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم الإذن</th>
                                <th>المستشفى / الوجهة</th>
                                <th>مندوب التوزيع</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_dispatches ?? [] as $dsp)
                                <tr>
                                    <td class="font-numeric font-bold text-[var(--primary)]">{{ $dsp->dispatch_number }}</td>
                                    <td class="font-semibold">{{ $dsp->hospital->name ?? 'مستشفى' }}</td>
                                    <td>{{ $dsp->driver->name ?? 'غير محدد' }}</td>
                                    <td>
                                        @if($dsp->status === 'delivered')
                                            <span class="erp-badge erp-badge-success">تم التسليم</span>
                                        @else
                                            <span class="erp-badge erp-badge-warning">في الطريق</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-[var(--text-muted)] text-xs">
                                        لا توجد أذون تحميل مسجلة حالياً
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. تنبيه الباتشات الأقرب انتهاءً (الأولوية للصرف الفوري) -->
        <div class="erp-card mb-0">
            <div class="erp-card-header">
                <div class="erp-card-title text-[var(--danger)]">
                    <x-icon name="alert-triangle" size="16" class="text-[var(--danger)]" />
                    <span>تنبيه الباتشات الأقرب انتهاءً (أولوية الصرف الفوري FIFO)</span>
                </div>
                <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-secondary text-xs">
                    <span>جدول الصلاحيات</span>
                    <x-icon name="arrow-left" size="12" />
                </a>
            </div>
            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم التشغيلة</th>
                                <th>الصنف الغذائي</th>
                                <th>الكمية المتبقية</th>
                                <th>تاريخ الصلاحية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiring_soon_batches ?? [] as $b)
                                <tr>
                                    <td class="font-numeric font-semibold text-xs">{{ $b->batch_number }}</td>
                                    <td class="font-bold text-[var(--text-main)]">{{ $b->product->name ?? 'صنف' }}</td>
                                    <td class="font-numeric">{{ number_format($b->remaining_quantity, 2) }} {{ $b->product->unit ?? 'كجم' }}</td>
                                    <td>
                                        <span class="erp-badge erp-badge-danger font-numeric">
                                            {{ $b->expiry_date }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-emerald-600 text-xs font-semibold">
                                        جميع الباتشات بحالة ممتازة ولا توجد أصناف قريبة الانتهاء
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
