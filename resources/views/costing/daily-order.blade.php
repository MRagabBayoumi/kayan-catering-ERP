@extends('layouts.app')

@section('title', 'حساب طلبيات المطبخ والمقننات')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">حاسبة طلبيات المطبخ بناء على عدد النزلاء والمقنن</h1>
        <p class="text-xs text-slate-500 mt-1">حساب كميات الخامات المطلوبة صرفها من المخزن للمطبخ (إعداد الوجبات = عدد النزلاء × مقنن الوجبة)</p>
    </div>

    <!-- Filter & Calculator Input Form -->
    <div class="erp-card p-6">
        <form action="{{ route('costing.daily-order') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="erp-form-label">المستشفى / الموقع</label>
                <select name="hospital_id" class="erp-form-control">
                    <option value="">-- اختر المستشفى --</option>
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}" {{ request('hospital_id') == $hosp->id ? 'selected' : '' }}>
                            {{ $hosp->name }} (سعة: {{ $hosp->daily_patient_capacity }} سرير)
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">المقنن الغذائي للوجبة</label>
                <select name="recipe_id" class="erp-form-control" required>
                    <option value="">-- اختر الوجبة --</option>
                    @foreach($recipes as $rcp)
                        <option value="{{ $rcp->id }}" {{ request('recipe_id') == $rcp->id ? 'selected' : '' }}>
                            {{ $rcp->name }} ({{ $rcp->mealType?->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">عدد الوجبات المطلوب إعدادها</label>
                <input type="number" name="patient_count" class="erp-form-control" placeholder="مثال: 450" required value="{{ request('patient_count', 100) }}" min="1">
            </div>
            <div class="flex items-end">
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="calculator" size="16" />
                    <span>حساب كميات الخامات</span>
                </button>
            </div>
        </form>
    </div>

    @if(!empty($requirements))
        <div class="erp-card">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100">
                        الخامات المطلوبة لإعداد {{ number_format($patientCount) }} وجبة
                    </h3>
                    <p class="text-xs text-slate-500">الوجبة: {{ $selectedRecipe?->name }}</p>
                </div>
                <div class="text-left font-mono font-bold text-primary">
                    التكلفة التقديرية الإجمالية: {{ number_format(($selectedRecipe?->standard_cost_per_meal ?? 0) * $patientCount, 2) }} ج.م
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>الصنف الخام</th>
                            <th>المقنن الفردي للوجبة</th>
                            <th>الكمية الإجمالية المطلوبة</th>
                            <th>الوحدة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requirements as $item)
                            <tr>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $item['product_name'] }}</td>
                                <td class="font-mono text-slate-600 dark:text-slate-400">{{ $item['raw_portion_per_meal'] }}</td>
                                <td class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
                                    {{ number_format($item['total_standard_qty'], 3) }}
                                </td>
                                <td><span class="erp-badge erp-badge-info">{{ $item['unit'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
