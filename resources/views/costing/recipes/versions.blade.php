@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سجل إصدارات المقنن القياسي للوجبة: {{ $recipe->name }}" :breadcrumbs="['التكاليف والمقننات' => route('costing.recipes.index'), 'المقننات القياسية' => route('costing.recipes.index'), 'إصدارات المقنن' => route('costing.recipes.versions', $recipe)]">
        <x-slot:actions>
            <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة الوجبات</span>
            </a>
            <a href="{{ route('costing.mrp.index', ['recipe_id' => $recipe->id]) }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>حساب احتياج MRP للوجبة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('success'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Recipe Info Card -->
    <div class="erp-card p-5 bg-gradient-to-l from-slate-50 to-sky-50/40 border border-sky-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-xs text-slate-500 font-mono mb-1">كود المقنن: {{ $recipe->recipe_code }}</div>
            <h2 class="text-xl font-bold text-slate-800">{{ $recipe->name }}</h2>
            <div class="text-xs text-slate-600 mt-1">
                نوع الوجبة: <span class="font-semibold">{{ $recipe->mealType->name ?? 'وجبة قياسية' }}</span>
                @if($recipe->hospital)
                    • المستشفى: <span class="font-semibold">{{ $recipe->hospital->name }}</span>
                @endif
            </div>
        </div>
        <div class="text-left">
            <div class="text-xs text-slate-500">التكلفة المعيارية الحالية للوجبة</div>
            <div class="text-2xl font-black font-numeric text-sky-700">
                {{ number_format($recipe->standard_cost_per_meal, 2) }} <span class="text-xs font-normal">ج.م</span>
            </div>
        </div>
    </div>

    <!-- Historical Versions List -->
    <div class="space-y-4">
        <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <x-icon name="history" size="18" class="text-sky-600" />
            <span>الإصدارات التاريخية للمقنن (حماية التواريخ السابقة من التعديل)</span>
        </h3>

        @forelse($versions as $ver)
            <div class="erp-card p-5 {{ $ver->is_active ? 'border-r-4 border-r-emerald-500' : 'border-r-4 border-r-slate-300' }}">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-3">
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-slate-800 text-base">{{ $ver->version_name }} (v{{ $ver->version_number }})</span>
                        @if($ver->is_active)
                            <span class="erp-badge erp-badge-success">الإصدار النشط حالياً</span>
                        @else
                            <span class="erp-badge erp-badge-neutral">إصدار تاريخي سابق</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 font-numeric">
                        سارٍ من: <strong>{{ $ver->effective_from->format('Y-m-d') }}</strong>
                        @if($ver->effective_to)
                            إلى {{ $ver->effective_to->format('Y-m-d') }}
                        @else
                            وحتى تاريخه
                        @endif
                        • التكلفة: <strong class="text-sky-700 font-bold text-sm">{{ number_format($ver->total_standard_cost, 2) }} ج.م</strong>
                    </div>
                </div>

                <div class="text-xs text-slate-600 mb-3">
                    <strong>سبب الإصدار:</strong> {{ $ver->change_reason ?? 'إصدار أولي معتمد' }}
                </div>

                <!-- Ingredients Table for this version -->
                <div class="overflow-x-auto">
                    <table class="erp-table text-xs">
                        <thead>
                            <tr>
                                <th>الصنف الغذائي</th>
                                <th>الوزن الخام للوجبة</th>
                                <th>الوزن بعد الطبخ</th>
                                <th>نسبة التشفية (Butchery Yield %)</th>
                                <th>نسبة فقد الطبخ (Cooking Loss %)</th>
                                <th>سعر الكيلو التقديري</th>
                                <th>تكلفة الصنف بالوجبة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ver->ingredients as $ing)
                                <tr>
                                    <td class="font-semibold">{{ $ing->product->name }}</td>
                                    <td class="font-numeric">{{ number_format($ing->raw_quantity_per_meal, 3) }} {{ $ing->unit }}</td>
                                    <td class="font-numeric">{{ number_format($ing->cooked_yield_quantity, 3) }} {{ $ing->unit }}</td>
                                    <td class="font-numeric font-bold text-indigo-700">{{ number_format($ing->butchery_yield_pct, 1) }}%</td>
                                    <td class="font-numeric font-bold text-amber-700">{{ number_format($ing->shrinkage_cooking_loss_pct, 1) }}%</td>
                                    <td class="font-numeric">{{ number_format($ing->standard_unit_cost, 2) }} ج.م</td>
                                    <td class="font-numeric font-bold text-sky-700">{{ number_format($ing->total_ingredient_cost, 2) }} ج.م</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="erp-card p-6 text-center text-slate-400">
                لا توجد إصدارات سابقة مسجلة؛ يمكنك إضافة الإصدار الأول من النموذج أدناه.
            </div>
        @endforelse
    </div>

    <!-- Create New Version Form -->
    <div class="erp-card p-6">
        <h3 class="font-bold text-slate-800 text-base mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
            <x-icon name="plus-circle" size="20" class="text-sky-600" />
            <span>إصدار نسخة جديدة من المقنن (New Ration Version)</span>
        </h3>

        <form action="{{ route('costing.recipes.versions.store', $recipe) }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-label">مسمى الإصدار الجديد <span class="text-rose-500">*</span></label>
                    <input type="text" name="version_name" class="erp-input" value="إصدار شتاء 2026 / مقنن معتمد" required />
                </div>
                <div>
                    <label class="erp-label">تاريخ سريان الإصدار <span class="text-rose-500">*</span></label>
                    <input type="date" name="effective_from" class="erp-input font-numeric" value="{{ date('Y-m-d') }}" required />
                </div>
                <div>
                    <label class="erp-label">سبب التعديل والتحديث <span class="text-rose-500">*</span></label>
                    <input type="text" name="change_reason" class="erp-input" placeholder="تعديل نسبة التشفية، تحديث أسعار الخامات..." required />
                </div>
            </div>

            <!-- Fast 3 ingredients pre-filled template -->
            <div class="border-t border-slate-100 pt-4">
                <h4 class="font-bold text-slate-800 text-sm mb-3">مقادير وأوزان خامات الوجبة:</h4>

                <div class="space-y-3">
                    @foreach($products->take(4) as $idx => $prod)
                        <div class="grid grid-cols-1 sm:grid-cols-6 gap-2 p-3 bg-slate-50 rounded border border-slate-200 items-end text-xs">
                            <div class="sm:col-span-2">
                                <label class="erp-label">الصنف الغذائي</label>
                                <input type="hidden" name="ingredients[{{ $idx }}][product_id]" value="{{ $prod->id }}" />
                                <strong class="text-slate-800 text-sm block">{{ $prod->name }}</strong>
                                <span class="text-slate-400 font-mono">{{ $prod->sku }}</span>
                            </div>
                            <div>
                                <label class="erp-label">الوزن الخام (كجم)</label>
                                <input type="number" step="0.001" name="ingredients[{{ $idx }}][raw_quantity_per_meal]" class="erp-input font-numeric" value="0.150" required />
                            </div>
                            <div>
                                <label class="erp-label">نسبة التشفية %</label>
                                <input type="number" step="0.1" name="ingredients[{{ $idx }}][butchery_yield_pct]" class="erp-input font-numeric" value="{{ str_contains($prod->name, 'لحم') ? '80.0' : '100.0' }}" required />
                            </div>
                            <div>
                                <label class="erp-label">فقد الطبخ %</label>
                                <input type="number" step="0.1" name="ingredients[{{ $idx }}][shrinkage_cooking_loss_pct]" class="erp-input font-numeric" value="{{ str_contains($prod->name, 'لحم') ? '30.0' : '5.0' }}" required />
                            </div>
                            <div>
                                <label class="erp-label">سعر الكيلو (ج.م)</label>
                                <input type="number" step="0.01" name="ingredients[{{ $idx }}][standard_unit_cost]" class="erp-input font-numeric" value="{{ $prod->cost_price }}" required />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="18" />
                    <span>اعتماد وحفظ الإصدار الجديد وتحديث المقنن</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
