@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="طلب شراء رقم: {{ $pr->pr_number }}" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'طلبات الشراء' => route('procurement.requests.index'), $pr->pr_number => '#']">
        <x-slot:actions>
            <a href="{{ route('procurement.requests.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للطلبات</span>
            </a>
            @if($pr->status === 'approved')
                <a href="{{ route('procurement.requests.compare', $pr->id) }}" class="erp-btn erp-btn-primary">
                    <x-icon name="trending-up" size="16" />
                    <span>مقارنة عروض الأسعار واختيار المورد</span>
                </a>
            @endif
        </x-slot:actions>

        <x-slot:extra>
            @if($pr->status === 'pending_approval')
                <span class="erp-badge erp-badge-warning">قيد مراجعة الإدارة</span>
            @elseif($pr->status === 'approved')
                <span class="erp-badge erp-badge-success">معتمد رسمياً</span>
            @elseif($pr->status === 'po_created')
                <span class="erp-badge erp-badge-primary">تم إصدار أمر الشراء (PO)</span>
            @elseif($pr->status === 'rejected')
                <span class="erp-badge erp-badge-danger">مرفوض</span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" dir="rtl">
    @if(session('message'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Request Overview Card -->
    <div class="erp-card p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-4 border-b border-slate-100">
            <div>
                <span class="text-xs text-slate-500 block">تاريخ الطلب</span>
                <span class="font-numeric font-bold text-slate-800">{{ $pr->request_date?->format('Y-m-d') }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">مطلوب قبل</span>
                <span class="font-numeric font-bold text-rose-600">{{ $pr->required_by_date?->format('Y-m-d') }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">المخزن المستلم</span>
                <span class="font-bold text-slate-800">{{ $pr->warehouse->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">طالب الشراء</span>
                <span class="font-bold text-slate-800">{{ $pr->requester->name ?? 'النظام الآلي' }}</span>
            </div>
        </div>

        <div class="mt-4">
            <span class="text-xs text-slate-500 block">سبب ودافع الشراء</span>
            <p class="text-sm font-medium text-slate-700 mt-1">{{ $pr->reason }}</p>
        </div>

        @if($pr->notes)
            <div class="mt-3 text-xs text-slate-500 bg-slate-50 p-2.5 rounded">
                ملاحظات: {{ $pr->notes }}
            </div>
        @endif

        @if($pr->status === 'pending_approval')
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-4">
                <span class="text-sm font-medium text-amber-800">يتطلب هذا الطلب موافقة المدير المالي أو مدير المشتريات قبل اعتماده.</span>
                <div class="flex items-center gap-3">
                    <form action="{{ route('procurement.requests.reject', $pr->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="erp-btn erp-btn-secondary text-rose-600 hover:bg-rose-50">
                            <x-icon name="x" size="16" />
                            <span>رفض الطلب</span>
                        </button>
                    </form>
                    <form action="{{ route('procurement.requests.approve', $pr->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="erp-btn erp-btn-primary">
                            <x-icon name="check" size="16" />
                            <span>اعتماد طلب الشراء</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <div class="erp-card overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <h4 class="font-bold text-slate-800 text-sm">الأصناف والخامات المطلوبة</h4>
            <span class="text-xs text-slate-500">{{ $pr->items->count() }} صنف مسجل</span>
        </div>
        <table class="erp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الصنف</th>
                    <th>اسم الصنف</th>
                    <th>الكمية المطلوبة</th>
                    <th>الوحدة</th>
                    <th>التكلفة التقديرية</th>
                    <th>الإجمالي التقديري</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pr->items as $idx => $item)
                    <tr>
                        <td class="font-numeric">{{ $idx + 1 }}</td>
                        <td class="font-mono text-xs">{{ $item->product->sku ?? '-' }}</td>
                        <td class="font-bold text-slate-800">{{ $item->product->name }}</td>
                        <td class="font-numeric font-bold text-sky-700">{{ number_format($item->quantity_requested, 2) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="font-numeric">{{ number_format($item->estimated_unit_cost, 2) }} ج.م</td>
                        <td class="font-numeric font-bold">{{ number_format($item->quantity_requested * $item->estimated_unit_cost, 2) }} ج.م</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($pr->purchaseOrder)
        <!-- Linked Purchase Order Card -->
        <div class="erp-card p-5 border border-sky-200 bg-sky-50/40">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs text-sky-700 block font-semibold">أمر الشراء الصادر بناء على هذا الطلب</span>
                    <h4 class="text-lg font-bold text-slate-900 font-mono mt-0.5">{{ $pr->purchaseOrder->po_number }}</h4>
                    <span class="text-xs text-slate-600 mt-1 block">
                        المورد: <strong>{{ $pr->purchaseOrder->supplier->name }}</strong> • 
                        الإجمالي: <strong class="font-numeric">{{ number_format($pr->purchaseOrder->total_amount, 2) }} ج.م</strong>
                    </span>
                </div>
                <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-primary">
                    <x-icon name="arrow-left" size="16" />
                    <span>متابعة أمر الشراء</span>
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
