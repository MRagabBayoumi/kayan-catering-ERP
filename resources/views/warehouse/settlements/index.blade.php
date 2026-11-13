@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" dir="rtl">
    <!-- Top Action Header -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>💰</span>
                <span>تسويات رحلات المناديب ومخرجات الـ ERP</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                سجل تسوية عهد سيارات التوريد اليومية ومطابقة إذن التحميل مع إشعار استلام المستشفى واحتساب تكلفة الـ FIFO.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Settlements Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3.5">رقم التسوية</th>
                        <th class="p-3.5">المستشفى</th>
                        <th class="p-3.5">السائق / المندوب</th>
                        <th class="p-3.5">محاسب المخزن</th>
                        <th class="p-3.5 text-center">المحمل بالسيارة</th>
                        <th class="p-3.5 text-center">المسلم للمستشفى</th>
                        <th class="p-3.5 text-center">المرتجع للمخزن</th>
                        <th class="p-3.5 text-center">تكلفة الـ FIFO المسلمة</th>
                        <th class="p-3.5 text-center">رقم قيد الـ ERP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($settlements as $st)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="p-3.5 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $st->settlement_number }}</td>
                            <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">{{ $st->dispatchOrder->hospital->name ?? '-' }}</td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $st->driver->name ?? '-' }}</td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $st->accountant->name ?? '-' }}</td>
                            <td class="p-3.5 font-mono text-center font-bold text-slate-700 dark:text-slate-300">{{ number_format($st->total_dispatched_qty, 2) }}</td>
                            <td class="p-3.5 font-mono text-center font-bold text-emerald-600">{{ number_format($st->total_delivered_qty, 2) }}</td>
                            <td class="p-3.5 font-mono text-center font-bold text-blue-600">{{ number_format($st->total_returned_qty, 2) }}</td>
                            <td class="p-3.5 font-mono text-center font-extrabold text-slate-900 dark:text-white">{{ number_format($st->total_cogs_cost, 2) }} ج.م</td>
                            <td class="p-3.5 font-mono text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                                    {{ $st->erp_journal_voucher }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">لا توجد تسويات مسجلة حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $settlements->links() }}
        </div>
    </div>
</div>
@endsection
