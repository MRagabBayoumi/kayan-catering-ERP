@extends('layouts.app')

@section('title', 'المقننات القياسية للوجبات (BOM)')

@section('control_panel')
    <x-control-panel title="المقننات الغذائية القياسية للوجبات (BOM)" :breadcrumbs="['إدارة التكاليف والمقننات' => route('costing.recipes.index'), 'المقننات' => route('costing.recipes.index')]">
        <x-slot:actions>
            <a href="{{ route('costing.recipes.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة مقنن وجبة جديد</span>
            </a>
            <a href="{{ route('costing.daily-order') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calculator" size="16" />
                <span>حساب طلبيات المطبخ</span>
            </a>
            <a href="{{ route('costing.consumption.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="alert-triangle" size="16" />
                <span>كشف انحرافات المقنن</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي المقننات:</span>
                <strong>{{ count($recipes) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $costingTabs = [
            [
                'label' => 'مقننات الوجبات القياسية (BOM)',
                'url' => route('costing.recipes.index'),
                'icon' => 'file-text',
                'active' => request()->routeIs('costing.recipes*'),
                'badge' => count($recipes),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'حساب طلبيات المطبخ اليومية',
                'url' => route('costing.daily-order'),
                'icon' => 'calculator',
                'active' => request()->routeIs('costing.daily-order'),
            ],
            [
                'label' => 'كشف انحرافات المقنن والهدر',
                'url' => route('costing.consumption.index'),
                'icon' => 'alert-triangle',
                'active' => request()->routeIs('costing.consumption*'),
            ],
        ];
    @endphp
    <x-module-nav :tabs="$costingTabs" />

    <!-- Fast Status KPI Ribbon (Costing Overview) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إجمالي المقننات القياسية</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="book-open" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $recipes->total() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">وجبات محددة ببطاقات مقنن (BOM)</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المقننات المعتمدة</span>
                <span class="kpi-icon text-blue-600 bg-blue-50 dark:bg-blue-950/40"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ $recipes->where('is_approved_by_costing', true)->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">معتمدة من محاسب التكاليف والجهة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">متوسط تكلفة الوجبة</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="calculator" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ number_format((float) ($recipes->avg('standard_cost_per_meal') ?? 0), 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">معيار التكلفة المعتمدة للوجبة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">رقابة الهدر والانحراف</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="alert-triangle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">0%</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مؤشر انحراف خامات المطبخ</div>
        </div>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="erp-card">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>كود المقنن</th>
                        <th>اسم الوجبة</th>
                        <th>نوع الوجبة</th>
                        <th>الجهة / المستشفى</th>
                        <th>التكلفة المعيارية</th>
                        <th>حالة الاعتماد</th>
                        <th>مكونات الخامات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipes as $recipe)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $recipe->recipe_code }}</td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $recipe->name }}</td>
                            <td>
                                <span class="erp-badge erp-badge-info">{{ $recipe->mealType?->name ?? 'وجبة عامة' }}</span>
                            </td>
                            <td>{{ $recipe->hospital?->name ?? 'لكافة المستشفيات' }}</td>
                            <td class="font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($recipe->standard_cost_per_meal, 2) }} ج.م
                            </td>
                            <td>
                                @if($recipe->is_approved_by_costing && $recipe->is_approved_by_site_mgr)
                                    <span class="erp-badge erp-badge-success">معتمد تكاليف وموقع</span>
                                @else
                                    <span class="erp-badge erp-badge-warning">قيد المراجعة</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-xs space-y-1">
                                    @foreach($recipe->ingredients as $ing)
                                        <div>
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $ing->product->name }}:</span>
                                            <span class="font-mono text-slate-500">{{ $ing->raw_quantity_per_meal }} {{ $ing->unit }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                لا توجد مقننات قياسية مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $recipes->links() }}
        </div>
    </div>
</div>
@endsection
