@extends('layouts.app')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6" dir="rtl">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full text-xs font-semibold {{ $audit->status === 'settled' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                <span>الحالة:</span>
                <span>{{ $audit->status === 'settled' ? 'تمت التسوية والاعتماد' : 'مقدم بانتظار المطابقة' }}</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white mt-1">
                محضر مطابقة الجرد رقم [{{ $audit->audit_number }}]
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                المخزن: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $audit->warehouse->name ?? '-' }}</span> | أمين المخزن: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $audit->storekeeper->name ?? '-' }}</span> | تاريخ الجرد: {{ $audit->audited_at ? $audit->audited_at->format('Y-m-d H:i') : '-' }}
            </p>
        </div>
        <a href="{{ route('warehouse.audits.index') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition">
            رجوع للقائمة
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Reconciliation Table (Auditor & Accountant View) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-700 dark:text-slate-300">
                جدول مقارنة الرصيد الفعلي المجرود بالرصيد الدفتري المسجل في السيستم
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 font-bold">
                    <tr>
                        <th class="p-3.5">الصنف</th>
                        <th class="p-3.5 text-center">الفعلي على الرف (أمين المخزن)</th>
                        <th class="p-3.5 text-center">الدفتري في السيستم</th>
                        <th class="p-3.5 text-center">الفارق (عجز / زيادة)</th>
                        <th class="p-3.5 text-center">سعر التكلفة</th>
                        <th class="p-3.5 text-center">قيمة الفارق المالي</th>
                        <th class="p-3.5">التبرير المسجل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($audit->items as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">{{ $item->product->name ?? '-' }}</td>
                            <td class="p-3.5 font-mono text-center font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($item->physical_count, 2) }} {{ $item->product->unit ?? 'كجم' }}
                            </td>
                            <td class="p-3.5 font-mono text-center text-slate-600 dark:text-slate-400">
                                {{ number_format($item->system_quantity, 2) }} {{ $item->product->unit ?? 'كجم' }}
                            </td>
                            <td class="p-3.5 font-mono text-center font-extrabold">
                                @if($item->variance == 0)
                                    <span class="text-emerald-600">0.00 (مطابق 100%)</span>
                                @elseif($item->variance < 0)
                                    <span class="text-rose-600">{{ number_format($item->variance, 2) }} (عجز)</span>
                                @else
                                    <span class="text-blue-600">+{{ number_format($item->variance, 2) }} (زيادة)</span>
                                @endif
                            </td>
                            <td class="p-3.5 font-mono text-center text-slate-600 dark:text-slate-400">{{ number_format($item->unit_cost, 2) }} ج.م</td>
                            <td class="p-3.5 font-mono text-center font-bold">
                                @if($item->variance_cost == 0)
                                    <span class="text-emerald-600">0.00 ج.م</span>
                                @elseif($item->variance_cost < 0)
                                    <span class="text-rose-600">{{ number_format($item->variance_cost, 2) }} ج.م</span>
                                @else
                                    <span class="text-blue-600">+{{ number_format($item->variance_cost, 2) }} ج.م</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-500 text-[11px]">{{ $item->discrepancy_reason ?: 'لا توجد ملاحظات' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
