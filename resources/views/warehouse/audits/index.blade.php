@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="محاضر الجرد اليومي الأعمى (Blind Stock Audits)" :breadcrumbs="['إدارة المخازن والتشغيلات' => route('warehouse.index'), 'الجرد الأعمى' => route('warehouse.audits.index')]">
        <x-slot:actions>
            @can('perform-blind-audit')
            <a href="{{ route('warehouse.audits.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>بدء جلسة جرد أعمى جديدة</span>
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
                <span>إجمالي المحاضر:</span>
                <strong>{{ count($audits) }}</strong>
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
                <span class="kpi-label">جلسات الجرد المنفذة</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="clipboard-check" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ count($audits) }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">محاضر جرد مخزني فعلي</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">حماية الرصيد الدفتري</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="shield" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base font-bold text-emerald-600">Blind Stocktake</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">إخفاء الأرصدة عن أمين المخزن</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">جلسات قيد المراجعة</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="alert-triangle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-amber-600">
                {{ collect($audits)->where('status', 'submitted')->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">بانتظار مطابقة المحاسب</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">جلسات تمت تسويتها</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-sky-600">
                {{ collect($audits)->where('status', 'settled')->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مطابقة دقيقة بدون فروقات</div>
        </div>
    </div>

    <!-- Audits Table Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="clipboard-check" size="16" class="text-[var(--primary)]" />
                <span>سجل محاضر الجرد الأعمى لمطابقة الأغذية والخامات المخزنة</span>
            </div>
            @can('perform-blind-audit')
            <a href="{{ route('warehouse.audits.create') }}" class="erp-btn erp-btn-secondary text-xs">
                <x-icon name="plus" size="12" />
                <span>جلسة جرد</span>
            </a>
            @endcan
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم المحضر</th>
                            <th>المخزن المجرود</th>
                            <th>أمين المخزن القائم بالعد</th>
                            <th>تاريخ ووقت الجرد</th>
                            <th>الحالة والاعتماد</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)]">{{ $audit->audit_number }}</td>
                                <td class="font-bold text-[var(--text-main)]">{{ $audit->warehouse->name ?? '-' }}</td>
                                <td>{{ $audit->storekeeper->name ?? '-' }}</td>
                                <td class="font-numeric text-xs">{{ $audit->audited_at ? $audit->audited_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>
                                    @if($audit->status === 'settled')
                                        <span class="erp-badge erp-badge-success">تمت التسوية</span>
                                    @elseif($audit->status === 'reviewed')
                                        <span class="erp-badge erp-badge-info">راجعها المحاسب</span>
                                    @else
                                        <span class="erp-badge erp-badge-warning">بانتظار المطابقة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('warehouse.audits.show', $audit->id) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-2.5">
                                        <x-icon name="eye" size="12" />
                                        <span>عرض المحضر</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-[var(--text-muted)] text-xs">
                                    لا توجد محاضر جرد مسجلة حالياً
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
