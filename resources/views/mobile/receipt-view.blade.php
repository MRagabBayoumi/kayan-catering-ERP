@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إشعار استلام وتوريد رسمي (Proof of Delivery - POD)" :breadcrumbs="['بوابة الميدان' => route('mobile.index'), 'رحلات التوريد' => route('mobile.driver.trips'), $receipt->receipt_number => '#']">
        <x-slot:actions>
            <a href="{{ route('prints.delivery-receipt', $receipt->id) }}" target="_blank" class="erp-btn erp-btn-primary">
                <x-icon name="printer" size="16" />
                <span>طباعة إشعار التسليم (POD)</span>
            </a>
            <a href="{{ route('mobile.driver.trips') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للرحلات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-success font-semibold px-2.5 py-1 text-xs">
                تم الاستلام والتحويل لمخزن الموقع
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-5" dir="rtl">
    @if(session('message'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- POD Document Box -->
    <div class="erp-card p-6 border-2 border-emerald-500/30">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <span class="text-xs text-slate-500 font-mono">رقم الإشعار الرسمي</span>
                <h3 class="text-xl font-bold font-mono text-emerald-800">{{ $receipt->receipt_number }}</h3>
            </div>
            <div class="text-left">
                <span class="text-xs text-slate-500 block">تاريخ وساعة الاستلام</span>
                <span class="font-numeric font-bold text-slate-800">{{ $receipt->received_at?->format('Y-m-d H:i') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-4 border-b border-slate-100 text-sm">
            <div>
                <span class="text-xs text-slate-500 block">المستشفى / الموقع</span>
                <strong class="text-slate-800">{{ $receipt->hospital->name }}</strong>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">السائق المورّد</span>
                <strong class="text-slate-800">{{ $receipt->driver->name ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">أمين الموقع المستلم</span>
                <strong class="text-emerald-700">{{ $receipt->receiving_officer_name }}</strong>
            </div>
        </div>

        @if($receipt->notes)
            <div class="py-3 text-xs text-slate-600 bg-slate-50 p-3 rounded-lg mt-3">
                ملاحظات الفحص: {{ $receipt->notes }}
            </div>
        @endif

        <!-- Delivered Items Table -->
        <div class="mt-5">
            <h4 class="font-bold text-slate-800 text-sm mb-3">الأصناف المسلّمة فعلياً لمخزن المستشفى</h4>
            <div class="overflow-x-auto">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>الصنف</th>
                            <th>المحمل</th>
                            <th>المستلم بالموقع</th>
                            <th>مرتجع للسائق</th>
                            <th>مرفوض</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receipt->items as $item)
                            <tr>
                                <td class="font-bold text-slate-800">{{ $item->product->name }}</td>
                                <td class="font-numeric">{{ number_format($item->dispatched_qty, 2) }}</td>
                                <td class="font-numeric font-bold text-emerald-700">{{ number_format($item->actual_delivered_qty, 2) }}</td>
                                <td class="font-numeric text-amber-700">{{ number_format($item->returned_qty, 2) }}</td>
                                <td class="font-numeric text-rose-700">{{ number_format($item->rejected_qty, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Electronic Signature & Verification Stamp -->
        <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/70 p-4 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <x-icon name="check-circle" size="20" />
                </div>
                <div>
                    <h5 class="font-bold text-sm text-slate-800">إثبات استلام وتوريد رسمي موثق</h5>
                    <p class="text-xs text-slate-500">تم ترحيل الكميات المستلمة إلى رصيد المخزن الفرعي للموقع بنجاح.</p>
                </div>
            </div>
            <div class="text-center sm:text-left">
                <span class="text-xs text-slate-400 block font-mono">حالة المستند:</span>
                <span class="text-xs font-bold text-emerald-700 font-mono">VERIFIED_AND_TRANSFERRED</span>
            </div>
        </div>
    </div>
</div>
@endsection
