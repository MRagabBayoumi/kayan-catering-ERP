@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="طلبات الشراء واحتياجات التموين (Purchase Requests)" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'طلبات الشراء' => route('procurement.requests.index')]">
        <x-slot:actions>
            <a href="{{ route('procurement.requests.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إعداد طلب شراء جديد</span>
            </a>
            <a href="{{ route('costing.mrp.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="cpu" size="16" />
                <span>تخطيط الاحتياجات (MRP)</span>
            </a>
            <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-bag" size="16" />
                <span>أوامر الشراء المعتمدة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الطلبات:</span>
                <strong>{{ $purchaseRequests->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $procurementTabs = [
            [
                'label' => 'طلبات الشراء والاحتياجات',
                'url' => route('procurement.requests.index'),
                'icon' => 'file-text',
                'active' => request()->routeIs('procurement.requests*'),
                'badge' => $purchaseRequests->total(),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'أوامر الشراء والتوريد',
                'url' => route('procurement.orders.index'),
                'icon' => 'shopping-bag',
                'active' => request()->routeIs('procurement.orders*'),
            ],
            [
                'label' => 'فحص واستلام التوريدات (GRN)',
                'url' => route('procurement.receipts.index'),
                'icon' => 'clipboard-check',
                'active' => request()->routeIs('procurement.receipts*'),
            ],
            [
                'label' => 'سجل الموردين المعتمدين',
                'url' => route('procurement.suppliers.index'),
                'icon' => 'truck',
                'active' => request()->routeIs('procurement.suppliers*'),
            ],
        ];
    @endphp

    <x-module-nav :tabs="$procurementTabs" />

    @if(session('message'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Status Filter Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('procurement.requests.index') }}" class="erp-btn {{ !$selectedStatus ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
            الكل ({{ $purchaseRequests->total() }})
        </a>
        <a href="{{ route('procurement.requests.index', ['status' => 'pending_approval']) }}" class="erp-btn {{ $selectedStatus === 'pending_approval' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
            قيد الاعتماد
        </a>
        <a href="{{ route('procurement.requests.index', ['status' => 'approved']) }}" class="erp-btn {{ $selectedStatus === 'approved' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
            معتمدة (جاهزة للتسعير)
        </a>
        <a href="{{ route('procurement.requests.index', ['status' => 'po_created']) }}" class="erp-btn {{ $selectedStatus === 'po_created' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
            صدر بها أمر شراء
        </a>
        <a href="{{ route('procurement.requests.index', ['status' => 'rejected']) }}" class="erp-btn {{ $selectedStatus === 'rejected' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs">
            مرفوضة
        </a>
    </div>

    <!-- Requests Table -->
    <div class="erp-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>تاريخ الطلب</th>
                        <th>مطلوب قبل</th>
                        <th>المخزن المستلم</th>
                        <th>طالب الشراء</th>
                        <th>عدد الأصناف</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseRequests as $pr)
                        <tr>
                            <td>
                                <a href="{{ route('procurement.requests.show', $pr->id) }}" class="font-mono font-bold text-sky-600 hover:underline">
                                    {{ $pr->pr_number }}
                                </a>
                            </td>
                            <td class="font-numeric">{{ $pr->request_date?->format('Y-m-d') }}</td>
                            <td class="font-numeric">{{ $pr->required_by_date?->format('Y-m-d') }}</td>
                            <td>{{ $pr->warehouse->name ?? '-' }}</td>
                            <td>{{ $pr->requester->name ?? 'النظام الآلي' }}</td>
                            <td class="font-numeric font-semibold">{{ $pr->items->count() }} صنف</td>
                            <td>
                                @if($pr->status === 'pending_approval')
                                    <span class="erp-badge erp-badge-warning">قيد الاعتماد</span>
                                @elseif($pr->status === 'approved')
                                    <span class="erp-badge erp-badge-success">معتمد</span>
                                @elseif($pr->status === 'po_created')
                                    <span class="erp-badge erp-badge-primary">صدر أمر شراء</span>
                                @elseif($pr->status === 'rejected')
                                    <span class="erp-badge erp-badge-danger">مرفوض</span>
                                @else
                                    <span class="erp-badge erp-badge-neutral">{{ $pr->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('procurement.requests.show', $pr->id) }}" class="erp-btn erp-btn-secondary text-xs px-2.5 py-1">
                                        <x-icon name="eye" size="14" />
                                        <span>عرض</span>
                                    </a>
                                    @if($pr->status === 'approved')
                                        <a href="{{ route('procurement.requests.compare', $pr->id) }}" class="erp-btn erp-btn-primary text-xs px-2.5 py-1">
                                            <x-icon name="trending-up" size="14" />
                                            <span>مقارنة الأسعار وإصدار PO</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد طلبات شراء مسجلة حالياً
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseRequests->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $purchaseRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
