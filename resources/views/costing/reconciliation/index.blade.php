@extends('layouts.app')

@section('title', 'تقرير مطابقة الصرف الفعلي للمطبخ وكشف الهدر والعجز')

@section('control_panel')
    <x-control-panel title="مطابقة أعداد المرضى ومنصرف المطبخ الفعلي وكشف الهدر" :breadcrumbs="['إدارة التكاليف والمقننات' => route('costing.recipes.index'), 'المطابقة اليومية' => route('costing.reconciliation.index')]">
        <x-slot:actions>
            <a href="{{ route('site.census.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calendar" size="16" />
                <span>أعداد وتشغيل الموقع</span>
            </a>
            <a href="{{ route('site.kitchen-issues.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="package" size="16" />
                <span>صرف المخزن الفرعي</span>
            </a>
            <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book-open" size="16" />
                <span>المقننات (BOM)</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($hospital)
                <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                    <span>الموقع المختار:</span>
                    <strong>{{ $hospital->name }}</strong>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <!-- Filter by Hospital and Date -->
    <div class="erp-card p-4">
        <form action="{{ route('costing.reconciliation.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="erp-form-label text-xs">المستشفى / الموقع</label>
                <select name="hospital_id" class="erp-form-control">
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}" {{ $hospital?->id === $hosp->id ? 'selected' : '' }}>
                            {{ $hosp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label text-xs">تاريخ المطابقة</label>
                <input type="date" name="date" class="erp-form-control font-mono text-sm" value="{{ $date }}">
            </div>
            <div>
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="search" size="16" />
                    <span>عرض تقرير المطابقة</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Census Summary Header -->
    @if($census)
        <div class="erp-card p-5 border-r-4 border-r-primary bg-primary/5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="erp-badge erp-badge-success text-xs">بيان معتمد من مدير الموقع</span>
                        <span class="text-xs text-slate-500 font-mono">تاريخ: {{ $census->census_date->format('Y-m-d') }}</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 mt-2">
                        تشغيل الموقع المعتمد:
                        <span class="text-primary">{{ $census->protein_label }}</span> +
                        <span class="text-slate-700 dark:text-slate-300">{{ $census->carb_label }}</span> +
                        <span class="text-slate-700 dark:text-slate-300">{{ $census->veg_label }}</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">
                        مسؤول الإدخال: {{ $census->siteManager?->name ?? 'مدير الموقع' }}
                        @if($census->notes)
                            | ملاحظات: <span class="italic text-slate-600 dark:text-slate-400">"{{ $census->notes }}"</span>
                        @endif
                    </p>
                </div>
                <div class="text-left font-mono">
                    <span class="text-xs text-slate-500 block">إجمالي تعداد النزلاء/العاملين</span>
                    <span class="text-2xl font-bold text-primary">{{ number_format($census->total_patient_count) }}</span>
                    <span class="text-xs text-slate-500">وجبة</span>
                </div>
            </div>
        </div>
    @else
        <div class="erp-alert erp-alert-warning">
            <x-icon name="alert-triangle" size="18" />
            <div>
                <span class="font-bold">تنبيه: لم يقم مدير الموقع بتسجيل تشغيل وأعداد هذا اليوم حتى الآن!</span>
                <p class="text-xs mt-1">يمكن لمدير الموقع الدخول من شاشة <a href="{{ route('site.census.index', ['hospital_id' => $hospital?->id, 'date' => $date]) }}" class="underline font-semibold">أعداد الموقع اليومية</a> لتحديد نوع البروتين وإثبات الأعداد.</p>
            </div>
        </div>
    @endif

    <!-- Financial KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-medium">التكلفة المعيارية المخططة</span>
            <h4 class="text-xl font-bold font-mono text-slate-800 dark:text-slate-100 mt-1">
                {{ number_format($totalStandardCost, 2) }} <span class="text-xs text-slate-500 font-normal">ج.م</span>
            </h4>
            <p class="text-xs text-slate-400 mt-1">بناءً على أعداد النزلاء ومقنن العقد</p>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-medium">التكلفة الفعلية المنصرفة</span>
            <h4 class="text-xl font-bold font-mono text-primary mt-1">
                {{ number_format($totalActualCost, 2) }} <span class="text-xs text-slate-500 font-normal">ج.م</span>
            </h4>
            <p class="text-xs text-slate-400 mt-1">منصرف المخزن الفرعي للمطبخ</p>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-medium">قيمة الهدر وزيادة الاستهلاك</span>
            <h4 class="text-xl font-bold font-mono text-rose-600 mt-1">
                {{ number_format($totalOverWasteCost, 2) }} <span class="text-xs text-slate-500 font-normal">ج.م</span>
            </h4>
            <p class="text-xs text-rose-400 mt-1">تكلفة كميات زائدة عن المعيار</p>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-medium">قيمة العجز والنقص عن المعيار</span>
            <h4 class="text-xl font-bold font-mono text-amber-600 mt-1">
                {{ number_format($totalShortageCost, 2) }} <span class="text-xs text-slate-500 font-normal">ج.م</span>
            </h4>
            <p class="text-xs text-amber-500 mt-1">نقص في حصة المرضى (مخاطرة تفتيش)</p>
        </div>
    </div>

    <!-- Reconciliation Table -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                جدول المطابقة التفصيلي ومؤشرات الهدر والعجز لكل صنف
            </h4>
            <span class="text-xs text-slate-500 font-mono">
                الموقع: {{ $hospital?->name }} | التاريخ: {{ $date }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>الصنف الخام</th>
                        <th>سعر التكلفة</th>
                        <th>المقنن المعياري (المفترض)</th>
                        <th>المنصرف الفعلي للمطبخ</th>
                        <th>الفارق (الانحراف)</th>
                        <th>نسبة الفارق (%)</th>
                        <th>الأثر المالي للانحراف</th>
                        <th>التقييم والقرار</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliationRows as $row)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">
                                {{ $row['product_name'] }}
                            </td>
                            <td class="font-mono text-xs text-slate-500">
                                {{ number_format($row['cost_price'], 2) }} ج.م
                            </td>
                            <td class="font-mono text-slate-700 dark:text-slate-300 font-medium">
                                {{ number_format($row['standard_qty'], 3) }} {{ $row['unit'] }}
                            </td>
                            <td class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($row['actual_qty'], 3) }} {{ $row['unit'] }}
                            </td>
                            <td class="font-mono font-bold {{ $row['variance_qty'] > 0 ? 'text-rose-600' : ($row['variance_qty'] < 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ $row['variance_qty'] > 0 ? '+' : '' }}{{ number_format($row['variance_qty'], 3) }} {{ $row['unit'] }}
                            </td>
                            <td class="font-mono text-xs font-bold">
                                {{ $row['variance_pct'] > 0 ? '+' : '' }}{{ $row['variance_pct'] }}%
                            </td>
                            <td class="font-mono font-bold text-xs {{ $row['variance_cost'] > 0 ? 'text-rose-600' : ($row['variance_cost'] < 0 ? 'text-amber-600' : 'text-slate-500') }}">
                                {{ $row['variance_cost'] > 0 ? '+' : '' }}{{ number_format($row['variance_cost'], 2) }} ج.م
                            </td>
                            <td>
                                @if($row['status'] === 'waste_alert')
                                    <span class="erp-badge erp-badge-danger">
                                        <x-icon name="alert-triangle" size="12" />
                                        <span>هدر وزيادة استهلاك</span>
                                    </span>
                                @elseif($row['status'] === 'shortage_alert')
                                    <span class="erp-badge erp-badge-warning">
                                        <x-icon name="alert-circle" size="12" />
                                        <span>عجز في حصة المرضى</span>
                                    </span>
                                @else
                                    <span class="erp-badge erp-badge-success">
                                        <x-icon name="check" size="12" />
                                        <span>منضبط مع المعيار</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                @if(!$census)
                                    يرجى تسجيل أعداد الموقع أولاً لعرض المقارنة والمطابقة.
                                @else
                                    لا توجد بيانات مطابقة متاحة لهذا اليوم.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
