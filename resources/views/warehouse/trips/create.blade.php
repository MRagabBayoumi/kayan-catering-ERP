@extends('layouts.app')

@section('title', 'تسجيل رحلة وتفويل سيارة - إدارة المخازن')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" dir="rtl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">
                تسجيل رحلة وتفويل سيارة وتوزيع التكلفة
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                تصفية عهدة النقل وتحميل التكلفة آلياً على مراكز تكلفة المواقع المستلمة
            </p>
        </div>
        <a href="{{ route('warehouse.trips.index') }}" class="erp-btn erp-btn-secondary">
            <span>العودة لسجل الرحلات</span>
        </a>
    </div>

    @if($errors->any())
        <div class="erp-alert erp-alert-danger">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="erp-card p-6">
        <form action="{{ route('warehouse.trips.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Trip & Vehicle Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-form-label">رقم لوحة السيارة</label>
                    <input type="text" name="vehicle_plate" class="erp-form-control" placeholder="مثال: د ن ر 8542" required value="{{ old('vehicle_plate') }}">
                </div>
                <div>
                    <label class="erp-form-label">السائق / مندوب التوريد</label>
                    <select name="driver_id" class="erp-form-control">
                        <option value="">-- اختر السائق --</option>
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->id }}" {{ old('driver_id') == $drv->id ? 'selected' : '' }}>
                                {{ $drv->name }} ({{ $drv->phone ?? 'بدون هاتف' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">تاريخ الرحلة والتفويل</label>
                    <input type="date" name="trip_date" class="erp-form-control" required value="{{ old('trip_date', date('Y-m-d')) }}">
                </div>
            </div>

            <!-- Expenses & Petty Cash Settlement -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700">
                <div>
                    <label class="erp-form-label">مصروف تفويل البنزين / السولار (ج.م)</label>
                    <input type="number" step="0.01" name="fuel_expense" class="erp-form-control font-mono font-bold text-base text-amber-600" placeholder="مثلاً: 3000" required value="{{ old('fuel_expense') }}">
                    <p class="text-[11px] text-slate-400 mt-1">سيتم قسمته بالتساوي على المواقع المحددة</p>
                </div>
                <div>
                    <label class="erp-form-label">كارتات ورسوم بوابات (اختياري)</label>
                    <input type="number" step="0.01" name="toll_and_other_expense" class="erp-form-control font-mono" placeholder="0.00" value="{{ old('toll_and_other_expense', 0) }}">
                </div>
                <div>
                    <label class="erp-form-label">تصفية من عهدة / خزينة المخزن</label>
                    <select name="treasury_id" class="erp-form-control text-xs">
                        <option value="">-- بدون خصم مباشر من خزينة --</option>
                        @foreach($treasuries as $t)
                            <option value="{{ $t->id }}" {{ old('treasury_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} (رصيد: {{ number_format($t->balance, 2) }} ج.م)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">توليد قيد تصفية عهدة تلقائي</p>
                </div>
            </div>

            <!-- Destination Hospitals -->
            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-100 mb-1">
                    المستشفيات والمواقع المستفيدة من الرحلة (حدد المواقع لتحميل النقل عليها):
                </label>
                <p class="text-xs text-slate-500 mb-3">
                    المبلغ سيُقسم بالتساوي تلقائياً كمركز تكلفة على كل موقع تم اختياره
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($hospitals as $hosp)
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 border-slate-200 dark:border-slate-700">
                            <input type="checkbox" name="hospital_ids[]" value="{{ $hosp->id }}" class="w-4 h-4 text-primary rounded" {{ in_array($hosp->id, old('hospital_ids', [])) ? 'checked' : '' }}>
                            <div class="mr-3">
                                <span class="block text-xs font-bold text-slate-800 dark:text-slate-100">{{ $hosp->name }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $hosp->address ?? 'الموقع الرئيسي' }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="erp-form-label">ملاحظات وخط سير الرحلة</label>
                <textarea name="notes" rows="2" class="erp-form-control text-xs" placeholder="مثال: توصيل طلبيات لحوم وأغذية جافة للمستشفيات">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('warehouse.trips.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="16" />
                    <span>حفظ الرحلة وتوزيع التكلفة وتصفية العهدة</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
