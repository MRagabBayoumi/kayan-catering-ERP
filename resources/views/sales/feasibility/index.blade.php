@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دراسات الجدوى التقديرية وهوامش ربحية المناقصات" :breadcrumbs="['المبيعات والمناقصات' => route('sales.hospitals.index'), 'دراسات الجدوى' => route('sales.feasibility.index')]">
        <x-slot:actions>
            <a href="{{ route('sales.feasibility.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إعداد دراسة جدوى جديدة</span>
            </a>
            <a href="{{ route('sales.tenders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="award" size="16" />
                <span>سجل المناقصات</span>
            </a>
            <a href="{{ route('sales.contracts.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>دليل العقود المعتمدة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الدراسات:</span>
                <strong>{{ $studies->total() }}</strong>
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
            ],
            [
                'label' => 'دراسات الجدوى التقديرية',
                'url' => route('sales.feasibility.index'),
                'icon' => 'trending-up',
                'active' => request()->routeIs('sales.feasibility*'),
                'badge' => $studies->total(),
                'badge_class' => 'erp-badge-primary',
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

    <div class="erp-card overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <x-icon name="trending-up" size="18" class="text-sky-600" />
                <span>سجل دراسات الجدوى التقديرية للمناقصات والمشاريع</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم الدراسة</th>
                        <th>المستشفى / الجهة</th>
                        <th>عدد الوجبات المتوقع</th>
                        <th>سعر البيع المستهدف</th>
                        <th>الإيراد المتوقع</th>
                        <th>التكلفة التقديرية</th>
                        <th>هامش الربح المتوقع</th>
                        <th>نقطة التعادل (وجبة)</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studies as $s)
                        <tr>
                            <td class="font-mono font-bold text-sky-700">{{ $s->study_number }}</td>
                            <td>
                                <strong class="text-slate-800">{{ $s->hospital_name }}</strong>
                                @if($s->tender)
                                    <div class="text-xs text-slate-500 font-mono">مناقصة: {{ $s->tender->title }}</div>
                                @endif
                            </td>
                            <td class="font-numeric">{{ number_format($s->expected_total_meals) }}</td>
                            <td class="font-numeric text-sky-700 font-bold">{{ number_format($s->target_selling_price_per_meal, 2) }} ج.م</td>
                            <td class="font-numeric font-bold text-emerald-700">{{ number_format($s->expected_revenue, 2) }} ج.م</td>
                            <td class="font-numeric font-bold text-rose-700">{{ number_format($s->estimated_total_cost, 2) }} ج.م</td>
                            <td>
                                <span class="erp-badge {{ $s->expected_gross_margin > 0 ? 'erp-badge-success' : 'erp-badge-danger' }} font-numeric font-bold">
                                    {{ number_format($s->expected_net_margin_percentage, 1) }}% ({{ number_format($s->expected_gross_margin, 0) }} ج.م)
                                </span>
                            </td>
                            <td class="font-numeric font-mono text-xs">{{ number_format($s->break_even_meals) }}</td>
                            <td>
                                <span class="erp-badge erp-badge-success">معتمد ومدروس</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                لا توجد دراسات جدوى مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($studies->hasPages())
            <div class="p-3 border-t border-slate-100">
                {{ $studies->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
