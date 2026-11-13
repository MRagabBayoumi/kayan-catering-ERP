@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" dir="rtl">
    <!-- Top Action Header -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>📦</span>
                <span>إدارة المخازن وتشغيلات الـ FIFO</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                متابعة أرصدة المواد الغذائية، تواريخ الصلاحية، وتشغيلات الشراء بنظام الوارد أولاً يصرف أولاً.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs rounded-xl shadow-sm transition flex items-center gap-2">
                <span>🚚</span>
                <span>إصدار إذن تحميل لسيارة مندوب</span>
            </a>
            @endcan

            @can('perform-blind-audit')
            <a href="{{ route('warehouse.audits.create') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs rounded-xl shadow-sm transition flex items-center gap-2">
                <span>📝</span>
                <span>جلسة جرد أعمى للمخزن</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Filter by Warehouse Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <a href="{{ route('warehouse.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ !$selectedWarehouseId ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}">
            كافة المخازن
        </a>
        @foreach($warehouses as $wh)
            <a href="{{ route('warehouse.index', ['warehouse_id' => $wh->id]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $selectedWarehouseId == $wh->id ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}">
                {{ $wh->name }}
            </a>
        @endforeach
    </div>

    <!-- Active FIFO Batches Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-800 dark:text-white flex items-center gap-2">
                <span>📑</span>
                <span>سجل الباتشات النشطة (مرتبة بالأقدم تاريخاً للصرف الفوري)</span>
            </h3>
            <span class="text-xs text-slate-400">إجمالي الباتشات المعروضة: {{ $batches->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3.5">رقم الباتش</th>
                        <th class="p-3.5">الصنف</th>
                        <th class="p-3.5">المخزن الحالي</th>
                        <th class="p-3.5">تاريخ الدخول (FIFO)</th>
                        <th class="p-3.5">تاريخ انتهاء الصلاحية</th>
                        <th class="p-3.5">سعر التكلفة</th>
                        <th class="p-3.5">الكمية المتبقية</th>
                        <th class="p-3.5">أولوية الصرف</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="p-3.5 font-mono font-semibold text-blue-600 dark:text-blue-400">{{ $batch->batch_number }}</td>
                            <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">{{ $batch->product->name ?? 'غير محدد' }}</td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $batch->warehouse->name ?? 'غير محدد' }}</td>
                            <td class="p-3.5 font-mono text-slate-600 dark:text-slate-400">{{ $batch->received_at ? $batch->received_at->format('Y-m-d') : '-' }}</td>
                            <td class="p-3.5 font-mono">
                                @if($batch->expiry_date)
                                    <span class="px-2 py-0.5 rounded font-bold text-xs {{ \Carbon\Carbon::parse($batch->expiry_date)->isPast() ? 'bg-rose-100 text-rose-800' : (\Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now()) <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                        {{ $batch->expiry_date }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3.5 font-mono font-bold text-slate-800 dark:text-slate-200">{{ number_format($batch->cost_price, 2) }} ج.م</td>
                            <td class="p-3.5 font-mono font-extrabold text-blue-700 dark:text-blue-300">
                                {{ number_format($batch->remaining_quantity, 2) }} {{ $batch->product->unit ?? 'كجم' }}
                            </td>
                            <td class="p-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    جاهز للصرف الأول
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">لا توجد تشغيلات شراء مسجلة حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $batches->links() }}
        </div>
    </div>
</div>
@endsection
