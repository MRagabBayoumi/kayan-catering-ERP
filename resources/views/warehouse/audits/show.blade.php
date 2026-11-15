@extends('layouts.app')

@section('title', 'محضر مطابقة الجرد')

@section('control_panel')
    <x-control-panel title="محضر مطابقة الجرد رقم [{{ $audit->audit_number }}]" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'جلسات الجرد' => route('warehouse.audits.index'), 'محضر المطابقة' => '#']">
        <x-slot:actions>
            <a href="{{ route('warehouse.audits.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع للقائمة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $audit->status === 'settled' ? 'erp-badge-success' : 'erp-badge-primary' }} font-numeric px-2.5 py-1 text-xs">
                <span>الحالة:</span>
                <strong>{{ $audit->status === 'settled' ? 'تمت التسوية والاعتماد' : 'مقدم بانتظار المطابقة' }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="erp-card">
        <div class="erp-card-header">
            <div>
                <h2 class="erp-card-title">
                    <x-icon name="layers" size="18" class="text-[var(--primary)]" />
                    <span>جدول مقارنة الرصيد الفعلي المجرود بالرصيد الدفتري المسجل في السيستم</span>
                </h2>
                <div class="text-xs text-slate-500 font-medium mt-1">
                    المخزن: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $audit->warehouse->name ?? '-' }}</span> | أمين المخزن: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $audit->storekeeper->name ?? '-' }}</span> | تاريخ الجرد: {{ $audit->audited_at ? $audit->audited_at->format('Y-m-d H:i') : '-' }}
                </div>
            </div>
        </div>

        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>الصنف</th>
                            <th class="text-center">الفعلي على الرف (أمين المخزن)</th>
                            <th class="text-center">الدفتري في السيستم</th>
                            <th class="text-center">الفارق (عجز / زيادة)</th>
                            <th class="text-center">سعر التكلفة</th>
                            <th class="text-center">قيمة الفارق المالي</th>
                            <th>التبرير المسجل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audit->items as $item)
                            <tr>
                                <td class="font-bold">{{ $item->product->name ?? '-' }}</td>
                                <td class="font-mono text-center font-bold text-[var(--primary)]">
                                    {{ number_format($item->physical_count, 2) }} {{ $item->product->unit ?? 'كجم' }}
                                </td>
                                <td class="font-mono text-center text-slate-600 dark:text-slate-400">
                                    {{ number_format($item->system_quantity, 2) }} {{ $item->product->unit ?? 'كجم' }}
                                </td>
                                <td class="font-mono text-center font-extrabold">
                                    @if($item->variance == 0)
                                        <span class="text-emerald-600">0.00 (مطابق 100%)</span>
                                    @elseif($item->variance < 0)
                                        <span class="text-rose-600">{{ number_format($item->variance, 2) }} (عجز)</span>
                                    @else
                                        <span class="text-blue-600">+{{ number_format($item->variance, 2) }} (زيادة)</span>
                                    @endif
                                </td>
                                <td class="font-mono text-center text-slate-600 dark:text-slate-400">{{ number_format($item->unit_cost, 2) }} ج.م</td>
                                <td class="font-mono text-center font-bold">
                                    @if($item->variance_cost == 0)
                                        <span class="text-emerald-600">0.00 ج.م</span>
                                    @elseif($item->variance_cost < 0)
                                        <span class="text-rose-600">{{ number_format($item->variance_cost, 2) }} ج.م</span>
                                    @else
                                        <span class="text-blue-600">+{{ number_format($item->variance_cost, 2) }} ج.م</span>
                                    @endif
                                </td>
                                <td class="text-slate-500 text-[11px]">{{ $item->discrepancy_reason ?: 'لا توجد ملاحظات' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
