@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" dir="rtl">
    <!-- Top Action Header -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>📋</span>
                <span>محاضر الجرد اليومي الأعمى (Blind Stock Audits)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                نظام الجرد المحمي الذي يمنع التلاعب عبر إخفاء الرصيد الدفتري برمجياً عن أمين المخزن أثناء العد الفعلي.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @can('perform-blind-audit')
            <a href="{{ route('warehouse.audits.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs rounded-xl shadow-sm transition flex items-center gap-2">
                <span>➕</span>
                <span>بدء جلسة جرد أعمى جديدة</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Audits Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3.5">رقم المحضر</th>
                        <th class="p-3.5">المخزن المجرود</th>
                        <th class="p-3.5">أمين المخزن القائم بالعد</th>
                        <th class="p-3.5">تاريخ ووقت الجرد</th>
                        <th class="p-3.5">الحالة</th>
                        <th class="p-3.5 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($audits as $audit)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                            <td class="p-3.5 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $audit->audit_number }}</td>
                            <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">{{ $audit->warehouse->name ?? '-' }}</td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-400">{{ $audit->storekeeper->name ?? '-' }}</td>
                            <td class="p-3.5 font-mono text-slate-600 dark:text-slate-400">{{ $audit->audited_at ? $audit->audited_at->format('Y-m-d H:i') : '-' }}</td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium {{ $audit->status === 'settled' ? 'bg-emerald-100 text-emerald-800' : ($audit->status === 'reviewed' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $audit->status === 'settled' ? 'تمت التسوية' : ($audit->status === 'reviewed' ? 'راجعها المحاسب' : 'مقدمة بانتظار المطابقة') }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                <a href="{{ route('warehouse.audits.show', $audit->id) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition inline-block">
                                    عرض ومطابقة الفوارق
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">لا توجد محاضر جرد مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $audits->links() }}
        </div>
    </div>
</div>
@endsection
