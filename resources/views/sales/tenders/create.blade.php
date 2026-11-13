@extends('layouts.app')

@section('title', 'تسجيل مناقصة جديدة')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">تسجيل مناقصة جديدة (إدارة المبيعات)</h1>
            <p class="text-xs text-slate-500 mt-1">إدخال بيانات كراسة الشروط ومواعيد المظاريف والضمانات</p>
        </div>
        <a href="{{ route('sales.tenders.index') }}" class="erp-btn erp-btn-secondary">
            <span>العودة للمناقصات</span>
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
        <form action="{{ route('sales.tenders.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="erp-form-label">عنوان المناقصة</label>
                    <input type="text" name="title" class="erp-form-control" placeholder="مثال: مناقصة تقديم الوجبات الغذائية المطهية لمستشفيات مديرية الصحة" required value="{{ old('title') }}">
                </div>
                <div>
                    <label class="erp-form-label">الجهة صاحبة المناقصة</label>
                    <input type="text" name="entity_name" class="erp-form-control" placeholder="مثال: مديرية الشؤون الصحية بالجيزة" required value="{{ old('entity_name') }}">
                </div>
                <div>
                    <label class="erp-form-label">المستشفى المرتبط (إن وجد)</label>
                    <select name="hospital_id" class="erp-form-control">
                        <option value="">-- اختياري --</option>
                        @foreach($hospitals as $hosp)
                            <option value="{{ $hosp->id }}">{{ $hosp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">تاريخ الإعلان في الجريدة الرسمية</label>
                    <input type="date" name="announcement_date" class="erp-form-control" required value="{{ old('announcement_date', date('Y-m-d')) }}">
                </div>
                <div>
                    <label class="erp-form-label">موعد جلسة فتح المظاريف الفنية والمالية</label>
                    <input type="date" name="envelope_opening_date" class="erp-form-control" value="{{ old('envelope_opening_date') }}">
                </div>
                <div>
                    <label class="erp-form-label">قيمة التأمين الابتدائي (خطاب ضمان ج.م)</label>
                    <input type="number" step="0.01" name="bid_bond_amount" class="erp-form-control" placeholder="مثلاً: 50000" value="{{ old('bid_bond_amount', 0) }}">
                </div>
                <div>
                    <label class="erp-form-label">قيمة التأمين النهائي (5% ج.م)</label>
                    <input type="number" step="0.01" name="final_guarantee_amount" class="erp-form-control" placeholder="مثلاً: 150000" value="{{ old('final_guarantee_amount', 0) }}">
                </div>
                <div>
                    <label class="erp-form-label">ثمن شراء كراسة الشروط (ج.م)</label>
                    <input type="number" step="0.01" name="booklet_cost" class="erp-form-control" placeholder="مثلاً: 1500" value="{{ old('booklet_cost', 0) }}">
                </div>
                <div>
                    <label class="erp-form-label">حالة المناقصة الحالية</label>
                    <select name="status" class="erp-form-control" required>
                        <option value="under_study">تحت الدراسة الفنية والتسعير</option>
                        <option value="submitted">تم شراء الكراسة وتقديم العطاء</option>
                        <option value="awarded">تمت الترسية والفوز بالمناقصة</option>
                        <option value="lost">لم يتم الفوز بالعطاء</option>
                        <option value="archived">مؤرشفة</option>
                    </select>
                </div>
                <div class="md:col-span-2">
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
@endsection
