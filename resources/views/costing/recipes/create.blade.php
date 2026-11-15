@extends('layouts.app')

@section('title', 'إضافة مقنن وجبة معتمد — نظام السلة والكاشير')

@section('control_panel')
    <x-control-panel title="إضافة مقنن وجبة معتمد (Recipe BOM — نظام كاشير التكاليف)" :breadcrumbs="['إدارة التكاليف' => route('costing.recipes.index'), 'المقننات القياسية' => route('costing.recipes.index'), 'إضافة مقنن' => '#']">
        <x-slot:actions>
            <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمقننات</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div id="recipeBuilderContainer" class="space-y-5" dir="rtl">

    @if($errors->any())
        <div class="erp-alert erp-alert-danger">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="recipePosForm" action="{{ route('costing.recipes.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" id="standardCostPerMealHidden" name="standard_cost_per_meal" value="0.00">

        <!-- 1. Meal Master Info Card -->
        <div class="erp-card p-5">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <x-icon name="file-text" size="16" class="text-slate-400" />
                <span>بيانات الوجبة الأساسية وتخصيص الموقع</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-form-label">اسم الوجبة القياسية <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" class="erp-form-control" placeholder="مثال: غداء لحم بلدي مع أرز مصري وخضار" required value="{{ old('name') }}">
                </div>

                <div>
                    <label class="erp-form-label">نوع الوجبة الطبية <span class="text-rose-500">*</span></label>
                    <select name="meal_type_id" class="erp-form-control" required>
                        <option value="">-- اختر نوع الوجبة --</option>
                        @foreach($mealTypes as $mt)
                            <option value="{{ $mt->id }}" {{ old('meal_type_id') == $mt->id ? 'selected' : '' }}>
                                {{ $mt->name }} ({{ $mt->category }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="erp-form-label">المستشفى / الموقع الخاضع للإشراف</label>
                    <select name="hospital_id" class="erp-form-control">
                        <option value="">عام لكافة المستشفيات المعتمدة</option>
                        @foreach($hospitals as $hosp)
                            <option value="{{ $hosp->id }}" {{ old('hospital_id') == $hosp->id ? 'selected' : '' }}>
                                {{ $hosp->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-slate-400 block mt-1">تقتصر القائمة على المستشفيات المرتبطة بصلاحية حسابك في التكاليف</span>
                </div>
            </div>
        </div>

        <!-- 2. POS Top Entry Scanner / Item Adder -->
        <div class="erp-card p-5 bg-gradient-to-r from-slate-50 to-indigo-50/30 dark:from-slate-900/40 dark:to-slate-800/40 border border-indigo-100 dark:border-indigo-950/50 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <x-icon name="shopping-cart" size="18" class="text-primary-600 dark:text-primary-400" />
                    <span>شريط إدخال الكاشير (إضافة خامات إلى سلة الوجبة)</span>
                </h2>
                <span class="erp-badge erp-badge-info text-xs flex items-center gap-1.5">
                    <x-icon name="lock" size="12" />
                    <span>الأسعار معتمدة ومقفلة من إدارة المشتريات</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <!-- Product Select -->
                <div class="md:col-span-4">
                    <label class="erp-form-label text-xs">الصنف الخام من المخزن</label>
                    <select id="posProductSelect" class="erp-form-control">
                        <option value="">-- اختر الصنف الخام للإضافة للسلة --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}"
                                    data-cost="{{ $prod->cost_price }}"
                                    data-unit="{{ $prod->unit }}"
                                    data-name="{{ $prod->name }}">
                                {{ $prod->name }} (سعر الشراء: {{ number_format($prod->cost_price, 2) }} ج.م / {{ $prod->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Weight / Quantity Input -->
                <div class="md:col-span-2">
                    <label class="erp-form-label text-xs">وزن المقنن للوجبة</label>
                    <input type="number" id="posWeightInput" step="0.001" min="0.001" class="erp-form-control font-mono font-bold text-center" placeholder="0.150 (كجم)">
                </div>

                <!-- Unit Selection -->
                <div class="md:col-span-2">
                    <label class="erp-form-label text-xs">الوحدة القياسية</label>
                    <select id="posUnitSelect" class="erp-form-control">
                        <option value="kg">كيلوجرام (kg)</option>
                        <option value="gm">جرام (gm)</option>
                        <option value="pcs">قطعة (pcs)</option>
                        <option value="liter">لتر (L)</option>
                        <option value="ml">مليلتر (ml)</option>
                    </select>
                </div>

                <!-- Live Sourced Cost Price (Procurement) -->
                <div class="md:col-span-2">
                    <label class="erp-form-label text-xs">سعر الوحدة المعتمد</label>
                    <div class="relative">
                        <input type="text" id="posCostInput" class="erp-form-control font-mono font-bold bg-slate-100 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 pl-16 text-center" placeholder="0.00" readonly>
                        <span id="posCostUnitBadge" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-slate-400 select-none">ج.م</span>
                    </div>
                </div>

                <!-- Add Button -->
                <div class="md:col-span-2">
                    <button type="button" id="posAddToCartBtn" class="erp-btn erp-btn-primary w-full h-[42px] justify-center text-xs font-bold gap-1.5" title="إضافة الصنف إلى سلة المقنن">
                        <x-icon name="plus" size="16" />
                        <span>إضافة للسلة</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Basket Cart Table (سلة مقننات الوجبة) -->
        <div class="erp-card overflow-hidden">
            <div class="p-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icon name="layers" size="18" class="text-slate-500" />
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">سلة المقنن القياسي للوجبة (خامات الوجبة المحسوبة)</h3>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <span>عدد الأصناف بالسلة: <strong id="basketCountDisplay" class="text-primary-600 font-mono text-sm">0</strong></span>
                    <span>•</span>
                    <span>الوزن التقريبي للوجبة: <strong id="basketTotalWeightDisplay" class="text-slate-800 dark:text-slate-100 font-mono text-sm">0.000 كجم</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="erp-table w-full">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>الصنف الخام</th>
                            <th class="w-36">وزن المقنن / الوجبة</th>
                            <th class="w-28 text-center">الوحدة</th>
                            <th class="w-40">سعر الشراء (المشتريات)</th>
                            <th class="w-36">تكلفة الصنف بالوجبة</th>
                            <th class="w-16 text-center">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="recipeBasketBody">
                        <!-- Dynamically populated by recipe-builder.js -->
                    </tbody>
                </table>
            </div>

            <!-- Empty Basket Notice -->
            <div id="recipeBasketEmpty" class="p-12 text-center text-slate-400">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                    <x-icon name="shopping-bag" size="28" />
                </div>
                <p class="font-bold text-slate-600 dark:text-slate-300">سلة خامات الوجبة فارغة حالياً</p>
                <p class="text-xs text-slate-400 mt-1">اختر الصنف الخام من الشريط العلوي وحدد وزنه ثم اضغط (إضافة للسلة) لاحتساب التكلفة</p>
            </div>

            <!-- Grand Live Cost Summary (POS Checkout Counter) -->
            <div class="p-5 bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950 text-white flex flex-col md:flex-row items-center justify-between gap-4 border-t border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400">
                        <x-icon name="calculator" size="24" />
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-300 font-semibold block">إجمالي تكلفة إنتاج الوجبة الواحدة المعتمدة</span>
                        <span class="text-xs text-slate-400">محتسبة فورياً من أسعار خامات المشتريات ومجموع الأوزان المضافة</span>
                    </div>
                </div>

                <div class="flex items-baseline gap-2">
                    <span class="text-xs text-slate-300">التكلفة الإجمالية:</span>
                    <span id="basketTotalCostDisplay" class="font-mono text-3xl font-extrabold text-emerald-400 tracking-tight">0.00 ج.م</span>
                </div>
            </div>
        </div>

        <!-- 4. Notes & Submit / Post Bar -->
        <div class="erp-card p-5">
            <div class="mb-4">
                <label class="erp-form-label">ملاحظات واعتماد كراسة الشروط (اختياري)</label>
                <textarea name="notes" rows="2" class="erp-form-control" placeholder="أي شروط خاصة بالمقنن، طريقة الطهي، أو اشتراطات المستشفى...">{{ old('notes') }}</textarea>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary w-full sm:w-auto justify-center">
                    <span>إلغاء</span>
                </a>

                <button type="submit" class="erp-btn erp-btn-primary w-full sm:w-auto justify-center px-8 py-2.5 text-sm font-bold">
                    <x-icon name="check-circle" size="18" />
                    <span>اعتماد وترحيل المقنن للوجبة</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
