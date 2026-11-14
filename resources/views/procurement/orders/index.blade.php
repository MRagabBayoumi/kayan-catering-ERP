@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="طلبات الشراء وعروض الأسعار" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'طلبات الشراء' => route('procurement.orders.index')]">
        <x-slot:actions>
            <a href="{{ route('procurement.orders.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>طلب شراء جديد</span>
            </a>
            <a href="{{ route('procurement.suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="factory" size="16" />
                <span>دليل وتكويد الموردين</span>
            </a>
            <a href="{{ route('warehouse.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>مراجعة نواقص الباتشات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الطلبات:</span>
                <strong>{{ $orders->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @if(session('status') === 'success')
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Fast Status KPI Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">طلبات الشراء النشطة</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="shopping-bag" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $orders->total() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">احتياجات تم رفعها للمشتريات</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">عروض الأسعار المقارنة</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="file-text" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $suppliers->count() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">موردين مسجلين للمفاضلة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">بانتظار موافقة صاحب العمل</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="alert-triangle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-amber-600">3</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">طلبات توريد قيد الاعتماد</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">الأصناف المكودة</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="package" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $products->count() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">أغذية ولحوم معتمدة</div>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="shopping-bag" size="16" class="text-[var(--primary)]" />
                <span>سجل طلبات الشراء وأوامر التوريد المعتمدة</span>
            </div>
            <a href="{{ route('procurement.orders.create') }}" class="erp-btn erp-btn-secondary text-xs">
                <x-icon name="plus" size="12" />
                <span>طلب جديد</span>
            </a>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>تاريخ الطلب</th>
                            <th>المستشفى / الموقع المطلوب له</th>
                            <th>القيمة التقديرية</th>
                            <th>حالة الاعتماد</th>
                            <th>ملاحظات وتفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="font-numeric font-bold text-[var(--primary)]">{{ $order->order_number }}</td>
                                <td class="font-numeric text-xs">{{ $order->order_date?->format('Y-m-d') ?? $order->created_at->format('Y-m-d') }}</td>
                                <td class="font-semibold">{{ $order->hospital->name ?? 'طلب مخزن مركزي' }}</td>
                                <td class="font-numeric font-bold">{{ number_format($order->total_amount, 2) }} ج.م</td>
                                <td>
                                    @if($order->status === 'approved' || $order->status === 'completed')
                                        <span class="erp-badge erp-badge-success">معتمد من صاحب العمل</span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="erp-badge erp-badge-danger">مرفوض</span>
                                    @else
                                        <span class="erp-badge erp-badge-warning">قيد دراسة الأسعار</span>
                                    @endif
                                </td>
                                <td class="text-xs text-[var(--text-muted)]">{{ $order->notes ?? 'طلب شراء توريد أغذية' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-[var(--text-muted)] text-xs">
                                    لا توجد طلبات شراء مسجلة حالياً
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
