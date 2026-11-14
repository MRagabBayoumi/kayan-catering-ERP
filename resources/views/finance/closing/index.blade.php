@extends('layouts.app')

@section('title', 'اليومية والتقفيل لصاحب العمل ومحضر العجز')

@section('control_panel')
    <x-control-panel title="محضر التقفيل اليومي ومطابقة الخزائن والعجز (Daily Closing & Audit)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'التقفيل واليومية' => route('finance.closing.index')]">
        <x-slot:actions>
            <form action="{{ route('finance.closing.index') }}" method="GET" class="inline-flex items-center gap-2 m-0">
                <input type="date" name="date" class="erp-input text-xs py-1 px-2.5 font-mono" value="{{ $date }}">
                <button type="submit" class="erp-btn erp-btn-secondary text-xs">
                    <x-icon name="search" size="14" />
                    <span>عرض اليومية</span>
                </button>
            </form>

            <button type="button" class="erp-btn erp-btn-primary text-xs" data-action="print-closing">
                <x-icon name="printer" size="14" />
                <span>طباعة محضر التقفيل A4</span>
            </button>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>تاريخ اليومية:</span>
                <strong>{{ $date }}</strong>
            </span>
            @if($closing)
                @if($closing->isPending())
                    <span class="erp-badge erp-badge-warning font-numeric px-2.5 py-1 text-xs animate-pulse">
                        بانتظار موافقة المدير
                    </span>
                @elseif($closing->isApproved())
                    <span class="erp-badge erp-badge-success font-numeric px-2.5 py-1 text-xs">
                        معتمد رسمياً
                    </span>
                @elseif($closing->isRejected())
                    <span class="erp-badge erp-badge-danger font-numeric px-2.5 py-1 text-xs">
                        مرفوض
                    </span>
                @endif
            @else
                <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                    اليومية مفتوحة
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <div class="no-print">
        <x-finance-nav />
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success no-print">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="erp-alert erp-alert-danger no-print">
            <x-icon name="alert-triangle" size="16" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Print Only Official Header -->
    <div class="hidden print-block mb-6 text-center border-b-2 border-slate-900 pb-4">
        <h1 class="text-2xl font-bold text-slate-900">شركة كيان للخدمات الفندقية والإعاشة المطهية</h1>
        <h2 class="text-lg font-semibold text-slate-700 mt-1">محضر جرد الخزائن وتقفيل اليومية المالية لقطاع المستشفيات</h2>
        <div class="flex justify-between items-center text-xs text-slate-600 mt-3 px-4 font-mono">
            <span>تاريخ اليومية: {{ $date }}</span>
            <span>تاريخ ووقت الطباعة: {{ now()->format('Y-m-d H:i') }}</span>
            <span>رقم السجل: CLS-{{ str_replace('-', '', $date) }}</span>
        </div>
    </div>

    <!-- Daily Financial Closing KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المقبوضات (الوارد)</span>
            <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                +{{ number_format($closing?->total_inflow ?? $totalInflow, 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">تحصيلات من الجهات والمستشفيات</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">إجمالي المدفوعات (المصروف)</span>
            <div class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400 mt-1">
                -{{ number_format($closing?->total_outflow ?? $totalOutflow, 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">مصروفات وتشغيل وسدادات موردين</span>
        </div>

        <div class="erp-card p-4">
            <span class="text-xs text-slate-500 font-semibold">الرصيد الدفتري المتوقع</span>
            <div class="text-2xl font-mono font-bold text-primary mt-1">
                {{ number_format($expectedBalance, 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">رصيد أول المدة + الوارد - المصروف</span>
        </div>

        <div class="erp-card p-4 {{ ($closing && $closing->hasDeficit()) ? 'border-2 border-rose-500 bg-rose-50/50 dark:bg-rose-950/20' : '' }}">
            <span class="text-xs text-slate-500 font-semibold">العد الفعلي للخزينة ومطابقة العجز</span>
            <div class="text-2xl font-mono font-bold {{ ($closing && $closing->hasDeficit()) ? 'text-rose-600' : 'text-slate-800 dark:text-slate-100' }} mt-1">
                {{ $closing ? number_format($closing->actual_cash_count, 2) : 'لم يتم العد' }} ج.م
            </div>
            @if($closing)
                <span class="text-[11px] font-bold mt-1 block {{ $closing->hasDeficit() ? 'text-rose-600' : ($closing->hasSurplus() ? 'text-emerald-600' : 'text-slate-500') }}">
                    @if($closing->hasDeficit())
                        ⚠️ عجز مالي: {{ number_format(abs($closing->difference), 2) }} ج.م
                    @elseif($closing->hasSurplus())
                        ✅ فائض مالي: {{ number_format($closing->difference, 2) }} ج.م
                    @else
                        ✅ مطابقة تامة (0.00 ج.م)
                    @endif
                </span>
            @else
                <span class="text-[11px] text-slate-400 mt-1 block">أدخل العد الفعلي بالأسفل لحساب العجز</span>
            @endif
        </div>
    </div>

    <!-- Manager Review Box (Approval / Rejection with Reason) -->
    @if($closing && $closing->isPending() && auth()->user()?->isAdmin())
        <div class="erp-card p-6 border-2 border-amber-400 bg-amber-50/60 dark:bg-amber-950/30 no-print">
            <div class="flex items-center gap-2 mb-3">
                <x-icon name="alert-circle" size="20" class="text-amber-600" />
                <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">
                    إشعار للمدير: مراجعة واعتماد إقفال اليومية ليوم {{ $date }}
                </h3>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 mb-4">
                قدم أمين الخزينة إقفال اليومية بالرصيد الدفتري <strong>{{ number_format($closing->closing_balance, 2) }} ج.م</strong>، وجرد فعلي <strong>{{ number_format($closing->actual_cash_count, 2) }} ج.م</strong>.
                @if($closing->hasDeficit())
                    <span class="text-rose-600 font-bold block mt-1">
                        تنبيه: يوجد عجز مالي قدره {{ number_format(abs($closing->difference), 2) }} ج.م سيتم ترحيل قيد تسوية به عند الموافقة.
                    </span>
                @endif
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Approve Form -->
                <form action="{{ route('finance.closing.review', $closing->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="erp-btn erp-btn-success text-xs py-2 px-4">
                        <x-icon name="check-circle" size="16" />
                        <span>موافقة واعتماد التقفيل وترحيل الفروقات</span>
                    </button>
                </form>

                <!-- Reject Details Form -->
                <details class="text-xs">
                    <summary class="erp-btn erp-btn-danger text-xs py-2 px-4 cursor-pointer">
                        <x-icon name="x-circle" size="16" />
                        <span>رفض الإقفال مع ذكر السبب...</span>
                    </summary>
                    <div class="mt-3 p-3 bg-white dark:bg-slate-800 rounded border border-rose-300 dark:border-rose-800">
                        <form action="{{ route('finance.closing.review', $closing->id) }}" method="POST" class="space-y-2">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <label class="erp-form-label text-rose-700 dark:text-rose-400 font-bold">سبب رفض الإقفال *</label>
                            <textarea name="rejection_reason" rows="2" class="erp-form-control text-xs" placeholder="وضح أسباب رفض الإقفال ليقوم أمين الخزينة بإعادة المطابقة..." required></textarea>
                            <button type="submit" class="erp-btn erp-btn-danger text-xs py-1.5 px-3">
                                تأكيد الرفض وإشعار أمين الخزنة
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    @endif

    <!-- Status Box -->
    <div class="erp-card p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <span>حالة إقفال اليومية ليوم:</span>
                <span class="font-mono text-primary">{{ $date }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">
                @if($closing && $closing->isApproved())
                    ✅ تم اعتماد التقفيل ومطابقة الأرصدة رسمياً بواسطة المدير: <strong>{{ $closing->approvedByUser->name ?? 'مدير النظام' }}</strong> في {{ $closing->approved_at?->format('Y-m-d H:i') }}
                @elseif($closing && $closing->isPending())
                    ⏳ تم تقديم المحضر بواسطة: <strong>{{ $closing->closedByUser->name ?? 'أمين الخزنة' }}</strong> وبانتظار اعتماد المدير العام.
                @elseif($closing && $closing->isRejected())
                    ❌ تم رفض الإقفال بواسطة المدير. السبب: <span class="font-bold text-rose-600">{{ $closing->rejection_reason }}</span>
                @else
                    ⚠️ اليومية مفتوحة ولم يتم إجراء الجرد الفعلي وتقديم التقفيل بعد.
                @endif
            </p>
        </div>

        <div class="text-xs font-mono text-slate-500 print-block">
            <span>طباعة معتمدة للوثائق المالية</span>
        </div>
    </div>

    <!-- Treasurer Submit / Resubmit Form -->
    <div class="erp-card p-6 no-print">
        <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-3 flex items-center gap-2">
            <x-icon name="clipboard-check" size="18" class="text-primary" />
            <span>إدخال الجرد الفعلي وتقديم تقفيل اليومية (أمين الخزنة)</span>
        </h3>

        <form action="{{ route('finance.closing.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">

            <div>
                <label class="erp-form-label font-bold text-slate-700 dark:text-slate-200">
                    الرصيد الفعلي المعدود بالخزائن (العد الفعلي) *
                </label>
                <input type="number" step="0.01" min="0" name="actual_cash_count" class="erp-form-control font-mono font-bold" 
                       value="{{ old('actual_cash_count', $closing?->actual_cash_count ?? $expectedBalance) }}" 
                       placeholder="0.00" required>
                <span class="text-[11px] text-slate-400 mt-1 block">الرصيد الدفتري المطلوب مطابقته: {{ number_format($expectedBalance, 2) }} ج.م</span>
            </div>

            <div class="md:col-span-2">
                <label class="erp-form-label">ملاحظات أمين الخزينة عن الجرد والعجز/الفائض</label>
                <input type="text" name="notes" class="erp-form-control" 
                       value="{{ old('notes', $closing?->notes) }}" 
                       placeholder="مثال: تم عد الخزينة المركزية وعهدة الموقع ومطابقة كافة إيصالات المصروف">
            </div>

            <div class="md:col-span-3 flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="send" size="16" />
                    <span>تقديم إقفال اليومية وإشعار مدير النظام</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Detail Table of Today's Transactions -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 dark:text-slate-100">سندات وحركات يومية تاريخ {{ $date }}</h3>
            <span class="text-xs text-slate-400 font-numeric">عدد العمليات: {{ count($vouchers) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم السند</th>
                        <th>النوع</th>
                        <th>الخزينة / الحساب</th>
                        <th>الجهة أو المستفيد</th>
                        <th>المبلغ</th>
                        <th>البيان والغرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $vch)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $vch->voucher_number }}</td>
                            <td>
                                @if($vch->type === 'payment_voucher')
                                    <span class="erp-badge erp-badge-danger">صرف</span>
                                @elseif($vch->type === 'receipt_voucher')
                                    <span class="erp-badge erp-badge-success">قبض</span>
                                @else
                                    <span class="erp-badge erp-badge-info">تحويل</span>
                                @endif
                            </td>
                            <td>{{ $vch->treasury->name }}</td>
                            <td>
                                {{ $vch->hospital?->name ?? ($vch->supplier?->name ?? 'المركز الرئيسي') }}
                            </td>
                            <td class="font-mono font-bold {{ $vch->type === 'payment_voucher' ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $vch->type === 'payment_voucher' ? '-' : '+' }}{{ number_format($vch->amount, 2) }} ج.م
                            </td>
                            <td>{{ $vch->purpose }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-400">
                                لا توجد حركات مالية مسجلة في هذا اليوم.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Official Signatures Block for Print -->
    <div class="hidden print-block mt-10 pt-6 border-t-2 border-slate-300">
        <div class="grid grid-cols-3 gap-6 text-center text-xs">
            <div>
                <span class="font-bold text-slate-800 block">أمين الخزينة</span>
                <span class="text-slate-500 block mt-1">الاسم: ..............................</span>
                <span class="text-slate-500 block mt-1">التوقيع: ............................</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">المحاسب المالي</span>
                <span class="text-slate-500 block mt-1">الاسم: ..............................</span>
                <span class="text-slate-500 block mt-1">التوقيع: ............................</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">المدير العام / المالك</span>
                <span class="text-slate-500 block mt-1">الاسم: ..............................</span>
                <span class="text-slate-500 block mt-1">التوقيع والختم: ...................</span>
            </div>
        </div>
    </div>
</div>
@endsection
