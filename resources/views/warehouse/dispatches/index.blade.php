@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" dir="rtl">
    <!-- Top Action Header -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>🚚</span>
                <span>أذون التحميل والتوريد لسيارات المناديب</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                سجل أذون خروج البضاعة من المخزن الرئيسي إلى عهدة سيارات التوريد متضمنة نسبة الأمان (Buffer).
            </p>
        </div>
        <div class="flex items-center gap-3">
            @can('create-dispatch')
            <a href="{{ route('warehouse.dispatches.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs rounded-xl shadow-sm transition flex items-center gap-2">
                <span>➕</span>
                <span>إصدار إذن تحميل جديد</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Dispatches Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3.5">رقم الإذن</th>
                        <th class="p-3.5">المستشفى المستهدفة</th>
                        <th class="p-3.5">المخزن المصدر</th>
                        <th class="p-3.5">سيارة المندوب (المخزن المتنقل)</th>
                        <th class="p-3.5">تاريخ ووقت الخروج</th>
                        <th class="p-3.5">الحالة</th>
                        <th class="p-3.5 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($dispatches as $dsp)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="p-3.5 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $dsp->dispatch_number }}</td>
                            <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">{{ $dsp->hospital->name ?? '-' }}</td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $dsp->sourceWarehouse->name ?? '-' }}</td>
                            <td class="p-3.5">
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block">{{ $dsp->transitWarehouse->name ?? '-' }}</span>
                                <span class="text-[11px] text-slate-400">السائق: {{ $dsp->driver->name ?? '-' }}</span>
                            </td>
                            <td class="p-3.5 font-mono text-slate-600 dark:text-slate-400">{{ $dsp->dispatch_date ? $dsp->dispatch_date->format('Y-m-d H:i') : '-' }}</td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium {{ $dsp->status === 'settled' ? 'bg-emerald-100 text-emerald-800' : ($dsp->status === 'delivered' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $dsp->status === 'settled' ? 'تمت التسوية والمطابقة' : ($dsp->status === 'delivered' ? 'تم التسليم بالموقع' : 'في الطريق بالسيارة') }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                @if($dsp->status !== 'settled')
                                    @can('manage-settlements')
                                    <a href="{{ route('warehouse.settlements.create', ['dispatch_id' => $dsp->id]) }}" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-sm transition inline-block">
                                        اعتماد التسوية اليومية
                                    </a>
                                    @endcan
                                @else
                                    <span class="text-xs text-slate-400 font-semibold">مغلق ومسوى</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">لا توجد أذون تحميل مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $dispatches->links() }}
        </div>
    </div>
</div>
@endsection
