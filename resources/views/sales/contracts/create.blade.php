@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تفعيل وصياغة عقد مستشفى جديد" :breadcrumbs="['المبيعات والمناقصات' => route('sales.hospitals.index'), 'العقود والتعاقدات' => route('sales.contracts.index'), 'تفعيل عقد' => route('sales.contracts.create')]">
        <x-slot:actions>
            <a href="{{ route('sales.contracts.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة العقود</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" dir="rtl">
    <div class="erp-card p-6">
        <div class="border-b border-slate-100 pb-4 mb-5 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <x-icon name="file-plus" size="22" class="text-sky-600" />
                <span>بيانات العقد والربط المالي بمركز التكلفة</span>
            </h3>
        </div>

        <form action="{{ route('sales.contracts.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-label">المستشفى المتعاقد <span class="text-rose-500">*</span></label>
                    <select name="hospital_id" class="erp-input" required>
                        <option value="">-- اختر المستشفى --</option>
                        @foreach($hospitals as $hosp)
                            <option value="{{ $hosp->id }}">{{ $hosp->name }} (سعة: {{ $hosp->daily_patient_capacity }} نزيل)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">المناقصة المرتبطة (إن وجدت)</label>
                    <select name="tender_id" class="erp-input">
                        <option value="">-- بدون مناقصة / أمر إسناد مباشر --</option>
                        @foreach($tenders as $t)
                            <option value="{{ $t->id }}">{{ $t->title }} ({{ $t->entity_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">رقم العقد الرسمي <span class="text-rose-500">*</span></label>
                    <input type="text" name="contract_number" class="erp-input font-mono" value="CTR-{{ date('Y') }}-{{ rand(1000, 9999) }}" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-label">مسمى العقد التجاري <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" class="erp-input" placeholder="عقد توريد وإعاشة وتغذية المرضى..." required />
                </div>
                <div>
                    <label class="erp-label">إجمالي القيمة التقديرية للعقد (ج.م) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="contract_value" class="erp-input font-numeric" placeholder="مثال: 5000000.00" required />
                </div>
            </div>

            <!-- Section 2: Dates & Billing -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="erp-label">تاريخ بدء التوريد <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date" class="erp-input font-numeric" value="{{ date('Y-m-d') }}" required />
                </div>
                <div>
                    <label class="erp-label">تاريخ نهاية العقد <span class="text-rose-500">*</span></label>
                    <input type="date" name="end_date" class="erp-input font-numeric" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required />
                </div>
                <div>
                    <label class="erp-label">دورة الفوترة والمطالبة <span class="text-rose-500">*</span></label>
                    <select name="billing_cycle" class="erp-input" required>
                        <option value="monthly">شهرياً (المعتاد حكومياً)</option>
                        <option value="weekly">أسبوعياً</option>
                        <option value="daily">يومياً حسب إشارة التعداد</option>
                    </select>
                </div>
                <div>
                    <label class="erp-label">فترة السداد والائتمان (أيام) <span class="text-rose-500">*</span></label>
                    <input type="number" name="payment_terms_days" class="erp-input font-numeric" value="30" required />
                </div>
            </div>

            <!-- Section 3: Meal Pricing Matrix -->
            <div class="border-t border-slate-100 pt-5">
                <h4 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                    <x-icon name="dollar-sign" size="18" class="text-emerald-600" />
                    <span>مصفوفة أسعار الوجبات التعاقدية (Meal Pricing Matrix)</span>
                </h4>
                <p class="text-xs text-slate-500 mb-4">حدد السعر المعتمد لكل نوع وجبة؛ سيتم احتساب إيرادات وربحية المستشفى ومطالباته الشهرية بناءً على هذه الأسعار.</p>

                <div class="space-y-3">
                    @foreach($mealTypes as $idx => $mt)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 items-center">
                            <div>
                                <input type="hidden" name="meal_prices[{{ $idx }}][meal_type_id]" value="{{ $mt->id }}" />
                                <strong class="text-sm text-slate-800">{{ $mt->name }}</strong>
                                <span class="text-xs text-slate-400 block">{{ $mt->code }}</span>
                            </div>
                            <div>
                                <label class="text-xs text-slate-600 block mb-1">سعر بيع الوجبة للمستشفى (ج.م) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" name="meal_prices[{{ $idx }}][price_per_meal]" class="erp-input font-numeric" value="{{ str_contains($mt->name, 'لحوم') ? '120.00' : (str_contains($mt->name, 'دواجن') ? '95.00' : '45.00') }}" required />
                            </div>
                            <div>
                                <label class="text-xs text-slate-600 block mb-1">الحد الأدنى للوجبات اليومية</label>
                                <input type="number" name="meal_prices[{{ $idx }}][minimum_daily_quantity]" class="erp-input font-numeric" value="0" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 4: Terms -->
            <div class="border-t border-slate-100 pt-5">
                <label class="erp-label">الشروط الجزائية وملاحظات العقد</label>
                <textarea name="penalty_terms" class="erp-input" rows="2" placeholder="غرامات التأخير، شروط الجودة، مواعيد الاستلام المحددة من إدارة المستشفى..."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <a href="{{ route('sales.contracts.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="18" />
                    <span>تفعيل العقد واعتماد الأسعار وربط مركز التكلفة</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
