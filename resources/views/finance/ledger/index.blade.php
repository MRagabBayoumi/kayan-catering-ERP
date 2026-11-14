@extends('layouts.app')

@section('title', 'دفتر الأستاذ العام لكل الحسابات')

@section('control_panel')
    <x-control-panel title="دفتر الأستاذ العام وكشوف الحسابات التفصيلية (General Ledger)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'دفتر الأستاذ' => route('finance.ledger.index')]">
        <x-slot:actions>
            <button type="button" class="erp-btn erp-btn-primary text-xs" data-action="print-closing">
                <x-icon name="printer" size="14" />
                <span>طباعة كشف الحساب A4</span>
            </button>
            <a href="{{ route('finance.journal.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>دفتر اليومية العامة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>الفترة:</span>
                <strong>{{ $startDate }} إلى {{ $endDate }}</strong>
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
        <h1 class="text-2xl font-bold text-slate-900">شركة كيان للإعاشة المطهية والخدمات الفندقية</h1>
        <h2 class="text-lg font-semibold text-slate-700 mt-1">كشف حساب تفصيلي من واقع دفتر الأستاذ العام</h2>
        <div class="flex justify-between items-center text-xs text-slate-600 mt-3 px-4 font-mono">
            <span>نوع الحساب: {{ $accountType ?: 'كافة الحسابات' }}</span>
            <span>الفترة من: {{ $startDate }} إلى: {{ $endDate }}</span>
            <span>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</span>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="erp-card p-4 no-print">
        <form action="{{ route('finance.ledger.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="erp-form-label text-xs">نوع الحساب / الأستاذ</label>
                <select name="account_type" class="erp-form-control text-xs">
                    <option value="">-- كافة الحسابات المالية --</option>
                    <option value="treasury" {{ $accountType === 'treasury' ? 'selected' : '' }}>خزينة نقدية رئيسية</option>
                    <option value="bank" {{ $accountType === 'bank' ? 'selected' : '' }}>حساب بنكي جاري</option>
                    <option value="supplier" {{ $accountType === 'supplier' ? 'selected' : '' }}>حسابات الموردين (دائنون)</option>
                    <option value="hospital_custody" {{ $accountType === 'hospital_custody' ? 'selected' : '' }}>عهد المواقع والمستشفيات</option>
                    <option value="expense" {{ $accountType === 'expense' ? 'selected' : '' }}>المصروفات والتشغيل</option>
                    <option value="revenue" {{ $accountType === 'revenue' ? 'selected' : '' }}>الإيرادات والتحصيلات</option>
                    <option value="cash_shortage" {{ $accountType === 'cash_shortage' ? 'selected' : '' }}>عجز وفروقات الخزائن</option>
                </select>
            </div>

            <div>
                <label class="erp-form-label text-xs">من تاريخ</label>
                <input type="date" name="start_date" class="erp-form-control text-xs font-mono" value="{{ $startDate }}">
            </div>

            <div>
                <label class="erp-form-label text-xs">إلى تاريخ</label>
                <input type="date" name="end_date" class="erp-form-control text-xs font-mono" value="{{ $endDate }}">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="erp-btn erp-btn-primary text-xs w-full">
                    <x-icon name="search" size="14" />
                    <span>عرض كشف الأستاذ</span>
                </button>
                <a href="{{ route('finance.ledger.index') }}" class="erp-btn erp-btn-secondary text-xs">
                    إلغاء
                </a>
            </div>
        </form>
    </div>

    <!-- Ledger KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">الرصيد الافتتاحي قبل الفترة</span>
            <div class="text-2xl font-mono font-bold text-slate-700 dark:text-slate-200 mt-1">
                {{ number_format($ledgerData['opening_balance'], 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">رصيد ما قبل تاريخ: {{ $startDate }}</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي الحركات المدينة (منه)</span>
            <div class="text-2xl font-mono font-bold text-primary mt-1">
                +{{ number_format($ledgerData['total_debit'], 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">حركات الزيادة أو الصرف للجهة</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي الحركات الدائنة (له)</span>
            <div class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400 mt-1">
                -{{ number_format($ledgerData['total_credit'], 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">حركات الخصم أو التوريد من الجهة</span>
        </div>

        <div class="erp-card p-4 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-300 dark:border-emerald-800">
            <span class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold">الرصيد الختامي في نهاية الفترة</span>
            <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                {{ number_format($ledgerData['closing_balance'], 2) }} ج.م
            </div>
            <span class="text-[11px] text-emerald-600/70 mt-1 block">صافي الرصيد حتى تاريخ: {{ $endDate }}</span>
        </div>
    </div>

    <!-- Ledger Movements Table -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <x-icon name="book-open" size="18" />
                <span>حركات دفتر الأستاذ خلال الفترة</span>
            </h3>
            <span class="text-xs text-slate-400 font-numeric">عدد الحركات: {{ count($ledgerData['movements']) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table text-xs">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>رقم القيد</th>
                        <th>اسم الحساب</th>
                        <th>البيان والتفاصيل</th>
                        <th class="text-left font-mono">مدين (+)</th>
                        <th class="text-left font-mono">دائن (-)</th>
                        <th class="text-left font-mono">الرصيد التراكمي</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="bg-slate-50 dark:bg-slate-800/40 font-bold">
                        <td class="font-mono text-slate-500">{{ $startDate }}</td>
                        <td class="font-mono text-slate-400">-</td>
                        <td colspan="2">الرصيد الافتتاحي السابق للفترة</td>
                        <td class="text-left font-mono">-</td>
                        <td class="text-left font-mono">-</td>
                        <td class="text-left font-mono text-slate-700 dark:text-slate-200 font-bold">
                            {{ number_format($ledgerData['opening_balance'], 2) }} ج.م
                        </td>
                        <td></td>
                    </tr>

                    @forelse($ledgerData['movements'] as $mov)
                        <tr>
                            <td class="font-mono text-slate-600 dark:text-slate-300">{{ $mov['date'] }}</td>
                            <td class="font-mono font-bold text-primary">{{ $mov['entry_number'] }}</td>
                            <td class="font-bold text-slate-800 dark:text-slate-100">{{ $mov['account_name'] }}</td>
                            <td>{{ $mov['description'] }}</td>
                            <td class="font-mono text-left font-bold {{ $mov['debit'] > 0 ? 'text-primary' : 'text-slate-400' }}">
                                {{ $mov['debit'] > 0 ? '+' . number_format($mov['debit'], 2) . ' ج.م' : '-' }}
                            </td>
                            <td class="font-mono text-left font-bold {{ $mov['credit'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                                {{ $mov['credit'] > 0 ? '-' . number_format($mov['credit'], 2) . ' ج.م' : '-' }}
                            </td>
                            <td class="font-mono text-left font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($mov['running_balance'], 2) }} ج.م
                            </td>
                            <td class="text-slate-400 text-[11px]">{{ $mov['notes'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد حركات أستاذ مسجلة لهذا الحساب في الفترة المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold font-mono">
                        <td colspan="4" class="text-left">المجاميع والرصيد الختامي:</td>
                        <td class="text-left text-primary">+{{ number_format($ledgerData['total_debit'], 2) }} ج.م</td>
                        <td class="text-left text-rose-600 dark:text-rose-400">-{{ number_format($ledgerData['total_credit'], 2) }} ج.م</td>
                        <td class="text-left text-emerald-600 dark:text-emerald-400">{{ number_format($ledgerData['closing_balance'], 2) }} ج.م</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Official Signatures Block for Print -->
    <div class="hidden print-block mt-10 pt-6 border-t-2 border-slate-300">
        <div class="grid grid-cols-2 gap-6 text-center text-xs">
            <div>
                <span class="font-bold text-slate-800 block">إعداد ومراجعة الحسابات</span>
                <span class="text-slate-500 block mt-1">التوقيع: ............................</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">اعتماد الإدارة المالية</span>
                <span class="text-slate-500 block mt-1">التوقيع والختم: ...................</span>
            </div>
        </div>
    </div>
</div>
@endsection
