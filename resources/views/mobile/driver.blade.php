@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="رحلات التوريد وعهدة السائق الميدانية" :breadcrumbs="['بوابة الميدان' => route('mobile.index'), 'رحلات التوريد' => route('mobile.driver.trips')]">
        <x-slot:actions>
            <a href="{{ route('mobile.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للبوابة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-4" dir="rtl">
    @if(session('message'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm">الأذون المحملة بعهدة السيارة</h3>
        <span class="text-xs text-slate-500 font-numeric">{{ $trips->total() }} إذن مسجل</span>
    </div>

    @forelse($trips as $dispatch)
        <div class="erp-card p-5 space-y-3">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-mono text-slate-400 block">{{ $dispatch->dispatch_number }}</span>
                    <h4 class="font-bold text-slate-900 text-base mt-0.5">{{ $dispatch->hospital->name }}</h4>
                    <span class="text-xs text-slate-500 mt-1 block">
                        السيارة / عهدة: {{ $dispatch->transitWarehouse->name ?? 'سيارة السائق' }}
                    </span>
                </div>
                <div>
                    @if($dispatch->status === 'in_transit')
                        <span class="erp-badge erp-badge-warning">في الطريق للتسليم</span>
                    @elseif($dispatch->status === 'delivered')
                        <span class="erp-badge erp-badge-success">تم الاستلام بالموقع</span>
                    @elseif($dispatch->status === 'settled')
                        <span class="erp-badge erp-badge-neutral">تمت التسوية والمطابقة</span>
                    @else
                        <span class="erp-badge erp-badge-neutral">{{ $dispatch->status }}</span>
                    @endif
                </div>
            </div>

            <!-- Items count and quantity preview -->
            <div class="bg-slate-50 rounded-lg p-3 text-xs space-y-1">
                <div class="flex justify-between text-slate-600">
                    <span>عدد الأصناف المحملة:</span>
                    <strong class="font-numeric">{{ $dispatch->items->count() }} صنف</strong>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>تاريخ التحميل والصرف:</span>
                    <span class="font-numeric">{{ $dispatch->dispatch_date?->format('Y-m-d H:i') }}</span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                @if($dispatch->status === 'in_transit')
                    <a href="{{ route('mobile.receiving.show', $dispatch->id) }}" class="erp-btn erp-btn-primary w-full sm:w-auto justify-center">
                        <x-icon name="check-circle" size="16" />
                        <span>تسليم للمستشفى وتأكيد الفحص (POD)</span>
                    </a>
                @elseif($dispatch->deliveryReceipt)
                    <a href="{{ route('mobile.receipt.show', $dispatch->deliveryReceipt->id) }}" class="erp-btn erp-btn-secondary w-full sm:w-auto justify-center">
                        <x-icon name="file-text" size="16" />
                        <span>عرض إشعار التسليم ({{ $dispatch->deliveryReceipt->receipt_number }})</span>
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div class="erp-card p-8 text-center text-slate-400">
            لا توجد أذون صرف أو شحنات جارية مسجلة حالياً
        </div>
    @endforelse

    @if($trips->hasPages())
        <div class="p-2">
            {{ $trips->links() }}
        </div>
    @endif
</div>
@endsection
