@extends('layouts.app')

@section('title', 'تسجيل جلسة جرد أعمى للمخزن')

@section('control_panel')
    <x-control-panel title="تسجيل جلسة جرد أعمى للمخزن (Blind Audit)" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'جلسات الجرد' => route('warehouse.audits.index'), 'جلسة جديدة' => '#']">
        <x-slot:actions>
            <a href="{{ route('warehouse.audits.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع للقائمة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <form action="{{ route('warehouse.audits.store') }}" method="POST" class="space-y-5">
        @csrf

        <div class="erp-card">
            <div class="erp-card-header">
                <h2 class="erp-card-title">
                    <x-icon name="clipboard-check" size="18" class="text-[var(--primary)]" />
                    <span>بيانات جلسة الجرد الفعلي للمخزن</span>
                </h2>
                <span class="text-xs text-slate-500 font-medium">خاص بأمين المخزن: قم بعد الأصناف فعلياً على الرف وإدخال الكمية مباشرة</span>
            </div>
            <div class="erp-card-body space-y-4">
                <div class="erp-form-group md:w-1/2 mb-0">
                    <label class="erp-form-label">
                        <span>المخزن المراد جرده</span>
                        <span class="required">*</span>
                    </label>
                    <select name="warehouse_id" class="erp-form-control" required>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Items Blind Table -->
                <div class="erp-table-responsive mt-4">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الصنف</th>
                                <th>وحدة القياس</th>
                                <th class="w-48 text-center">الكمية المجرودة فعلياً على الرف *</th>
                                <th>ملاحظات أمين المخزن (إن وجدت)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $idx => $prod)
                                <tr>
                                    <td class="font-mono text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="font-bold">
                                        {{ $prod->name }}
                                        <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                                    </td>
                                    <td class="text-slate-500 font-medium">{{ $prod->unit }}</td>
                                    <td>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            name="items[{{ $idx }}][physical_count]" 
                                            required 
                                            class="erp-form-control font-mono font-bold text-center"
                                            placeholder="اكتب ما تم عده..."
                                        />
                                    </td>
                                    <td>
                                        <input 
                                            type="text" 
                                            name="items[{{ $idx }}][discrepancy_reason]" 
                                            class="erp-form-control text-xs"
                                            placeholder="أي تلف أو كسر..."
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="erp-form-group pt-2">
                    <label class="erp-form-label">ملاحظات عامة على الجلسة</label>
                    <textarea name="notes" rows="2" class="erp-form-control" placeholder="أي تفاصيل عن حالة المخزن أو ظروف الجرد..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="send" size="16" />
                        <span>إرسال محضر الجرد الأعمى للمحاسب للمطابقة</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
