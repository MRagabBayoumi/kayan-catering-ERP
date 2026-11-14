@extends('layouts.app')

@section('title', 'أذونات الصرف اللحظية')

@section('control_panel')
    <x-control-panel title="شاشة أذونات الصرف اللحظية (من المدير إلى أمين الخزنة)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'أذونات الصرف اللحظية' => route('finance.disbursements.index')]">
        <x-slot:actions>
            <a href="#newDisbursementSection" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إصدار إذن صرف جديد</span>
            </a>
            <a href="{{ route('finance.treasury.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="wallet" size="16" />
                <span>الخزينة وعهد المواقع</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-danger font-numeric px-2.5 py-1 text-xs">
                <span>أذونات بانتظار الصرف:</span>
                <strong>{{ $pendingCount }}</strong>
            </span>
            <span class="erp-badge erp-badge-success font-numeric px-2.5 py-1 text-xs">
                <span>لحظي / مباشر</span>
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
                <span class="text-xs text-slate-500 font-semibold">أذونات معلقة تنتظر صرف أمين الخزنة</span>
                <span class="erp-badge erp-badge-danger font-numeric">{{ $pendingCount }} إذن</span>
            </div>
            <div class="text-2xl font-mono font-bold text-rose-600 dark:text-rose-400 mt-2">
                {{ $pendingCount }}
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">تتطلب التنفيذ وتحديد الخزينة أو البنك</span>
        </div>

        <div class="erp-card p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">أذونات صُرفت اليوم</span>
                <span class="erp-badge erp-badge-success font-numeric">{{ $completedToday }} إذن</span>
            </div>
            <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400 mt-2">
                {{ $completedToday }}
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">تم تنفيذها وترصيدها محاسبياً اليوم</span>
        </div>

        <div class="erp-card p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-semibold">إجمالي المنصرف الفعلي اليوم</span>
                <span class="erp-badge erp-badge-primary">اليوم</span>
            </div>
            <div class="text-2xl font-mono font-bold text-primary mt-2">
                {{ number_format($disbursedTodaySum, 2) }} ج.م
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">مجموع المبالغ المخصومة من الخزائن والبنوك اليوم</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="erp-card p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">تصفية حسب الحالة:</span>
            <a href="{{ route('finance.disbursements.index', ['status' => 'all']) }}" 
               class="erp-btn {{ $status === 'all' ? 'erp-btn-primary' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               الكل
            </a>
            <a href="{{ route('finance.disbursements.index', ['status' => 'pending']) }}" 
               class="erp-btn {{ $status === 'pending' ? 'erp-btn-danger' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               بانتظار الصرف ({{ $pendingCount }})
            </a>
            <a href="{{ route('finance.disbursements.index', ['status' => 'completed']) }}" 
               class="erp-btn {{ $status === 'completed' ? 'erp-btn-success' : 'erp-btn-secondary' }} text-xs py-1 px-3">
               تم الصرف
            </a>
        </div>

        <div class="text-xs text-slate-400 font-numeric">
            إجمالي الأذونات: {{ $orders->total() }}
        </div>
    </div>

    <!-- Disbursements Table -->
    <div class="erp-card">
        <div class="erp-card-header">
            <h3 class="erp-card-title">
                <x-icon name="truck" size="18" />
                <span>جدول أذونات الصرف اللحظية</span>
            </h3>
            <span class="text-xs text-slate-500 font-medium">تحديث فوري لجميع الأذونات</span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم الإذن</th>
                        <th>التاريخ</th>
                        <th>المستفيد</th>
                        <th>الغرض والبيان</th>
                        <th>المبلغ المطلوب</th>
                        <th>الحالة</th>
                        <th>محرر الإذن (الإدارة)</th>
                        <th>المنفذ / الصرف</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="{{ $order->isPending() ? 'bg-amber-50/40 dark:bg-amber-950/20' : '' }}">
                            <td class="font-mono font-bold text-primary">{{ $order->order_number }}</td>
                            <td class="font-mono text-xs">{{ $order->order_date->toDateString() }}</td>
                            <td>
                                <div class="font-bold text-slate-800 dark:text-slate-100">{{ $order->beneficiary_label }}</div>
                                <span class="text-[11px] text-slate-500">
                                    @if($order->beneficiary_type === 'supplier')
                                        مورد خامات
                                    @elseif($order->beneficiary_type === 'hospital')
                                        عهدة مستشفى
                                    @elseif($order->beneficiary_type === 'user')
                                        موظف / عهدة
                                    @else
                                        أخرى
                                    @endif
                                </span>
                            </td>
                            <td class="max-w-xs truncate" title="{{ $order->purpose }}">
                                <span>{{ $order->purpose }}</span>
                                @if($order->notes)
                                    <span class="block text-[11px] text-slate-500 truncate">{{ $order->notes }}</span>
                                @endif
                            </td>
                            <td class="font-mono font-bold text-rose-600 dark:text-rose-400 text-sm">
                                {{ number_format($order->amount, 2) }} ج.م
                            </td>
                            <td>
                                @if($order->isPending())
                                    <span class="erp-badge erp-badge-danger animate-pulse">بانتظار الصرف</span>
                                @elseif($order->isCompleted())
                                    <span class="erp-badge erp-badge-success">تم الصرف</span>
                                @else
                                    <span class="erp-badge erp-badge-neutral">{{ $order->status }}</span>
                                @endif
                            </td>
                            <td class="text-xs">{{ $order->creator->name ?? 'المدير' }}</td>
                            <td class="text-xs">
                                @if($order->isCompleted())
                                    <span class="font-bold text-slate-700 dark:text-slate-200">{{ $order->executor->name ?? 'أمين الخزنة' }}</span>
                                    <span class="block text-[10px] text-slate-500 font-mono">{{ $order->executed_at?->format('Y-m-d H:i') }}</span>
                                    @if($order->financialVoucher)
                                        <span class="block text-[10px] text-primary font-mono font-bold">{{ $order->financialVoucher->voucher_number }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td>
                                @if($order->isPending())
                                    <a href="{{ route('finance.disbursements.execute.view', $order->id) }}" 
                                       class="erp-btn erp-btn-primary text-xs py-1 px-3">
                                        <x-icon name="wallet" size="14" />
                                        <span>صرف فوري</span>
                                    </a>
                                @else
                                    <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                                        <x-icon name="check-circle" size="14" />
                                        <span>صُرف</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="py-10 text-center flex flex-col items-center justify-center gap-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-1">
                                        <x-icon name="inbox" size="24" />
                                    </div>
                                    <p class="font-bold text-sm text-slate-700">لا توجد أذونات صرف مطابقة للشروط</p>
                                    <p class="text-xs text-slate-500">يمكنك إصدار إذن صرف جديد لحظياً من النموذج أدناه</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- Manager Form: Create New Disbursement Order -->
    <div id="newDisbursementSection" class="erp-card">
        <div class="erp-card-header">
            <h3 class="erp-card-title text-primary">
                <x-icon name="plus-circle" size="20" />
                <span>إصدار إذن صرف جديد (من الإدارة إلى أمين الخزنة)</span>
            </h3>
            <span class="text-xs text-slate-500 font-medium">يصل فورياً إلى شاشة أمين الخزنة للصرف</span>
        </div>

        <div class="erp-card-body">
            <form action="{{ route('finance.disbursements.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="erp-form-group">
                        <label class="erp-form-label">
                            <span>تاريخ الإذن</span>
                            <span class="required">*</span>
                        </label>
                        <input type="date" name="order_date" class="erp-form-control font-mono" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">
                            <span>المبلغ المطلوب صرفه (ج.م)</span>
                            <span class="required">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="erp-form-control font-mono font-bold" placeholder="0.00" required>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">
                            <span>نوع المستفيد</span>
                            <span class="required">*</span>
                        </label>
                        <select name="beneficiary_type" class="erp-form-control" required>
                            <option value="supplier">مورد خامات ومواد غذائية</option>
                            <option value="hospital">عهدة موقع مستشفى (نثريات/خبز)</option>
                            <option value="user">سلفة / عهدة موظف أو سائق</option>
                            <option value="other">جهة أخرى / مصروف تشغيلي</option>
                        </select>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">المورد (إن كان المستفيد مورداً)</label>
                        <select name="supplier_id" class="erp-form-control">
                            <option value="">-- اختياري: اختر المورد --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }} (رصيد: {{ number_format($supplier->current_balance, 2) }} ج.م)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">موقع المستشفى (إن كان لعهدة موقع)</label>
                        <select name="hospital_id" class="erp-form-control">
                            <option value="">-- اختياري: اختر المستشفى --</option>
                            @foreach($hospitals as $hosp)
                                <option value="{{ $hosp->id }}">{{ $hosp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">اسم المستفيد / الشخص</label>
                        <input type="text" name="beneficiary_name" class="erp-form-control" placeholder="اسم المستفيد في حال عدم وجوده بالقوائم">
                    </div>

                    <div class="erp-form-group md:col-span-2">
                        <label class="erp-form-label">
                            <span>الغرض والبيان من الصرف</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="purpose" class="erp-form-control" placeholder="مثال: سداد دفعة توريد لحوم مجمدة أو عهدة شراء كايزر وثلج طارئة" required>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">ملاحظات وتوجيهات المدير لأمين الخزنة</label>
                        <input type="text" name="notes" class="erp-form-control" placeholder="تعليمات إضافية لأمين الخزنة">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 mt-2 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check-circle" size="18" />
                        <span>إصدار إذن الصرف وإرساله لحظياً لأمين الخزنة</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
