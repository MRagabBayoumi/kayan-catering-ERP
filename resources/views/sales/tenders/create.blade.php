@extends('layouts.app')

@section('title', 'تسجيل مناقصة جديدة')

@section('control_panel')
    <x-control-panel title="تسجيل مناقصة جديدة (إدارة المبيعات)" :breadcrumbs="['المبيعات والمناقصات' => route('sales.tenders.index'), 'المناقصات' => route('sales.tenders.index'), 'تسجيل مناقصة' => '#']">
        <x-slot:actions>
            <a href="{{ route('sales.tenders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمناقصات</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @if($errors->any())
        <div class="erp-alert erp-alert-danger">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="erp-card">
        <div class="erp-card-header">
            <h2 class="erp-card-title">
                <x-icon name="file-text" size="18" class="text-[var(--primary)]" />
                <span>إدخال بيانات كراسة الشروط ومواعيد المظاريف والضمانات</span>
            </h2>
        </div>
        <div class="erp-card-body">
            <form action="{{ route('sales.tenders.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2 erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>عنوان المناقصة</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="title" class="erp-form-control" placeholder="مثال: مناقصة تقديم الوجبات الغذائية المطهية لمستشفيات مديرية الصحة" required value="{{ old('title') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>الجهة صاحبة المناقصة</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="entity_name" class="erp-form-control" placeholder="مثال: مديرية الشؤون الصحية بالجيزة" required value="{{ old('entity_name') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">المستشفى المرتبط (إن وجد)</label>
                        <select name="hospital_id" class="erp-form-control">
                            <option value="">-- اختياري --</option>
                            @foreach($hospitals as $hosp)
                                <option value="{{ $hosp->id }}">{{ $hosp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>تاريخ الإعلان في الجريدة الرسمية</span>
                            <span class="required">*</span>
                        </label>
                        <input type="date" name="announcement_date" class="erp-form-control font-mono" required value="{{ old('announcement_date', date('Y-m-d')) }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">موعد جلسة فتح المظاريف الفنية والمالية</label>
                        <input type="date" name="envelope_opening_date" class="erp-form-control font-mono" value="{{ old('envelope_opening_date') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">قيمة التأمين الابتدائي (خطاب ضمان ج.م)</label>
                        <input type="number" step="0.01" name="bid_bond_amount" class="erp-form-control font-mono" placeholder="مثلاً: 50000" value="{{ old('bid_bond_amount', 0) }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">قيمة التأمين النهائي (5% ج.م)</label>
                        <input type="number" step="0.01" name="final_guarantee_amount" class="erp-form-control font-mono" placeholder="مثلاً: 150000" value="{{ old('final_guarantee_amount', 0) }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">ثمن شراء كراسة الشروط (ج.م)</label>
                        <input type="number" step="0.01" name="booklet_cost" class="erp-form-control font-mono" placeholder="مثلاً: 1500" value="{{ old('booklet_cost', 0) }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>حالة المناقصة الحالية</span>
                            <span class="required">*</span>
                        </label>
                        <select name="status" class="erp-form-control" required>
                            <option value="under_study">تحت الدراسة الفنية والتسعير</option>
                            <option value="submitted">تم شراء الكراسة وتقديم العطاء</option>
                            <option value="awarded">تمت الترسية والفوز بالمناقصة</option>
                            <option value="lost">لم يتم الفوز بالعطاء</option>
                            <option value="archived">مؤرشفة</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 erp-form-group mb-0">
                        <label class="erp-form-label">ملاحظات وشروط خاصة</label>
                        <textarea name="notes" rows="3" class="erp-form-control" placeholder="شروط التوريد، غرامات التأخير، متطلبات الشهادات الصحية...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="{{ route('sales.tenders.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check" size="16" />
                        <span>حفظ المناقصة</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
