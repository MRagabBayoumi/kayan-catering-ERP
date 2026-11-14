@extends('layouts.app')

@section('title', 'التفريدة الشهرية للجهات والأشخاص')

@section('control_panel')
    <x-control-panel title="التفريدة والتحليل الشهري للجهات والأشخاص (Monthly Financial Breakdown)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'التفريدة الشهرية' => route('finance.breakdown.index')]">
        <x-slot:actions>
            <button type="button" class="erp-btn erp-btn-primary text-xs" data-action="print-closing">
                <x-icon name="printer" size="14" />
                <span>طباعة التفريدة الشهرية A4</span>
            </button>
            <a href="{{ route('finance.ledger.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book-open" size="16" />
                <span>دفتر الأستاذ العام</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>الشهر المالي:</span>
                <strong>{{ $year }} / {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <div class="no-print">
        <x-finance-nav />
    </div>

    <!-- Print Only Header -->
    <div class="hidden print-block mb-6 text-center border-b-2 border-slate-900 pb-4">
        <h1 class="text-2xl font-bold text-slate-900">شركة كيان للإعاشة المطهية والخدمات التموينية</h1>
        <h2 class="text-lg font-semibold text-slate-700 mt-1">التفريدة الشهرية التفصيلية لحركات الصادر والوارد لكافة الجهات والأشخاص</h2>
        <div class="flex justify-between items-center text-xs text-slate-600 mt-3 px-4 font-mono">
            <span>عن شهر: {{ $year }} - {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}</span>
            <span>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</span>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="erp-card p-4 no-print">
        <form action="{{ route('finance.breakdown.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="erp-form-label text-xs">السنة المالية</label>
                <select name="year" class="erp-form-control text-xs font-mono">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="erp-form-label text-xs">الشهر المالي</label>
                <select name="month" class="erp-form-control text-xs font-mono">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            شهر {{ $m }} ({{ date('F', mktime(0, 0, 0, $m, 10)) }})
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="erp-form-label text-xs">نوع الجهة</label>
                <select name="entity_type" class="erp-form-control text-xs">
                    <option value="">-- كافة الجهات والأشخاص --</option>
                    <option value="supplier" {{ $entityType === 'supplier' ? 'selected' : '' }}>الموردين فقط</option>
                    <option value="hospital" {{ $entityType === 'hospital' ? 'selected' : '' }}>مواقع المستشفيات فقط</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="erp-btn erp-btn-primary text-xs w-full">
                    <x-icon name="search" size="14" />
                    <span>تحديث التفريدة</span>
                </button>
                <a href="{{ route('finance.breakdown.index') }}" class="erp-btn erp-btn-secondary text-xs">
                    إلغاء
                </a>
            </div>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المنصرف (فلوس أخذتها الجهات طوال الشهر)</span>
            <div class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400 mt-1">
                {{ number_format($breakdown['grand_total_disbursed'], 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">سدادات موردين + مصروفات عهد ومواقع نقدية</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي الوارد (فلوس دخلتها الجهات طوال الشهر)</span>
            <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                {{ number_format($breakdown['grand_total_received'], 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">تحصيلات مستشفيات + رد عهد + إيرادات</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">صافي التدفق المالي للشهر</span>
            @php
                $netFlow = $breakdown['grand_total_received'] - $breakdown['grand_total_disbursed'];
            @endphp
            <div class="text-2xl font-mono font-bold {{ $netFlow >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-1">
                {{ $netFlow >= 0 ? '+' : '' }}{{ number_format($netFlow, 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">الوارد الإجمالي - المنصرف الإجمالي</span>
        </div>
    </div>

    <!-- Itemized Entities List (التفريدة) -->
    <div class="space-y-4">
        @forelse($breakdown['entities'] as $entity)
            <div class="erp-card p-4 border border-slate-200 dark:border-slate-700">
                <!-- Entity Header -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm">{{ $entity['name'] }}</h3>
                            <span class="erp-badge erp-badge-info text-xs">{{ $entity['category'] }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-mono">
                        <div>
                            <span class="text-slate-400">إجمالي ما أخذ (صادر):</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400 font-numeric">
                                {{ number_format($entity['total_disbursed'], 2) }} ج.م
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400">إجمالي ما ورّد (وارد):</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 font-numeric">
                                {{ number_format($entity['total_received'], 2) }} ج.م
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Entity Transactions Sub-table -->
                <div class="mt-3 overflow-x-auto rounded border border-slate-200 dark:border-slate-700">
                    <table class="erp-table text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60">
                                <th>التاريخ</th>
                                <th>رقم السند</th>
                                <th>نوع الحركة</th>
                                <th>الخزينة / الحساب</th>
                                <th>البيان والغرض</th>
                                <th>طريقة الدفع</th>
                                <th class="text-left font-mono">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entity['transactions'] as $tx)
                                <tr>
                                    <td class="font-mono text-slate-600 dark:text-slate-300">{{ $tx['date'] }}</td>
                                    <td class="font-mono font-bold text-primary">{{ $tx['voucher_number'] }}</td>
                                    <td>
                                        <span class="erp-badge {{ $tx['type'] === 'payment_voucher' ? 'erp-badge-danger' : 'erp-badge-success' }} text-[10px]">
                                            {{ $tx['type_label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $tx['treasury_name'] }}</td>
                                    <td>{{ $tx['purpose'] }}</td>
                                    <td>{{ $tx['payment_method'] === 'cash' ? 'نقدي' : ($tx['payment_method'] === 'bank_transfer' ? 'تحويل بنكي' : 'شيك') }}</td>
                                    <td class="font-mono text-left font-bold {{ $tx['type'] === 'payment_voucher' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $tx['type'] === 'payment_voucher' ? '-' : '+' }}{{ number_format($tx['amount'], 2) }} ج.م
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="erp-card p-12 text-center text-slate-400">
                <x-icon name="calendar" size="36" class="mx-auto mb-2 opacity-50" />
                <p>لا توجد حركات مالية مسجلة لأي جهة خلال شهر {{ $month }} / {{ $year }}.</p>
            </div>
        @endforelse
    </div>

    <!-- Official Signatures Block for Print -->
    <div class="hidden print-block mt-10 pt-6 border-t-2 border-slate-300">
        <div class="grid grid-cols-2 gap-6 text-center text-xs">
            <div>
                <span class="font-bold text-slate-800 block">إعداد الحسابات والمطابقة</span>
                <span class="text-slate-500 block mt-1">التوقيع: ............................</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">اعتماد الإدارة المالية والتنفيذية</span>
                <span class="text-slate-500 block mt-1">التوقيع والختم: ...................</span>
            </div>
        </div>
    </div>
</div>
@endsection
