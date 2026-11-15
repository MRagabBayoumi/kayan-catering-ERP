@extends('layouts.app')

@section('title', 'إصدار إذن تحميل وصرف لسيارة المندوب')

@section('control_panel')
    <x-control-panel title="إصدار إذن تحميل وصرف لسيارة المندوب ومطابخ المواقع" :breadcrumbs="['إدارة المخازن والتشغيلات' => route('warehouse.index'), 'أذون التحميل' => route('warehouse.dispatches'), 'إصدار إذن جديد' => '#']">
        <x-slot:actions>
            <a href="{{ route('warehouse.dispatches') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع للأذون</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @if(session('error'))
        <div class="erp-alert erp-alert-danger">
            <x-icon name="alert-circle" size="16" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('warehouse.dispatches.store') }}" method="POST" id="dispatchOrderForm" class="space-y-5">
        @csrf

        <!-- Main Info Box -->
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 class="erp-card-title">
                    <x-icon name="truck" size="18" class="text-[var(--primary)]" />
                    <span>بيانات التوريد والسيارة</span>
                </h2>
                <span class="text-xs text-slate-500 font-medium">صرف الخامات من المخزن بنظام FEFO وربطها بالمقننات (BOM)</span>
            </div>
            <div class="erp-card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Hospital -->
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>المستشفى / الجهة المستهدفة</span>
                            <span class="required">*</span>
                        </label>
                        <select name="hospital_id" id="hospitalSelect" class="erp-form-control" required>
                            <option value="">اختر المستشفى...</option>
                            @foreach($hospitals as $hosp)
                                <option value="{{ $hosp->id }}" {{ old('hospital_id') == $hosp->id ? 'selected' : '' }}>{{ $hosp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Source Warehouse -->
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>المخزن المصدر للصرف</span>
                            <span class="required">*</span>
                        </label>
                        <select name="source_warehouse_id" id="sourceWarehouseSelect" class="erp-form-control" required>
                            @foreach($sourceWarehouses as $swh)
                                <option value="{{ $swh->id }}" {{ old('source_warehouse_id') == $swh->id ? 'selected' : '' }}>{{ $swh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Transit Van Warehouse -->
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>سيارة المندوب (المخزن المتنقل)</span>
                            <span class="required">*</span>
                        </label>
                        <select name="transit_warehouse_id" class="erp-form-control" required>
                            @foreach($transitWarehouses as $twh)
                                <option value="{{ $twh->id }}" {{ old('transit_warehouse_id') == $twh->id ? 'selected' : '' }}>{{ $twh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Driver -->
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>السائق المستلم للعهدة</span>
                            <span class="required">*</span>
                        </label>
                        <select name="driver_id" class="erp-form-control" required>
                            @foreach($drivers as $drv)
                                <option value="{{ $drv->id }}" {{ old('driver_id') == $drv->id ? 'selected' : '' }}>{{ $drv->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kitchen BOM & Waste Rate Integration Box -->
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 class="erp-card-title">
                    <x-icon name="utensils" size="18" class="text-amber-600" />
                    <span>ربط الصرف بمطبخ الوجبات ومقنن الـ BOM المعياري (Kitchen Yield & Waste Control)</span>
                </h2>
                <span class="text-xs text-slate-500 font-medium">اختياري - للاحتساب الآلي لمكونات الوجبة وهالك التحضير</span>
            </div>
            <div class="erp-card-body space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-6 erp-form-group mb-0">
                        <label class="erp-form-label">اختر الوجبة / المقنن المعتمد (BOM)</label>
                        <select name="recipe_id" id="recipeSelector" class="erp-form-control">
                            <option value="">-- اختر الوجبة لاحتساب مقنناتها آلياً --</option>
                            @foreach($recipes as $rec)
                                <option value="{{ $rec->id }}" data-code="{{ $rec->recipe_code }}">{{ $rec->name }} ({{ $rec->recipe_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3 erp-form-group mb-0">
                        <label class="erp-form-label">عدد الوجبات المطلوب إعدادها</label>
                        <input type="number" min="1" step="1" name="planned_meals_count" id="plannedMealsCount" value="100" placeholder="مثال: 100" class="erp-form-control font-mono">
                    </div>

                    <div class="md:col-span-3">
                        <button type="button" id="btnCalculateBom" class="erp-btn erp-btn-secondary w-full justify-center">
                            <x-icon name="calculator" size="16" />
                            <span>احتساب المقننات والهالك</span>
                        </button>
                    </div>
                </div>

                <!-- Dynamic BOM Calculation Preview Table -->
                <div id="bomCalculationContainer" class="hidden space-y-3 pt-2">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-center justify-between">
                        <span id="bomSummaryText" class="font-medium">نتائج احتساب مقننات الوجبة ومعدل الهالك المعياري:</span>
                        <button type="button" id="btnApplyBomToTable" class="erp-btn erp-btn-primary text-xs py-1 px-3">
                            <x-icon name="check" size="14" />
                            <span>تطبيق هذه المقادير في جدول إذن الصرف أدناه</span>
                        </button>
                    </div>

                    <div class="erp-table-responsive">
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
        </div>

        <!-- Items Loading Box -->
        <div class="erp-card">
            <div class="erp-card-header">
                <h2 class="erp-card-title">
                    <x-icon name="package" size="18" class="text-[var(--primary)]" />
                    <span>أصناف التحميل والكميات المنصرفة ونسبة الأمان</span>
                </h2>
                <button type="button" id="btnAddRowBtn" class="erp-btn erp-btn-secondary text-xs">
                    <x-icon name="plus" size="14" />
                    <span>إضافة صنف إضافي</span>
                </button>
            </div>
            <div class="erp-card-body space-y-3">
                <div class="space-y-3" id="dispatchItemsContainer">
                    @for($i = 0; $i < 3; $i++)
                        <div class="dispatch-row grid grid-cols-1 md:grid-cols-12 gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700 items-center">
                            <div class="md:col-span-5 erp-form-group mb-0">
                                <label class="erp-form-label">الصنف الغذائي</label>
                                <select name="items[{{ $i }}][product_id]" class="product-selector erp-form-control">
                                    <option value="">اختر الصنف...</option>
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->id }}" data-unit="{{ $prod->unit }}" data-waste="{{ $prod->acceptable_waste_pct }}" {{ $i == 0 && $loop->first ? 'selected' : '' }}>
                                            {{ $prod->name }} ({{ $prod->unit }}) @if($prod->barcode) [{{ $prod->barcode }}] @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-3 erp-form-group mb-0">
                                <label class="erp-form-label">الكمية الصافية المقننة</label>
                                <input type="number" step="0.01" min="0" name="items[{{ $i }}][planned_qty]" value="{{ $i == 0 ? '50' : '' }}" class="planned-qty-input erp-form-control font-mono" placeholder="0.00" />
                            </div>
                            <div class="md:col-span-3 erp-form-group mb-0">
                                <label class="erp-form-label">هالك التحضير والأمان (Buffer)</label>
                                <input type="number" step="0.01" min="0" name="items[{{ $i }}][buffer_qty]" value="{{ $i == 0 ? '5' : '' }}" class="buffer-qty-input erp-form-control font-mono" placeholder="0.00" />
                            </div>
                            <div class="md:col-span-1 text-center pt-5">
                                <button type="button" class="btn-remove-row text-slate-400 hover:text-rose-500 p-1.5 transition" title="حذف السطر">
                                    <x-icon name="trash" size="16" />
                                </button>
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="truck" size="18" />
                        <span>تأكيد الصرف والتحميل لسيارة المندوب بنظام FEFO / FIFO</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
