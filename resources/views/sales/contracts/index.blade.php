@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دليل العقود والأسعار التعاقدية للمستشفيات" :breadcrumbs="['المبيعات والمناقصات' => route('sales.hospitals.index'), 'العقود والتعاقدات' => route('sales.contracts.index')]">
        <x-slot:actions>
            <a href="{{ route('sales.contracts.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تفعيل عقد مستشفى جديد</span>
            </a>
            <a href="{{ route('sales.feasibility.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="trending-up" size="16" />
                <span>دراسات الجدوى التقديرية</span>
            </a>
            <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="building" size="16" />
                <span>دليل المستشفيات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>العقود النشطة:</span>
                <strong>{{ $activeCount }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $salesTabs = [
            [
                'label' => 'دليل المستشفيات والجهات',
                'url' => route('sales.hospitals.index'),
                'icon' => 'building-2',
                'active' => request()->routeIs('sales.hospitals*'),
            ],
            [
                'label' => 'العقود والأسعار التعاقدية',
                'url' => route('sales.contracts.index'),
                'icon' => 'file-text',
                'active' => request()->routeIs('sales.contracts*'),
                'badge' => $activeCount,
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'دراسات الجدوى التقديرية',
                'url' => route('sales.feasibility.index'),
                'icon' => 'trending-up',
                'active' => request()->routeIs('sales.feasibility*'),
            ],
            [
                'label' => 'سجل المناقصات والمزايدات',
                'url' => route('sales.tenders.index'),
                'icon' => 'award',
                'active' => request()->routeIs('sales.tenders*'),
            ],
            [
                'label' => 'أرشيف أسعار المنافسين',
                'url' => route('sales.competitors.index'),
                'icon' => 'bar-chart-2',
                'active' => request()->routeIs('sales.competitors*'),
            ],
        ];
    @endphp
    <x-module-nav :tabs="$salesTabs" />

    @if(session('success'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- KPI Cards Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-title">العقود النشطة والسارية</div>
            <div class="kpi-value font-numeric text-emerald-600">{{ $activeCount }}</div>
            <div class="kpi-subtext">من إجمالي {{ $contracts->total() }} عقد مسجل</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">القيمة الإجمالية للعقود النشطة</div>
            <div class="kpi-value font-numeric text-sky-600">{{ number_format($totalContractsValue, 0) }} <span class="text-xs font-normal">ج.م</span></div>
            <div class="kpi-subtext">حجم الأعمال السنوي المتعاقد عليه</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">المستشفيات المتعاقدة</div>
            <div class="kpi-value font-numeric text-indigo-600">{{ $hospitalsCount }}</div>
            <div class="kpi-subtext">مواقع ومطابخ ميدانية</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">الربط مع مراكز التكلفة</div>
            <div class="kpi-value font-numeric text-amber-600">100%</div>
            <div class="kpi-subtext">تتبع الإيرادات وربحية المستشفى</div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="erp-card overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <x-icon name="file-text" size="18" class="text-sky-600" />
                <span>سجل العقود والمطالبات التعاقدية للمستشفيات</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>اسم المستشفى</th>
                        <th>مركز التكلفة</th>
                        <th>فترة العقد</th>
                        <th>قيمة العقد</th>
                        <th>دورة المطالبة</th>
                        <th>أسعار الوجبات</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $c)
                        <tr>
                            <td class="font-mono font-bold text-sky-700">{{ $c->contract_number }}</td>
                            <td>
                                <div class="font-bold text-slate-800">{{ $c->hospital->name }}</div>
                                <div class="text-xs text-slate-500">{{ $c->title }}</div>
                            </td>
                            <td>
                                @if($c->costCenter)
                                    <span class="erp-badge erp-badge-neutral font-mono text-xs">{{ $c->costCenter->code }}</span>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="font-numeric text-xs">
                                <div>{{ $c->start_date->format('Y-m-d') }}</div>
                                <div class="text-slate-400">إلى {{ $c->end_date->format('Y-m-d') }}</div>
                            </td>
                            <td class="font-numeric font-bold text-emerald-700">
                                {{ number_format($c->contract_value, 2) }} ج.م
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-neutral">{{ $c->billing_cycle === 'monthly' ? 'شهرياً' : $c->billing_cycle }}</span>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    @foreach($c->mealPrices->take(2) as $mp)
                                        <div>
                                            {{ $mp->mealType->name ?? 'وجبة' }}: 
                                            <strong class="font-numeric text-sky-700">{{ number_format($mp->price_per_meal, 2) }} ج.م</strong>
                                        </div>
                                    @endforeach
                                    @if($c->mealPrices->count() > 2)
                                        <div class="text-slate-400">+{{ $c->mealPrices->count() - 2 }} وجبات أخرى</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($c->status === 'active')
                                    <span class="erp-badge erp-badge-success">سارٍ ونشط</span>
                                @elseif($c->status === 'draft')
                                    <span class="erp-badge erp-badge-neutral">مسودة</span>
                                @else
                                    <span class="erp-badge erp-badge-warning">{{ $c->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد عقود مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contracts->hasPages())
            <div class="p-3 border-t border-slate-100">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
