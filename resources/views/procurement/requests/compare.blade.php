@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="مقارنة عروض الأسعار واختيار المورد الأفضل" :breadcrumbs="['المشتريات والتوريدات' => route('procurement.orders.index'), 'طلبات الشراء' => route('procurement.requests.index'), $pr->pr_number => route('procurement.requests.show', $pr->id), 'مقارنة الأسعار' => '#']">
        <x-slot:actions>
            <a href="{{ route('procurement.requests.show', $pr->id) }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لطلب الشراء</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6 max-w-6xl mx-auto" dir="rtl">
    <!-- Analysis Summary Header -->
    <div class="erp-card p-5 bg-gradient-to-l from-slate-50 to-sky-50/50 border border-sky-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-xs text-slate-500 font-mono">طلب شراء معتمد: {{ $pr->pr_number }}</div>
            <h2 class="text-lg font-bold text-slate-800 mt-1">مقارنة عروض الموردين المعتمدين واختيار الأنسب</h2>
            <p class="text-xs text-slate-600 mt-1">يتم احتساب السعر تلقائياً بناء على سجل تسعير الموردين وفواتير التوريد السابقة بالـ ERP.</p>
        </div>
        <div class="text-left">
            <span class="text-xs text-slate-500 block">عدد الموردين المتنافسين</span>
            <span class="text-2xl font-black font-numeric text-sky-700">{{ count($supplierRankings) }}</span>
        </div>
    </div>

    <!-- Supplier Ranking & Selection Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($supplierRankings as $rank => $rankData)
            @php
                $sup = $rankData['supplier'];
                $isBest = ($rank === 0);
            @endphp
            <div class="erp-card p-5 relative {{ $isBest ? 'border-2 border-emerald-500 bg-emerald-50/20' : '' }}">
                @if($isBest)
                    <div class="absolute -top-3 right-4 bg-emerald-600 text-white text-[11px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 shadow-sm">
                        <x-icon name="award" size="12" />
                        <span>العرض الأوفر سعراً</span>
                    </div>
                @endif

                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="font-bold text-slate-800">{{ $sup->name }}</h4>
                        <span class="text-xs text-slate-500">{{ $sup->code }} • {{ $sup->payment_terms }}</span>
                    </div>
                </div>

                <div class="py-4 space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">إجمالي قيمة التوريد:</span>
                        <strong class="font-numeric text-lg text-slate-900">{{ number_format($rankData['total_amount'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>فترة السماح والسداد:</span>
                        <span>{{ $sup->payment_terms }}</span>
                    </div>
                </div>

                <form action="{{ route('procurement.requests.convert', $pr->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="supplier_id" value="{{ $sup->id }}" />
                    <button type="submit" class="erp-btn {{ $isBest ? 'erp-btn-primary' : 'erp-btn-secondary' }} w-full justify-center">
                        <x-icon name="check-circle" size="16" />
                        <span>اختيار وإصدار أمر الشراء (PO)</span>
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <!-- Detailed Items Price Matrix -->
    <div class="erp-card overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 text-sm">مصفوفة مقارنة أسعار الأصناف الفردية</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>الصنف المطلوب</th>
                        <th>الكمية المطلوبة</th>
                        @foreach($supplierRankings as $rankData)
                            <th>{{ $rankData['supplier']->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($itemsComparison as $comp)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-800">{{ $comp['item']->product->name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $comp['item']->product->sku }}</div>
                            </td>
                            <td class="font-numeric font-bold">
                                {{ number_format($comp['item']->quantity_requested, 2) }} {{ $comp['item']->unit }}
                            </td>
                            @foreach($supplierRankings as $rankData)
                                @php
                                    $supId = $rankData['supplier']->id;
                                    $offer = $comp['offers'][$supId] ?? null;
                                    $isLowest = ($offer && $offer['unit_price'] == $comp['lowest_price']);
                                @endphp
                                <td class="{{ $isLowest ? 'bg-emerald-50/60 font-bold text-emerald-800' : '' }}">
                                    @if($offer)
                                        <div class="font-numeric">{{ number_format($offer['unit_price'], 2) }} ج.م</div>
                                        <div class="text-[11px] text-slate-400 font-numeric">إجمالي: {{ number_format($offer['line_total'], 2) }}</div>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
