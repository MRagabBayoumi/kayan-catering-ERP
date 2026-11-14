@extends('layouts.app')

@section('title', 'إضافة مقنن غذائي قياسي للوجبة')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">إضافة مقنن وجبة معتمد (Recipe BOM)</h1>
            <p class="text-xs text-slate-500 mt-1">تحديد نسب الخامات القياسية للوجبة الواحدة</p>
        </div>
        <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">
            <span>العودة للمقننات</span>
        </a>
    </div>

    @if($errors->any())
        <div class="erp-alert erp-alert-danger">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="erp-card p-6">
        <form action="{{ route('costing.recipes.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">اسم الوجبة</label>
                    <input type="text" name="name" class="erp-form-control" placeholder="مثال: غداء لحم بلدي مع أرز مصري" required value="{{ old('name') }}">
                </div>
                <div>
                    <label class="erp-form-label">نوع الوجبة الطبية</label>
                    <select name="meal_type_id" class="erp-form-control" required>
                        <option value="">-- اختر نوع الوجبة --</option>
                        @foreach($mealTypes as $mt)
                            <option value="{{ $mt->id }}">{{ $mt->name }} ({{ $mt->category }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">المستشفى (اختياري)</label>
                    <select name="hospital_id" class="erp-form-control">
                        <option value="">عام لكافة المستشفيات</option>
                        @foreach($hospitals as $hosp)
                            <option value="{{ $hosp->id }}">{{ $hosp->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">الخامات المكونة للوجبة (المقنن بالجرامات/الكيلو)</h3>
                    <span class="erp-badge erp-badge-info text-xs">
                        <x-icon name="shopping-bag" size="13" />
                        <span>أسعار الخامات معتمدة وتحدث آلياً من إدارة المشتريات والتوريدات</span>
                    </span>
                </div>

                <div class="space-y-3">
                    @for($i = 0; $i < 4; $i++)
                        <div class="recipe-ingredient-row grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div>
                                <label class="erp-form-label text-xs">الصنف الخام</label>
                                <select name="ingredients[{{ $i }}][product_id]" class="erp-form-control recipe-product-select" {{ $i === 0 ? 'required' : '' }}>
                                    <option value="">-- اختر الصنف --</option>
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->id }}" data-cost="{{ $prod->cost_price }}" data-unit="{{ $prod->unit }}">
                                            {{ $prod->name }} ({{ number_format($prod->cost_price, 2) }} ج.م - مشتريات)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="erp-form-label text-xs">الوزن الخام للوجبة</label>
                                <input type="number" step="0.001" name="ingredients[{{ $i }}][raw_quantity_per_meal]" class="erp-form-control font-mono font-bold" placeholder="مثلاً: 0.150" {{ $i === 0 ? 'required' : '' }}>
                            </div>
                            <div>
                                <label class="erp-form-label text-xs">الوحدة</label>
                                <select name="ingredients[{{ $i }}][unit]" class="erp-form-control recipe-unit-select">
                                    <option value="kg">كيلوجرام (kg)</option>
                                    <option value="gm">جرام (gm)</option>
                                    <option value="pcs">قطعة (pcs)</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-form-label text-xs">سعر التكلفة (إدارة المشتريات)</label>
                                <input type="number" step="0.01" name="ingredients[{{ $i }}][standard_unit_cost]" class="erp-form-control recipe-cost-input font-mono font-bold bg-slate-100 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400" placeholder="آلي من المشتريات" readonly>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="16" />
                    <span>حفظ واعتماد المقنن</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
