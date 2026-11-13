@extends('layouts.app')

@section('title', 'إضافة تعاقد مستشفى جديد')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">إضافة تعاقد مستشفى جديد (إدارة المبيعات)</h1>
            <p class="text-xs text-slate-500 mt-1">تسجيل بيانات المستشفى وتأسيس مخزن الموقع الفرعي تلقائياً</p>
        </div>
        <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary">
            <span>العودة للمستشفيات</span>
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
        <form action="{{ route('sales.hospitals.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">اسم المستشفى / الجهة</label>
                    <input type="text" name="name" class="erp-form-control" placeholder="مثال: مستشفى إمبابة العام" required value="{{ old('name') }}">
                </div>
                <div>
                    <label class="erp-form-label">رقم العقد / أمر التوريد</label>
                    <input type="text" name="contract_number" class="erp-form-control" placeholder="مثال: CONTR-GIZA-2026-09" required value="{{ old('contract_number') }}">
                </div>
                <div>
                    <label class="erp-form-label">مسؤول الموقع / الاتصال</label>
                    <input type="text" name="contact_person" class="erp-form-control" placeholder="اسم مدير التغذية أو المستشفى" value="{{ old('contact_person') }}">
                </div>
                <div>
                    <label class="erp-form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="erp-form-control" placeholder="01xxxxxxxxx" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="erp-form-label">العنوان والموقع الجغرافي</label>
                    <input type="text" name="address" class="erp-form-control" placeholder="العنوان بالتفصيل" value="{{ old('address') }}">
                </div>
                <div>
                    <label class="erp-form-label">السعة السريرية اليومية (عدد النزلاء)</label>
                    <input type="number" name="daily_patient_capacity" class="erp-form-control" placeholder="مثال: 500" required value="{{ old('daily_patient_capacity', 350) }}">
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-3">أسعار الوجبات المعتمدة بالعقد (سعر البيع للجهة)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="erp-form-label">سعر وجبة الإفطار (ج.م)</label>
                        <input type="number" step="0.01" name="meal_price_breakfast" class="erp-form-control" required value="{{ old('meal_price_breakfast', 25) }}">
                    </div>
                    <div>
                        <label class="erp-form-label">سعر وجبة الغداء (ج.م)</label>
                        <input type="number" step="0.01" name="meal_price_lunch" class="erp-form-control" required value="{{ old('meal_price_lunch', 85) }}">
                    </div>
                    <div>
                        <label class="erp-form-label">سعر وجبة العشاء (ج.م)</label>
                        <input type="number" step="0.01" name="meal_price_dinner" class="erp-form-control" required value="{{ old('meal_price_dinner', 35) }}">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-3">فترة سريان العقد</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="erp-form-label">تاريخ بدء العقد</label>
                        <input type="date" name="contract_start_date" class="erp-form-control" required value="{{ old('contract_start_date', date('Y-01-01')) }}">
                    </div>
                    <div>
                        <label class="erp-form-label">تاريخ انتهاء العقد</label>
                        <input type="date" name="contract_end_date" class="erp-form-control" required value="{{ old('contract_end_date', date('Y-12-31')) }}">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="16" />
                    <span>تأكيد التعاقد وتأسيس المخزن</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
