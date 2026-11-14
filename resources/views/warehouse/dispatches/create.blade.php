@extends('layouts.app')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6" dir="rtl">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-icon name="truck" size="22" class="text-blue-600" />
                <span>إصدار إذن تحميل وصرف لسيارة المندوب ومطابخ المواقع</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                صرف الخامات من المخزن بنظام FEFO وربطها المباشر بمقننات الوجبات (BOM) ونسب الهالك المعيارية لكل صنف.
            </p>
        </div>
        <a href="{{ route('warehouse.dispatches') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition">
            رجوع للأذون
        </a>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <x-icon name="alert-circle" size="16" class="text-rose-600" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('warehouse.dispatches.store') }}" method="POST" id="dispatchOrderForm" class="space-y-6">
        @csrf

        <!-- Main Info Box -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700 pb-2">بيانات التوريد والسيارة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Hospital -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">المستشفى / الجهة المستهدفة *</label>
                    <select name="hospital_id" id="hospitalSelect" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        <option value="">اختر المستشفى...</option>
                        @foreach($hospitals as $hosp)
                            <option value="{{ $hosp->id }}" {{ old('hospital_id') == $hosp->id ? 'selected' : '' }}>{{ $hosp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Warehouse -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">المخزن المصدر للصرف *</label>
                    <select name="source_warehouse_id" id="sourceWarehouseSelect" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        @foreach($sourceWarehouses as $swh)
                            <option value="{{ $swh->id }}" {{ old('source_warehouse_id') == $swh->id ? 'selected' : '' }}>{{ $swh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Transit Van Warehouse -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">سيارة المندوب (المخزن المتنقل) *</label>
                    <select name="transit_warehouse_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        @foreach($transitWarehouses as $twh)
                            <option value="{{ $twh->id }}" {{ old('transit_warehouse_id') == $twh->id ? 'selected' : '' }}>{{ $twh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Driver -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">السائق المستلم للعهدة *</label>
                    <select name="driver_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->id }}" {{ old('driver_id') == $drv->id ? 'selected' : '' }}>{{ $drv->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Kitchen BOM & Waste Rate Integration Box -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-950/60 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600">
                        <x-icon name="utensils" size="16" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                        ربط الصرف بمطبخ الوجبات ومقنن الـ BOM المعياري (Kitchen Yield & Waste Control)
                    </h2>
                </div>
                <span class="text-[11px] text-slate-400">اختياري - للاحتساب الآلي لمكونات الوجبة وهالك التحضير</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">اختر الوجبة / المقنن المعتمد (BOM)</label>
                    <select name="recipe_id" id="recipeSelector" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        <option value="">-- اختر الوجبة لاحتساب مقنناتها آلياً --</option>
                        @foreach($recipes as $rec)
                            <option value="{{ $rec->id }}" data-code="{{ $rec->recipe_code }}">{{ $rec->name }} ({{ $rec->recipe_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">عدد الوجبات المطلوب إعدادها</label>
                    <input type="number" min="1" step="1" name="planned_meals_count" id="plannedMealsCount" value="100" placeholder="مثال: 100" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-mono">
                </div>

                <div class="md:col-span-3">
                    <button type="button" id="btnCalculateBom" class="w-full py-2.5 px-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-1.5">
                        <x-icon name="calculator" size="14" />
                        <span>احتساب المقننات والهالك</span>
                    </button>
                </div>
            </div>

            <!-- Dynamic BOM Calculation Preview Table -->
            <div id="bomCalculationContainer" class="hidden space-y-3 pt-2">
                <div class="p-3 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-xl border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-center justify-between">
                    <span id="bomSummaryText">نتائج احتساب مقننات الوجبة ومعدل الهالك المعياري:</span>
                    <button type="button" id="btnApplyBomToTable" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg transition flex items-center gap-1 text-[11px]">
                        <x-icon name="check" size="13" />
                        <span>تطبيق هذه المقادير في جدول إذن الصرف أدناه</span>
                    </button>
                </div>

                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>الصنف والباركود</th>
                                <th>المقنن للوجبة</th>
                                <th>المطلوب الصافي</th>
                                <th>نسبة الهالك المعياري</th>
                                <th>كمية الهالك (Buffer)</th>
                                <th>إجمالي المقترح صرفه</th>
                                <th>رصيد المخزن المتاح</th>
                                <th>حالة الكفاية</th>
                            </tr>
                        </thead>
                        <tbody id="bomPreviewTbody">
                            <!-- Populated via warehouse.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Items Loading Box -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200">أصناف التحميل والكميات المنصرفة ونسبة الأمان</h2>
                <button type="button" id="btnAddRowBtn" class="text-xs px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg transition flex items-center gap-1">
                    <x-icon name="plus" size="13" />
                    <span>إضافة صنف إضافي</span>
                </button>
            </div>

            <div class="space-y-3" id="dispatchItemsContainer">
                @for($i = 0; $i < 3; $i++)
                    <div class="dispatch-row grid grid-cols-1 md:grid-cols-12 gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700 items-center">
                        <div class="md:col-span-5">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">الصنف الغذائي</label>
                            <select name="items[{{ $i }}][product_id]" class="product-selector w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                <option value="">اختر الصنف...</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" data-unit="{{ $prod->unit }}" data-waste="{{ $prod->acceptable_waste_pct }}" {{ $i == 0 && $loop->first ? 'selected' : '' }}>
                                        {{ $prod->name }} ({{ $prod->unit }}) @if($prod->barcode) [{{ $prod->barcode }}] @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">الكمية الصافية المقننة</label>
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][planned_qty]" value="{{ $i == 0 ? '50' : '' }}" class="planned-qty-input w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono" placeholder="0.00" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">هالك التحضير والأمان (Buffer)</label>
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][buffer_qty]" value="{{ $i == 0 ? '5' : '' }}" class="buffer-qty-input w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono" placeholder="0.00" />
                        </div>
                        <div class="md:col-span-1 text-center pt-4">
                            <button type="button" class="btn-remove-row text-slate-400 hover:text-rose-500 p-1" title="حذف السطر">
                                <x-icon name="trash" size="15" />
                            </button>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                <x-icon name="truck" size="16" />
                <span>تأكيد الصرف والتحميل لسيارة المندوب بنظام FEFO / FIFO</span>
            </button>
        </div>
    </form>
</div>
@endsection
