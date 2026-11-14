@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إنشاء طلب شراء احتياج جديد" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'طلبات الشراء' => route('procurement.orders.index'), 'طلب جديد' => '#']">
        <x-slot:actions>
            <button type="submit" form="procurementOrderForm" class="erp-btn erp-btn-primary">
                <x-icon name="save" size="16" />
                <span>حفظ ورفع للاعتماد</span>
            </button>
            <a href="{{ route('procurement.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-left" size="16" />
                <span>إلغاء وعودة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-4xl mx-auto" dir="rtl">
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="plus" size="16" class="text-[var(--primary)]" />
                <span>بيانات طلب الشراء الجديد</span>
            </div>
        </div>
        <div class="erp-card-body">
            <form id="procurementOrderForm" method="POST" action="{{ route('procurement.orders.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[var(--text-main)] mb-1">رقم طلب الشراء *</label>
                        <input type="text" name="order_number" class="erp-input w-full" value="PO-{{ date('Ymd') }}-{{ rand(100,999) }}" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--text-main)] mb-1">تاريخ الطلب *</label>
                        <input type="date" name="order_date" class="erp-input w-full" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--text-main)] mb-1">إجمالي التكلفة التقديرية (ج.م) *</label>
                        <input type="number" step="0.01" name="total_amount" class="erp-input w-full" placeholder="0.00" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--text-main)] mb-1">حالة الطلب *</label>
                        <select name="status" class="erp-input w-full">
                            <option value="pending">قيد جمع عروض الأسعار</option>
                            <option value="approved">معتمد وموجه للتوريد</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[var(--text-main)] mb-1">ملاحظات والأصناف المطلوبة (لحوم، خضروات، بقالة، كايزر...)</label>
                    <textarea name="notes" rows="4" class="erp-input w-full" placeholder="اكتب تفاصيل الأصناف وكمياتها المطلوبة طبقاً لنواقص المخازن أو المقنن..."></textarea>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
