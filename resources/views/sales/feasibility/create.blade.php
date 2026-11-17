@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إعداد دراسة جدوى مالية وتقدير تكاليف مناقصة" :breadcrumbs="['المبيعات والمناقصات' => route('sales.hospitals.index'), 'دراسات الجدوى' => route('sales.feasibility.index'), 'دراسة جديدة' => route('sales.feasibility.create')]">
        <x-slot:actions>
            <a href="{{ route('sales.feasibility.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للدراسات</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" dir="rtl">
    <div class="erp-card p-6">
        <div class="border-b border-slate-100 pb-4 mb-5 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <x-icon name="calculator" size="22" class="text-sky-600" />
                <span>نموذج التكاليف التقديرية وهامش المساهمة (Feasibility Study)</span>
            </h3>
        </div>

        <form action="{{ route('sales.feasibility.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-label">اسم المستشفى / الجهة المطروحة <span class="text-rose-500">*</span></label>
                    <input type="text" name="hospital_name" class="erp-input" placeholder="مستشفى التأمين الصحي الجديد..." required />
                </div>
                <div>
                    <label class="erp-label">المناقصة المرتبطة (إن وجدت)</label>
                    <select name="tender_id" class="erp-input">
                        <option value="">-- كراسة شروط مستقلة --</option>
                        @foreach($tenders as $t)
                            <option value="{{ $t->id }}">{{ $t->title }} ({{ $t->entity_name }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-label">مدة المشروع التقديرية (شهور) <span class="text-rose-500">*</span></label>
                    <input type="number" name="planned_duration_months" class="erp-input font-numeric" value="12" required />
                </div>
                <div>
                    <label class="erp-label">إجمالي الوجبات المتوقع توريدها <span class="text-rose-500">*</span></label>
                    <input type="number" name="expected_total_meals" class="erp-input font-numeric" placeholder="مثال: 180000" required />
                </div>
                <div>
                    <label class="erp-label">سعر البيع المستهدف للوجبة (ج.م) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="target_selling_price_per_meal" class="erp-input font-numeric" placeholder="مثال: 85.00" required />
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h4 class="font-bold text-slate-800 text-sm mb-3">عناصر التكلفة التشغيلية التقديرية:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="erp-label">تكلفة خامات الأغذية المقدرة للوجبة الواحدة (ج.م) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="food_cost_per_meal" class="erp-input font-numeric" placeholder="مثال: 45.00" required />
                    </div>
                    <div>
                        <label class="erp-label">تكلفة العمالة والشيفات الشهرية بالموقع (ج.م) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="labor_monthly_cost" class="erp-input font-numeric" placeholder="مثال: 50000.00" required />
                    </div>
                    <div>
                        <label class="erp-label">تكلفة النقل ولوجستيات السيارات الشهرية (ج.م) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="transport_monthly_cost" class="erp-input font-numeric" placeholder="مثال: 15000.00" required />
                    </div>
                    <div>
                        <label class="erp-label">تكلفة الخبز ومواد التغليف الشهرية (ج.م) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="consumables_monthly_cost" class="erp-input font-numeric" placeholder="مثال: 20000.00" required />
                    </div>
                    <div>
                        <label class="erp-label">المصروفات التشغيلية والمفاجآت الشهرية (ج.م) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="operating_monthly_cost" class="erp-input font-numeric" placeholder="مثال: 10000.00" required />
                    </div>
                </div>
            </div>

            <div>
                <label class="erp-label">ملاحظات دراسة الجدوى والشروط الفنية</label>
                <textarea name="notes" class="erp-input" rows="2" placeholder="ملاحظات حول سعة المطبخ، شروط كراسة المواصفات، التأمين الابتدائي..."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="{{ route('sales.feasibility.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check-circle" size="18" />
                    <span>حساب الجدوى واعتماد الدراسة</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
