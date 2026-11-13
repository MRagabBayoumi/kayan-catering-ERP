@extends('layouts.app')

@section('title', 'المقننات القياسية للوجبات (BOM)')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">المقننات الغذائية القياسية للوجبات (BOM)</h1>
            <p class="text-xs text-slate-500 mt-1">تحديد أوزان الخامات المعيارية (جرام/وجبة) والتكلفة المستهدفة لكل مريض وطاقم طبي</p>
        </div>
        <a href="{{ route('costing.recipes.create') }}" class="erp-btn erp-btn-primary">
            <x-icon name="plus" size="16" />
            <span>إضافة مقنن وجبة جديد</span>
        </a>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
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
