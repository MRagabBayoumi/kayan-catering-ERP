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
