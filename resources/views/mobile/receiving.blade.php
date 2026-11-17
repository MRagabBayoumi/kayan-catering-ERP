@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="فحص واستلام التوريد لمستشفى: {{ $dispatch->hospital->name }}" :breadcrumbs="['بوابة الميدان' => route('mobile.index'), 'رحلات التوريد' => route('mobile.driver.trips'), 'فحص واستلام التوريد' => '#']">
        <x-slot:actions>
            <a href="{{ route('mobile.driver.trips') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>إلغاء وعودة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-5" dir="rtl">
    <!-- Header Card -->
    <div class="erp-card p-5 bg-gradient-to-l from-slate-50 to-emerald-50/50 border border-emerald-100">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-500 font-mono">إذن صرف: {{ $dispatch->dispatch_number }}</span>
                <h3 class="text-lg font-bold text-slate-900 mt-0.5">{{ $dispatch->hospital->name }}</h3>
                <span class="text-xs text-slate-600 mt-1 block">
                    السائق المسلّم: <strong>{{ $dispatch->driver->name ?? 'مندوب الشركة' }}</strong>
                </span>
            </div>
            <span class="erp-badge erp-badge-warning font-semibold">بانتظار استلام أمين الموقع</span>
        </div>
    </div>

    <!-- Receiving Form -->
    <form action="{{ route('mobile.receiving.submit', $dispatch->id) }}" method="POST" class="space-y-4">
        @csrf

        <div class="erp-card p-5 space-y-4">
            <h4 class="font-bold text-slate-800 text-sm border-b border-slate-100 pb-2">بيانات مستلم الموقع</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-label">اسم المستلم الرسمي بالمستشفى <span class="text-rose-500">*</span></label>
                    <input type="text" name="receiving_officer_name" class="erp-input" placeholder="اسم أمين المخزن أو مشرف التغذية..." value="{{ auth()->user()->name }}" required />
                </div>
                <div>
                    <label class="erp-label">ملاحظات الاستلام والفحص</label>
                    <input type="text" name="notes" class="erp-input" placeholder="تمت مطابقة الأوزان وتاريخ الصلاحية..." />
                </div>
            </div>
        </div>

        <!-- Items Inspection Cards -->
        <div class="erp-card overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h4 class="font-bold text-slate-800 text-sm">الأصناف المحملة والفحص الفعلي</h4>
                <span class="text-xs text-slate-500">{{ $dispatch->items->count() }} صنف</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($dispatch->items as $idx => $item)
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="font-bold text-slate-800">{{ $item->product->name }}</h5>
                                <span class="text-xs text-slate-400 font-mono">{{ $item->product->sku ?? '-' }}</span>
                            </div>
                            <div class="text-left">
                                <span class="text-xs text-slate-500 block">الكمية المحملة</span>
                                <strong class="font-numeric text-base text-sky-700">{{ number_format($item->total_dispatched_qty, 2) }} {{ $item->product->unit ?? 'كجم' }}</strong>
                            </div>
                        </div>

                        <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}" />
                        <input type="hidden" name="items[{{ $idx }}][dispatched_qty]" value="{{ $item->total_dispatched_qty }}" />

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 p-3 rounded-lg">
                            <div>
                                <label class="erp-label text-xs">المستلم الفعلي للمخزن</label>
                                <input type="number" step="0.01" name="items[{{ $idx }}][actual_delivered_qty]" class="erp-input font-numeric" value="{{ $item->planned_qty }}" required />
                            </div>
                            <div>
                                <label class="erp-label text-xs">مرتجع مع السائق (Buffer)</label>
                                <input type="number" step="0.01" name="items[{{ $idx }}][returned_qty]" class="erp-input font-numeric" value="{{ $item->buffer_qty ?? 0 }}" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label class="erp-label text-xs">كمية مرفوضة (تالف/غير مطابق)</label>
                                <input type="number" step="0.01" name="items[{{ $idx }}][rejected_qty]" class="erp-input font-numeric" value="0.00" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="erp-btn erp-btn-primary w-full justify-center py-3 text-base shadow-md">
                <x-icon name="check" size="20" />
                <span>تأكيد استلام التوريد وتحويله لمخزن الموقع (POD)</span>
            </button>
        </div>
    </form>
</div>
@endsection
