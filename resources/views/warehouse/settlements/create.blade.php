@extends('layouts.app')

@section('title', 'اعتماد تسوية رحلة توريد المندوب')

@section('control_panel')
    <x-control-panel title="اعتماد تسوية رحلة توريد المندوب: {{ $dispatch->driver->name ?? '-' }}" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'تسويات المناديب' => route('warehouse.settlements.index'), 'اعتماد التسوية' => '#']">
        <x-slot:actions>
            <a href="{{ route('warehouse.settlements.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع للتسويات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-primary font-numeric px-2.5 py-1 text-xs">
                <span>إذن تحميل:</span>
                <strong>{{ $dispatch->dispatch_number }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @if(session('error'))
        <div class="erp-alert erp-alert-danger">
            <x-icon name="alert-circle" size="16" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('warehouse.settlements.store') }}" method="POST" class="space-y-5">
        @csrf
        <input type="hidden" name="dispatch_order_id" value="{{ $dispatch->id }}">

        <div class="erp-card">
            <div class="erp-card-header">
                <h2 class="erp-card-title">
                    <x-icon name="file-text" size="18" class="text-[var(--primary)]" />
                    <span>بيانات إشعار استلام المستشفى</span>
                </h2>
                <span class="text-xs text-slate-500 font-medium">المستشفى: {{ $dispatch->hospital->name ?? '-' }}</span>
            </div>
            <div class="erp-card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">
                            <span>اسم مسؤول الاستلام بالمستشفى الموقع على المحضر</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="receiving_officer_name" required class="erp-form-control" placeholder="مثلاً: د. أحمد فؤاد (مشرف التغذية)" value="{{ old('receiving_officer_name') }}" />
                    </div>
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">ملاحظات المحاسب على التسوية</label>
                        <input type="text" name="notes" class="erp-form-control" placeholder="أي فوارق أو بنود تم استعاضتها..." value="{{ old('notes') }}" />
                    </div>
                </div>

                <!-- Items Clearance Table -->
                <div class="erp-table-responsive mt-5">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>الصنف</th>
                                <th class="text-center">المحمل بالسيارة</th>
                                <th class="text-center w-36">المسلم للمستشفى فعلياً *</th>
                                <th class="text-center w-36">المرتجع للمخزن *</th>
                                <th class="text-center w-32">زيادة طلبتها المستشفى</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dispatch->items as $idx => $item)
                                <tr>
                                    <td class="font-bold">
                                        {{ $item->product->name ?? '-' }}
                                        <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                        <input type="hidden" name="items[{{ $idx }}][dispatched_qty]" value="{{ $item->total_dispatched_qty }}">
                                    </td>
                                    <td class="font-mono text-center font-extrabold text-[var(--primary)]">
                                        {{ number_format($item->total_dispatched_qty, 2) }} {{ $item->product->unit ?? 'كجم' }}
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            name="items[{{ $idx }}][actual_delivered_qty]" 
                                            value="{{ old("items.$idx.actual_delivered_qty", $item->planned_qty) }}" 
                                            required 
                                            class="erp-form-control font-mono font-bold text-center"
                                        />
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            name="items[{{ $idx }}][returned_qty]" 
                                            value="{{ old("items.$idx.returned_qty", $item->buffer_qty) }}" 
                                            required 
                                            class="erp-form-control font-mono font-bold text-center"
                                        />
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            name="items[{{ $idx }}][extra_requested_qty]" 
                                            value="{{ old("items.$idx.extra_requested_qty", '0') }}" 
                                            class="erp-form-control font-mono text-center"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit" class="erp-btn erp-btn-success">
                        <x-icon name="check-circle" size="18" />
                        <span>اعتماد التسوية وإرجاع البضاعة وتوليد قيد الـ ERP</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
