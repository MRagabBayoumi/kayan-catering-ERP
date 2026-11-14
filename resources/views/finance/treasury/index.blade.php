@extends('layouts.app')

@section('title', 'الخزينة والبنوك وعهد المواقع')

@section('control_panel')
    <x-control-panel title="الخزينة والبنوك وعهد المواقع النقدية (الإدارة المالية)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'الخزينة وعهد المواقع' => route('finance.treasury.index')]">
        <x-slot:actions>
            <a href="{{ route('finance.closing.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="pie-chart" size="16" />
                <span>التقفيل واليومية</span>
            </a>
            <a href="{{ route('finance.suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="factory" size="16" />
                <span>حسابات الموردين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الخزن والعهد:</span>
                <strong>{{ count($treasuries) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $financeTabs = [
            [
                'label' => 'كشوف حسابات الموردين والأعمار',
                'url' => route('finance.suppliers.index'),
                'icon' => 'users',
                'active' => request()->routeIs('finance.suppliers*'),
            ],
            [
                'label' => 'الخزينة وعهد النثريات النقدية (المواقع)',
                'url' => route('finance.treasury.index'),
                'icon' => 'wallet',
                'active' => request()->routeIs('finance.treasury*'),
                'badge' => count($treasuries),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'التقفيل واليومية للمالك والأرباح',
                'url' => route('finance.closing.index'),
                'icon' => 'pie-chart',
                'active' => request()->routeIs('finance.closing*'),
            ],
        ];
    @endphp
    <x-module-nav :tabs="$financeTabs" />

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Balances Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($treasuries as $t)
            <div class="erp-card p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-semibold text-slate-500">
                            @if($t->type === 'cashbox')
                                🏢 خزينة نقدية
                            @elseif($t->type === 'bank')
                                🏦 حساب بنكي
                            @else
                                🏥 عهدة موقع المستشفى
                            @endif
                        </span>
                        <h4 class="font-bold text-slate-800 dark:text-slate-100 mt-1">{{ $t->name }}</h4>
                    </div>
                    <span class="erp-badge erp-badge-info">{{ $t->type }}</span>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <span class="text-xs text-slate-400 block">الرصيد المتاح:</span>
                    <span class="text-xl font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        {{ number_format($t->balance, 2) }} ج.م
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create Financial Voucher Form -->
    <div class="erp-card p-6">
        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-4">إنشاء سند صرف / استلام نقدية أو تحويل بنكي</h3>
        <form action="{{ route('finance.treasury.vouchers.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="erp-form-label">نوع السند</label>
                <select name="type" class="erp-form-control" required>
                    <option value="payment_voucher">سند صرف نقدية (مصروف / عهدة / سداد مورد)</option>
                    <option value="receipt_voucher">سند قبض واستلام نقدية (تحصيل من جهة)</option>
                    <option value="bank_transfer">إشعار تحويل بنكي</option>
                </select>
            </div>
            <div>
                <label class="erp-form-label">الخزينة أو العهدة المتأثرة</label>
                <select name="treasury_id" class="erp-form-control" required>
                    @foreach($treasuries as $tr)
                        <option value="{{ $tr->id }}">{{ $tr->name }} ({{ number_format($tr->balance, 2) }} ج.م)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">المبلغ (ج.م)</label>
                <input type="number" step="0.01" name="amount" class="erp-form-control" placeholder="0.00" required>
            </div>
            <div>
                <label class="erp-form-label">تاريخ السند</label>
                <input type="date" name="voucher_date" class="erp-form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div>
                <label class="erp-form-label">الموقع / المستشفى (للعهد الميدانية)</label>
                <select name="hospital_id" class="erp-form-control">
                    <option value="">-- اختياري --</option>
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}">{{ $hosp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">المورد (في حال سداد مورد)</label>
                <select name="supplier_id" class="erp-form-control">
                    <option value="">-- اختياري --</option>
                    @foreach($suppliers as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">طريقة الدفع</label>
                <select name="payment_method" class="erp-form-control">
                    <option value="cash">نقداً من الخزينة / العهدة</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                    <option value="check">شيك مصرفي</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="check" size="16" />
                    <span>تسجيل السند المالي</span>
                </button>
            </div>
            <div class="md:col-span-4">
                <label class="erp-form-label">الغرض والبيان التفصيلي</label>
                <input type="text" name="purpose" class="erp-form-control" placeholder="مثال: شراء 1200 رغيف عيش بلدي وكايزر طازج لمستشفى إمبابة" required>
            </div>
        </form>
    </div>

    <!-- Vouchers History Table -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-bold text-slate-800 dark:text-slate-100">سجل اليومية وسندات الصرف والقبض الأخيرة</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم السند</th>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>الخزينة / العهدة</th>
                        <th>المبلغ</th>
                        <th>البيان والغرض</th>
                        <th>المسؤول</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $v)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $v->voucher_number }}</td>
                            <td class="font-mono text-xs">{{ $v->voucher_date->format('Y-m-d') }}</td>
                            <td>
                                @if($v->type === 'payment_voucher')
                                    <span class="erp-badge erp-badge-danger">صرف نقدية</span>
                                @elseif($v->type === 'receipt_voucher')
                                    <span class="erp-badge erp-badge-success">قبض نقدية</span>
                                @else
                                    <span class="erp-badge erp-badge-info">تحويل بنكي</span>
                                @endif
                            </td>
                            <td>{{ $v->treasury->name }}</td>
                            <td class="font-mono font-bold text-base {{ $v->type === 'payment_voucher' ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $v->type === 'payment_voucher' ? '-' : '+' }}{{ number_format($v->amount, 2) }} ج.م
                            </td>
                            <td class="text-slate-700 dark:text-slate-300">{{ $v->purpose }}</td>
                            <td class="text-xs text-slate-500">{{ $v->creator->name ?? 'مدير النظام' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                لا توجد سندات مالية مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $vouchers->links() }}
        </div>
    </div>
</div>
@endsection
