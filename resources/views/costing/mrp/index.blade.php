@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="محرك تخطيط الاحتياجات التموينية (Material Requirement Planning - MRP)" :breadcrumbs="['التكاليف والمقننات' => route('costing.recipes.index'), 'تخطيط الاحتياجات MRP' => route('costing.mrp.index')]">
        <x-slot:actions>
            <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book-open" size="16" />
                <span>دليل المقننات القياسية</span>
            </a>
            @if($mrpResult && $selectedRecipeId)
                <form action="{{ route('costing.mrp.create-pr') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="recipe_id" value="{{ $selectedRecipeId }}" />
                    <input type="hidden" name="meals_count" value="{{ $mealsCount }}" />
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="shopping-cart" size="16" />
                        <span>إصدار طلب شراء بالعجز (PR)</span>
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <!-- MRP Parameters Card -->
    <div class="erp-card p-5">
        <form method="GET" action="{{ route('costing.mrp.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div class="sm:col-span-2">
                <label class="erp-label">الوجبة القياسية المستهدفة</label>
                <select name="recipe_id" class="erp-input">
                    @foreach($recipes as $rcp)
                        <option value="{{ $rcp->id }}" {{ $selectedRecipeId == $rcp->id ? 'selected' : '' }}>
                            {{ $rcp->name }} ({{ $rcp->recipe_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">عدد الوجبات المطلوب تجهيزها</label>
                <input type="number" name="meals_count" class="erp-input font-numeric" value="{{ $mealsCount }}" min="1" required />
            </div>
            <div>
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="cpu" size="16" />
                    <span>حساب احتياج الخامات (MRP)</span>
                </button>
            </div>
        </form>
    </div>

    @if($mrpResult)
        <!-- Calculation Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="kpi-card">
                <div class="kpi-title">إجمالي الوجبات المخططة</div>
                <div class="kpi-value font-numeric text-sky-700">{{ number_format($mrpResult['meals_count']) }}</div>
                <div class="kpi-subtext">وجبة نزلاء / طاقم طبي</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">إجمالي التكلفة التقديرية للخامات</div>
                <div class="kpi-value font-numeric text-emerald-700">{{ number_format($mrpResult['total_cost'], 2) }} ج.م</div>
                <div class="kpi-subtext">تكلفة الخامات قبل أجور التشغيل</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">تكلفة الوجبة التقديرية بعد معامل التشفية</div>
                <div class="kpi-value font-numeric text-indigo-700">{{ number_format($mrpResult['cost_per_meal'], 2) }} ج.م</div>
                <div class="kpi-subtext">بناءً على المقنن النشط v{{ $mrpResult['version']?->version_number ?? 1 }}</div>
            </div>
        </div>

        <!-- MRP Exploded Table -->
        <div class="erp-card overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <x-icon name="layers" size="18" class="text-sky-600" />
                    <span>جدول الاحتياجات التموينية بعد معالجة معامل التشافي والهدر (MRP Exploded BOM)</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>الصنف الغذائي</th>
                            <th>المقنن الصافي للوجبة</th>
                            <th>الاحتياج النظري الصافي</th>
                            <th>معامل التشفية (Yield %)</th>
                            <th>الكمية المطلوبة للشراء والصرف</th>
                            <th>سعر الكيلو التقديري</th>
                            <th>إجمالي التكلفة المتوقعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mrpResult['items'] as $item)
                            <tr>
                                <td class="font-bold text-slate-800">{{ $item['product_name'] }}</td>
                                <td class="font-numeric">{{ number_format($item['raw_per_meal'], 3) }} {{ $item['unit'] }}</td>
                                <td class="font-numeric font-semibold text-slate-700">{{ number_format($item['theoretical_net_qty'], 2) }} {{ $item['unit'] }}</td>
                                <td>
                                    <span class="erp-badge font-numeric font-bold {{ $item['butchery_yield_pct'] < 100 ? 'erp-badge-warning' : 'erp-badge-neutral' }}">
                                        {{ number_format($item['butchery_yield_pct'], 1) }}%
                                    </span>
                                </td>
                                <td class="font-numeric font-black text-rose-700 text-base">
                                    {{ number_format($item['required_procurement_qty'], 2) }} {{ $item['unit'] }}
                                </td>
                                <td class="font-numeric text-slate-600">{{ number_format($item['unit_cost'], 2) }} ج.م</td>
                                <td class="font-numeric font-bold text-emerald-700">{{ number_format($item['total_cost'], 2) }} ج.م</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-slate-400">
                                    لا توجد مقادير مسجلة في هذا الإصدار من المقنن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
