@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إعداد طلب شراء واحتياج تمويني جديد" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'طلبات الشراء' => route('procurement.requests.index'), 'طلب جديد' => route('procurement.requests.create')]">
        <x-slot:actions>
            <a href="{{ route('procurement.requests.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للقائمة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" dir="rtl">
    <div class="erp-card p-6">
        <div class="border-b border-slate-100 pb-4 mb-5 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <x-icon name="file-plus" size="22" class="text-sky-600" />
                <span>بيانات طلب الشراء والتوريد المخزني</span>
            </h3>
        </div>

        <form action="{{ route('procurement.requests.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="erp-label">المخزن المستلم المستهدف <span class="text-rose-500">*</span></label>
                    <select name="warehouse_id" class="erp-input" required>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label">تاريخ الطلب <span class="text-rose-500">*</span></label>
                    <input type="date" name="request_date" class="erp-input font-numeric" value="{{ date('Y-m-d') }}" required />
                </div>
                <div>
                    <label class="erp-label">مطلوب التوريد قبل <span class="text-rose-500">*</span></label>
                    <input type="date" name="required_by_date" class="erp-input font-numeric" value="{{ date('Y-m-d', strtotime('+3 days')) }}" required />
                </div>
            </div>

            <div>
                <label class="erp-label">سبب الشراء والاحتياج <span class="text-rose-500">*</span></label>
                <input type="text" name="reason" class="erp-input" placeholder="مثال: تغطية احتياج الوجبات التموينية للأسبوع القادم بمستشفى التأمين" required />
            </div>

            <div>
                <label class="erp-label">ملاحظات إضافية</label>
                <textarea name="notes" rows="2" class="erp-input" placeholder="أي شروط أو متطلبات خاصة للتوريد..."></textarea>
            </div>

            <!-- Single Item or Multiple Items in simple grid -->
            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 space-y-4">
                <h4 class="font-bold text-sm text-slate-700">الأصناف المطلوبة</h4>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="erp-label">الصنف المطلوب <span class="text-rose-500">*</span></label>
                        <select name="items[0][product_id]" class="erp-input" required>
                            <option value="">-- اختر الصنف --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="erp-label">الكمية المطلوبة <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="items[0][quantity]" class="erp-input font-numeric" placeholder="100.00" required />
                    </div>
                    <div>
                        <label class="erp-label">الوحدة</label>
                        <input type="text" name="items[0][unit]" class="erp-input" value="kg" required />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('procurement.requests.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="16" />
                    <span>إرسال طلب الشراء للاعتماد</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
