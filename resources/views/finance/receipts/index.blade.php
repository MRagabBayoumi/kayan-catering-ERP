@extends('layouts.app')

@section('title', 'الوارد واعتماد التحصيلات')

@section('control_panel')
    <x-control-panel title="الوارد واعتماد التحصيلات والترصيد المالي (Receipts & Inflows)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'الوارد والتحصيلات' => route('finance.receipts.index')]">
        <x-slot:actions>
            <a href="#newReceiptSection" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسجيل وارد جديد</span>
            </a>
            <a href="{{ route('finance.treasury.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="wallet" size="16" />
                <span>الخزينة وعهد المواقع</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning font-numeric px-2.5 py-1 text-xs">
                <span>وارد بانتظار الاعتماد:</span>
                <strong>{{ $pendingCount }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <x-finance-nav />

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="erp-alert erp-alert-danger">
            <x-icon name="alert-triangle" size="16" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="erp-card p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">وارد بانتظار موافقة المدير</span>
                <span class="erp-badge erp-badge-warning font-numeric">{{ $pendingCount }} طلب</span>
            </div>
            <div class="text-2xl font-mono font-bold text-amber-600 dark:text-amber-400 mt-2">
                {{ $pendingCount }}
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">مبالغ واردة تتطلب موافقة أو رفض المدير مع السبب</span>
        </div>

        <div class="erp-card p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">إجمالي الوارد المعتمد اليوم</span>
                <span class="erp-badge erp-badge-success">اليوم</span>
            </div>
            <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-2">
                {{ number_format($approvedTodaySum, 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">تم ترصيده في الخزائن والبنوك رسميًا</span>
        </div>

        <div class="erp-card p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">إجمالي الخزائن والحسابات</span>
                <span class="erp-badge erp-badge-info">{{ count($treasuries) }} حساب</span>
            </div>
            <div class="text-2xl font-mono font-bold text-primary mt-2">
                {{ number_format($treasuries->sum('balance'), 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">إجمالي السيولة النقدية والبنكية الحالية</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="erp-card p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">تصفية الوارد:</span>
            <a href="{{ route('finance.receipts.index', ['status' => 'all']) }}" 
               class="erp-btn {{ $status === 'all' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               الكل
            </a>
            <a href="{{ route('finance.receipts.index', ['status' => 'pending_approval']) }}" 
               class="erp-btn {{ $status === 'pending_approval' ? 'erp-btn-warning' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               بانتظار الموافقة ({{ $pendingCount }})
            </a>
            <a href="{{ route('finance.receipts.index', ['status' => 'approved']) }}" 
               class="erp-btn {{ $status === 'approved' ? 'erp-btn-success' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               معتمد ومرصّد
            </a>
            <a href="{{ route('finance.receipts.index', ['status' => 'rejected']) }}" 
               class="erp-btn {{ $status === 'rejected' ? 'erp-btn-danger' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               مرفوض
            </a>
        </div>

        <div class="text-xs text-slate-400 font-numeric">
            إجمالي السجلات: {{ $receipts->total() }}
        </div>
    </div>

    <!-- Receipts Table -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <x-icon name="arrow-down-left" size="18" />
                <span>سجل الوارد والتحصيلات</span>
            </h3>
            <span class="text-xs text-slate-400">اعتماد الإدارة والترصيد التلقائي</span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم الإذن</th>
                        <th>التاريخ</th>
                        <th>الجهة المورّدة</th>
                        <th>الحساب المستلم</th>
                        <th>المبلغ</th>
                        <th>البيان والغرض</th>
                        <th>الحالة</th>
                        <th>المدخل</th>
                        <th>إجراء المدير</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $rcpt)
                        <tr class="{{ $rcpt->isPending() ? 'bg-amber-50/40 dark:bg-amber-950/20' : '' }}">
                            <td class="font-mono font-bold text-primary">{{ $rcpt->order_number }}</td>
                            <td class="font-mono text-xs">{{ $rcpt->order_date->toDateString() }}</td>
                            <td>
                                <div class="font-bold text-slate-800 dark:text-slate-100">{{ $rcpt->payer_label }}</div>
                                <span class="text-[11px] text-slate-400">
                                    {{ $rcpt->payment_method === 'cash' ? 'نقدي' : ($rcpt->payment_method === 'bank_transfer' ? 'تحويل بنكي' : 'شيك') }}
                                    @if($rcpt->reference_number)
                                        (مرجع: {{ $rcpt->reference_number }})
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="font-semibold text-slate-700 dark:text-slate-200 text-xs">
                                    {{ $rcpt->treasury?->name ?? 'الخزينة الرئيسية' }}
                                </span>
                            </td>
                            <td class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                +{{ number_format($rcpt->amount, 2) }} ج.م
                            </td>
                            <td class="max-w-xs truncate" title="{{ $rcpt->purpose }}">
                                <span>{{ $rcpt->purpose }}</span>
                                @if($rcpt->notes)
                                    <span class="block text-[11px] text-slate-400 truncate">{{ $rcpt->notes }}</span>
                                @endif
                                @if($rcpt->isRejected() && $rcpt->rejection_reason)
                                    <span class="block text-[11px] text-rose-600 font-bold mt-1">سبب الرفض: {{ $rcpt->rejection_reason }}</span>
                                @endif
                            </td>
                            <td>
                                @if($rcpt->isPending())
                                    <span class="erp-badge erp-badge-warning animate-pulse">بانتظار الموافقة</span>
                                @elseif($rcpt->isApproved())
                                    <span class="erp-badge erp-badge-success">معتمد ومرصّد</span>
                                @else
                                    <span class="erp-badge erp-badge-danger">مرفوض</span>
                                @endif
                            </td>
                            <td class="text-xs">{{ $rcpt->creator->name ?? 'مستخدم' }}</td>
                            <td>
                                @if($rcpt->isPending() && auth()->user()?->isAdmin())
                                    <div class="flex flex-col gap-1.5 min-w-[200px]">
                                        <!-- Approval Action Form -->
                                        <form action="{{ route('finance.receipts.review', $rcpt->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="erp-btn erp-btn-success text-xs py-1 px-2.5 w-full">
                                                <x-icon name="check" size="14" />
                                                <span>موافقة وترصيد فوري</span>
                                            </button>
                                        </form>

                                        <!-- Rejection Action Form -->
                                        <details class="text-xs">
                                            <summary class="cursor-pointer text-rose-600 hover:text-rose-700 font-semibold py-0.5">
                                                رفض مع ذكر السبب...
                                            </summary>
                                            <form action="{{ route('finance.receipts.review', $rcpt->id) }}" method="POST" class="mt-1 space-y-1">
                                                @csrf
                                                <input type="hidden" name="action" value="reject">
                                                <input type="text" name="rejection_reason" class="erp-form-control text-xs py-1 px-2" placeholder="اكتب سبب الرفض هنا..." required>
                                                <button type="submit" class="erp-btn erp-btn-danger text-xs py-0.5 px-2 w-full">
                                                    تأكيد الرفض
                                                </button>
                                            </form>
                                        </details>
                                    </div>
                                @elseif($rcpt->isApproved())
                                    <div class="text-xs text-emerald-600 font-bold">
                                        <span>معتمد بواسطة: {{ $rcpt->approver->name ?? 'المدير' }}</span>
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $rcpt->approved_at?->format('Y-m-d H:i') }}</span>
                                    </div>
                                @elseif($rcpt->isRejected())
                                    <div class="text-xs text-rose-600 font-bold">
                                        <span>مرفوض من: {{ $rcpt->approver->name ?? 'المدير' }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">بانتظار قرار المدير</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                لا توجد سجلات وارد مطابقة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>

    <!-- Form: Create New Receipt Order -->
    <div id="newReceiptSection" class="erp-card p-6">
        <div class="flex items-center gap-2 mb-4 border-b border-slate-200 dark:border-slate-700 pb-3">
            <x-icon name="plus-circle" size="20" class="text-primary" />
            <h3 class="font-bold text-slate-800 dark:text-slate-100">تسجيل وارد جديد وإرسال إشعار للمدير للموافقة</h3>
        </div>

        <form action="{{ route('finance.receipts.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf

            <div>
                <label class="erp-form-label">تاريخ التوريد</label>
                <input type="date" name="order_date" class="erp-form-control font-mono" value="{{ date('Y-m-d') }}" required>
            </div>

            <div>
                <label class="erp-form-label">المبلغ الوارد (ج.م)</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="erp-form-control font-mono font-bold" placeholder="0.00" required>
            </div>

            <div>
                <label class="erp-form-label">الحساب المراد الإيداع فيه *</label>
                <select name="treasury_id" class="erp-form-control" required>
                    <option value="">-- حدد الخزينة أو البنك --</option>
                    @foreach($treasuries as $t)
                        <option value="{{ $t->id }}">
                            {{ $t->name }} (الرصيد: {{ number_format($t->balance, 2) }} ج.م)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label">نوع الجهة المورّدة</label>
                <select name="payer_type" class="erp-form-control" required>
                    <option value="hospital">مستشفى / عميل (تحصيل مستخلص)</option>
                    <option value="user">موظف / مسترد عهدة موقع</option>
                    <option value="supplier">مورد (مرتجع نقدي أو تسوية)</option>
                    <option value="other">جهة خارجية / تمويل / أخرى</option>
                </select>
            </div>

            <div>
                <label class="erp-form-label">المستشفى (إن كان الوارد من مستشفى)</label>
                <select name="hospital_id" class="erp-form-control">
                    <option value="">-- اختر المستشفى --</option>
                    @foreach($hospitals as $hosp)
                        <option value="{{ $hosp->id }}">{{ $hosp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label">اسم الشخص / الجهة المورّدة</label>
                <input type="text" name="payer_name" class="erp-form-control" placeholder="اسم الجهة أو الشخص المورّد">
            </div>

            <div>
                <label class="erp-form-label">طريقة التوريد</label>
                <select name="payment_method" class="erp-form-control" required>
                    <option value="cash">نقداً بالخزينة</option>
                    <option value="bank_transfer">إيداع / تحويل بنكي</option>
                    <option value="check">شيك بنكي مقبول الدفع</option>
                </select>
            </div>

            <div>
                <label class="erp-form-label">رقم الإيداع / مرجع الشيك</label>
                <input type="text" name="reference_number" class="erp-form-control font-mono" placeholder="رقم إيصال الإيداع أو رقم الشيك">
            </div>

            <div>
                <label class="erp-form-label">الغرض والبيان من التوريد</label>
                <input type="text" name="purpose" class="erp-form-control" placeholder="مثال: تحصيل مستخلص وجبات شهر أكتوبر أو رد عهدة متبقية" required>
            </div>

            <div class="md:col-span-3">
                <label class="erp-form-label">ملاحظات إضافية</label>
                <textarea name="notes" rows="2" class="erp-form-control" placeholder="ملاحظات تفصيلية تخص عملية الوارد..."></textarea>
            </div>

            <div class="md:col-span-3 flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check-circle" size="16" />
                    <span>تسجيل إذن الوارد وإرساله للمدير للموافقة</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
