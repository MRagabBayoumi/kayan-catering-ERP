@extends('layouts.app')

@section('title', 'إضافة تعاقد مستشفى جديد')

@section('control_panel')
    <x-control-panel title="إضافة تعاقد مستشفى جديد (إدارة المبيعات)" :breadcrumbs="['المبيعات والمناقصات' => route('sales.hospitals.index'), 'المستشفيات والتعاقدات' => route('sales.hospitals.index'), 'إضافة تعاقد' => '#']">
        <x-slot:actions>
            <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمستشفيات</span>
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
                <x-icon name="hospital" size="18" class="text-[var(--primary)]" />
                <span>بيانات المستشفى وتأسيس مخزن الموقع الفرعي تلقائياً</span>
            </h2>
        </div>
        <div class="erp-card-body">
            <form action="{{ route('sales.hospitals.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>اسم المستشفى / الجهة</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="name" class="erp-form-control" placeholder="مثال: مستشفى إمبابة العام" required value="{{ old('name') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>رقم العقد / أمر التوريد</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="contract_number" class="erp-form-control font-mono" placeholder="مثال: CONTR-GIZA-2026-09" required value="{{ old('contract_number') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">مسؤول الموقع / الاتصال</label>
                        <input type="text" name="contact_person" class="erp-form-control" placeholder="اسم مدير التغذية أو المستشفى" value="{{ old('contact_person') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">رقم الهاتف</label>
                        <input type="text" name="phone" class="erp-form-control font-mono" placeholder="01xxxxxxxxx" value="{{ old('phone') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">العنوان والموقع الجغرافي</label>
                        <input type="text" name="address" class="erp-form-control" placeholder="العنوان بالتفصيل" value="{{ old('address') }}">
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>السعة السريرية اليومية (عدد النزلاء)</span>
                            <span class="required">*</span>
                        </label>
                        <input type="number" name="daily_patient_capacity" class="erp-form-control font-mono" placeholder="مثال: 500" required value="{{ old('daily_patient_capacity', 350) }}">
                    </div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-700 pt-5">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                        <x-icon name="dollar-sign" size="16" class="text-emerald-600" />
                        <span>أسعار الوجبات المعتمدة بالعقد (سعر البيع للجهة)</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label">سعر وجبة الإفطار (ج.م)</label>
                            <input type="number" step="0.01" name="meal_price_breakfast" class="erp-form-control font-mono font-bold" required value="{{ old('meal_price_breakfast', 25) }}">
                        </div>
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label">سعر وجبة الغداء (ج.م)</label>
                            <input type="number" step="0.01" name="meal_price_lunch" class="erp-form-control font-mono font-bold text-emerald-600" required value="{{ old('meal_price_lunch', 85) }}">
                        </div>
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label">سعر وجبة العشاء (ج.م)</label>
                            <input type="number" step="0.01" name="meal_price_dinner" class="erp-form-control font-mono font-bold" required value="{{ old('meal_price_dinner', 35) }}">
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-700 pt-5">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
                        <x-icon name="calendar" size="16" class="text-primary" />
                        <span>فترة سريان العقد</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label">تاريخ بدء العقد</label>
                            <input type="date" name="contract_start_date" class="erp-form-control font-mono" required value="{{ old('contract_start_date', date('Y-01-01')) }}">
                        </div>
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label">تاريخ انتهاء العقد</label>
                            <input type="date" name="contract_end_date" class="erp-form-control font-mono" required value="{{ old('contract_end_date', date('Y-12-31')) }}">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-5 border-t border-slate-200 dark:border-slate-700">
                    <a href="{{ route('sales.hospitals.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check" size="16" />
                        <span>تأكيد التعاقد وتأسيس المخزن</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
