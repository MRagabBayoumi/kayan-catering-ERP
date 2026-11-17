@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تحليل ربحية المستشفيات ومراكز التكلفة (Contract Profitability)" :breadcrumbs="['المالية والحسابات' => route('finance.treasury.index'), 'ربحية المستشفيات' => route('finance.profitability.index')]">
        <x-slot:actions>
            <a href="{{ route('finance.closing.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="check-square" size="16" />
                <span>تقفيل اليومية للمالك</span>
            </a>
            <a href="{{ route('finance.ledger.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book" size="16" />
                <span>دفتر الأستاذ العام</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <x-finance-nav />

    <!-- Hospital & Date Range Filter Bar -->
    <div class="erp-card p-4">
        <form method="GET" action="{{ route('finance.profitability.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="erp-label">المستشفى / مركز التكلفة</label>
                <select name="hospital_id" class="erp-input">
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}" {{ $selectedHospitalId == $hosp->id ? 'selected' : '' }}>
                            {{ $hosp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-label">من تاريخ</label>
                <input type="date" name="from_date" class="erp-input font-numeric" value="{{ $fromDate }}" />
            </div>
            <div>
                <label class="erp-label">إلى تاريخ</label>
                <input type="date" name="to_date" class="erp-input font-numeric" value="{{ $toDate }}" />
            </div>
            <div>
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="filter" size="16" />
                    <span>تحديث تقرير الربحية</span>
                </button>
            </div>
        </form>
    </div>

    @if($profitability)
        @php
            $k = $profitability['kpis'];
            $h = $profitability['hospital'];
            $c = $profitability['contract'];
        @endphp

        <!-- Main Profitability Summary Banner -->
        <div class="bg-gradient-to-l from-slate-900 to-sky-950 text-white rounded-xl p-6 shadow-md border border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-4 mb-5">
                <div>
                    <span class="inline-block bg-sky-500/20 text-sky-300 border border-sky-400/30 text-xs px-2.5 py-0.5 rounded-full font-bold mb-1">
                        مركز التكلفة: {{ $h->costCenter->code ?? 'CC-HOSP-'.$h->id }}
                    </span>
                    <h2 class="text-2xl font-black">{{ $h->name }}</h2>
                    <p class="text-xs text-slate-300">
                        العقد الساري: {{ $c->contract_number ?? 'عقد معتمد' }} • الفترة: {{ $profitability['period']['from'] }} إلى {{ $profitability['period']['to'] }}
                    </p>
                </div>
                <div class="text-left">
                    <span class="text-xs text-slate-400 block">صافي ربح المستشفى (Net Contribution)</span>
                    <div class="text-3xl font-black font-numeric {{ $k['net_profit'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ number_format($k['net_profit'], 2) }} <span class="text-base font-normal">ج.م</span>
                    </div>
                    <span class="text-xs font-bold font-numeric {{ $k['net_profit'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                        هامش المساهمة: {{ number_format($k['profit_margin_pct'], 1) }}%
                    </span>
                </div>
            </div>

            <!-- Fast Stat Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                    <span class="text-xs text-slate-300 block">إجمالي الإيرادات التعاقدية</span>
                    <strong class="text-xl font-numeric text-sky-300">{{ number_format($k['total_revenue'], 2) }} ج.م</strong>
                    <span class="text-2xs text-slate-400 block mt-0.5 font-numeric">{{ number_format($k['total_meals_count']) }} وجبة</span>
                </div>
                <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                    <span class="text-xs text-slate-300 block">تكلفة خامات الأغذية (Food Cost)</span>
                    <strong class="text-xl font-numeric text-amber-300">{{ number_format($k['food_cost'], 2) }} ج.م</strong>
                    <span class="text-2xs text-slate-400 block mt-0.5 font-numeric">نسبة التكلفة: {{ number_format($k['food_cost_pct'], 1) }}%</span>
                </div>
                <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                    <span class="text-xs text-slate-300 block">أجور عمالة المطبخ المقدرة</span>
                    <strong class="text-xl font-numeric text-slate-200">{{ number_format($k['labor_cost'], 2) }} ج.م</strong>
                    <span class="text-2xs text-slate-400 block mt-0.5">شيفات وعمال الموقع</span>
                </div>
                <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                    <span class="text-xs text-slate-300 block">مصاريف الموقع والنقل والهدر</span>
                    <strong class="text-xl font-numeric text-rose-300">{{ number_format($k['fleet_cost'] + $k['site_expenses'] + $k['waste_cost'], 2) }} ج.م</strong>
                    <span class="text-2xs text-slate-400 block mt-0.5">وقود، خبز، وهدر تالف</span>
                </div>
            </div>
        </div>

        <!-- Detailed Cost Allocation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Cost Composition -->
            <div class="erp-card p-5">
                <h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <x-icon name="pie-chart" size="18" class="text-sky-600" />
                    <span>تفريدة وتحليل عناصر تكلفة المستشفى (Cost Breakdown)</span>
                </h3>
                <div class="space-y-3 font-numeric text-sm">
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded">
                        <span class="text-slate-700">تكلفة خامات الأغذية (من واقع FIFO وباتشات المطبخ):</span>
                        <strong class="text-slate-900">{{ number_format($k['food_cost'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded">
                        <span class="text-slate-700">أجور وطواقم تشغيل مطبخ الموقع:</span>
                        <strong class="text-slate-900">{{ number_format($k['labor_cost'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded">
                        <span class="text-slate-700">لوجستيات سيارات التوزيع والوقود الموزعة:</span>
                        <strong class="text-slate-900">{{ number_format($k['fleet_cost'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded">
                        <span class="text-slate-700">مصاريف تشغيل الموقع النقدية وشراء الخبز (العهدة):</span>
                        <strong class="text-slate-900">{{ number_format($k['site_expenses'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded">
                        <span class="text-slate-700">خسائر الهدر والتلف المعتمد بالموقع:</span>
                        <strong class="text-rose-600">{{ number_format($k['waste_cost'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-sky-50 rounded border border-sky-100 font-bold text-base">
                        <span class="text-sky-900">إجمالي التكلفة التشغيلية الفعلية للمستشفى:</span>
                        <span class="text-sky-900">{{ number_format($k['total_cost'], 2) }} ج.م</span>
                    </div>
                </div>
            </div>

            <!-- Contract & Census Summary -->
            <div class="erp-card p-5">
                <h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <x-icon name="file-text" size="18" class="text-emerald-600" />
                    <span>محددات العقد والتعداد المنفذ خلال الفترة</span>
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">إجمالي عدد النزلاء المخدمين (Census):</span>
                        <strong class="font-numeric text-slate-900">{{ number_format($k['total_patient_census']) }} مريض</strong>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">إجمالي الوجبات المجهزة والمقدمة:</span>
                        <strong class="font-numeric text-slate-900">{{ number_format($k['total_meals_count']) }} وجبة</strong>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">متوسط سعر بيع الوجبة التعاقدي:</span>
                        <strong class="font-numeric text-emerald-700">{{ number_format($k['avg_meal_price'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">متوسط التكلفة الفعلية للوجبة الواحدة:</span>
                        <strong class="font-numeric text-slate-800">
                            {{ $k['total_meals_count'] > 0 ? number_format($k['total_cost'] / $k['total_meals_count'], 2) : '0.00' }} ج.م
                        </strong>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">صافي ربح الوجبة الواحدة:</span>
                        <strong class="font-numeric text-emerald-600">
                            {{ $k['total_meals_count'] > 0 ? number_format($k['net_profit'] / $k['total_meals_count'], 2) : '0.00' }} ج.م
                        </strong>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-600">حالة الربحية التشغيلية:</span>
                        @if($k['profit_margin_pct'] >= 15)
                            <span class="erp-badge erp-badge-success font-bold">ربحية ممتازة تتجاوز المستهدف</span>
                        @elseif($k['profit_margin_pct'] > 0)
                            <span class="erp-badge erp-badge-warning font-bold">ربحية مقبولة تتطلب ترشيد التكاليف</span>
                        @else
                            <span class="erp-badge erp-badge-danger font-bold">عجز تشغيلي يستدعي مراجعة المقنن</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
