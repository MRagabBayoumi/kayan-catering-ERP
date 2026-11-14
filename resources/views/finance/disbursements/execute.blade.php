@extends('layouts.app')

@section('title', 'تنفيذ صرف إذن مالي')

@section('control_panel')
    <x-control-panel title="تنفيذ صرف إذن مالي (أمين الخزنة)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'أذونات الصرف' => route('finance.disbursements.index'), 'تنفيذ الصرف' => '#']">
        <x-slot:actions>
            <a href="{{ route('finance.disbursements.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لشاشة أذونات الصرف</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning font-numeric px-2.5 py-1 text-xs">
                <span>رقم الإذن:</span>
                <strong>{{ $order->order_number }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5 max-w-4xl mx-auto" dir="rtl">
    <x-finance-nav />

    @if(session('error'))
        <div class="erp-alert erp-alert-danger">
            <x-icon name="alert-triangle" size="16" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Disbursement Order Summary Card -->
    <div class="erp-card p-6 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-4">
            <div>
                <span class="text-xs text-slate-400 block">إذن صرف معتمد من الإدارة</span>
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 font-mono mt-0.5">{{ $order->order_number }}</h2>
            </div>
            <div class="text-left">
                <span class="text-xs text-slate-400 block">المبلغ المصرح بصرفه</span>
                <span class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400">
                    {{ number_format($order->amount, 2) }} ج.م
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-xs">
            <div>
                <span class="text-slate-400 block">تاريخ الإذن:</span>
                <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">{{ $order->order_date->toDateString() }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">المستفيد:</span>
                <span class="font-bold text-slate-700 dark:text-slate-200">{{ $order->beneficiary_label }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">محرر الإذن بالمنظومة:</span>
                <span class="font-bold text-slate-700 dark:text-slate-200">{{ $order->creator->name ?? 'المدير' }}</span>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700 text-xs">
            <span class="text-slate-400 block font-semibold mb-1">الغرض والبيان:</span>
            <p class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 p-2.5 rounded border border-slate-200 dark:border-slate-700">
                {{ $order->purpose }}
            </p>
            @if($order->notes)
                <div class="mt-2 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 p-2 rounded border border-amber-200 dark:border-amber-800">
                    <strong>توجيهات المدير:</strong> {{ $order->notes }}
                </div>
            @endif
        </div>
    </div>

    <!-- Execution Form -->
    <div class="erp-card p-6">
        <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
            <x-icon name="wallet" size="18" class="text-primary" />
            <span>بيانات التنفيذ والخصم الفعلي (أمين الخزنة)</span>
        </h3>

        <form action="{{ route('finance.disbursements.execute', $order->id) }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="erp-form-label font-bold text-slate-700 dark:text-slate-200">
                    مصدر الصرف (من الخزنة ولا البنك) *
                </label>
                <select name="treasury_id" class="erp-form-control" required>
                    <option value="">-- حدد مصدر الصرف والخصم --</option>
                    @foreach($treasuries as $t)
                        <option value="{{ $t->id }}" {{ old('treasury_id') == $t->id ? 'selected' : '' }}>
                            @if($t->type === 'bank')
                                🏦 بنك:
                            @elseif($t->type === 'cashbox')
                                🏢 خزينة نقدية:
                            @else
                                🏥 عهدة موقع:
                            @endif
                            {{ $t->name }} (الرصيد المتاح حالياً: {{ number_format($t->balance, 2) }} ج.م)
                        </option>
                    @endforeach
                </select>
                <span class="text-[11px] text-slate-400 mt-1 block">يجب اختيار حساب به رصيد كافٍ لتغطية مبلغ الصرف ({{ number_format($order->amount, 2) }} ج.م)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">طريقة الصرف *</label>
                    <select name="payment_method" class="erp-form-control" required>
                        <option value="cash">نقداً من الخزينة</option>
                        <option value="bank_transfer">تحويل بنكي مباشر</option>
                        <option value="check">شيك بنكي مسحوب</option>
                    </select>
                </div>

                <div>
                    <label class="erp-form-label">تاريخ الصرف الفعلي *</label>
                    <input type="date" name="disbursement_date" class="erp-form-control font-mono" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div>
                <label class="erp-form-label">ملاحظات أمين الخزنة وبيانات السند / الإيصال</label>
                <textarea name="notes" rows="3" class="erp-form-control" placeholder="اكتب رقم إيصال الاستلام، اسم الشخص المستلم، أو أي ملاحظات أخرى تخص عملية الصرف...">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('finance.disbursements.index') }}" class="erp-btn erp-btn-secondary">
                    <span>إلغاء والعودة</span>
                </a>

                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check-circle" size="16" />
                    <span>تأكيد الصرف، خصم الرصيد، وإنشاء القيد المحاسبي</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
