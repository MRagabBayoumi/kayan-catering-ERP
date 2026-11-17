<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    @vite(['resources/css/print/invoice-document.css', 'resources/js/pages/invoice-print.js'])
</head>
<body class="theme-navy">
    <div class="print-toolbar no-print">
        <div>
            <strong>{{ $title }}</strong>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-action="print-now" class="btn btn-primary">
                طباعة محضر تسوية عهدة السائق
            </button>
            <button type="button" data-action="close-window" class="btn btn-secondary">
                إغلاق
            </button>
        </div>
    </div>

    <div class="document-page">
        <!-- Letterhead Header -->
        <div class="doc-header">
            <div>
                <h1 class="text-xl font-bold">{{ $company->company_name_ar ?? 'شركة كيان لتوريد الأغذية وإعاشة المستشفيات' }}</h1>
                <p class="text-xs text-slate-500 mt-1">إدارة الحسابات والمخازن • محضر تسوية ومطابقة عهدة سيارة التوريد</p>
            </div>
            <div class="text-left font-mono">
                <span class="text-xs text-slate-400 block">رقم محضر التسوية</span>
                <span class="text-lg font-bold text-sky-800">{{ $settlement->settlement_number }}</span>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- Metadata -->
        <div class="grid grid-cols-3 gap-4 text-xs py-3">
            <div>
                <span class="text-slate-400 block">السائق صاحب العهدة:</span>
                <strong class="text-slate-900 text-sm">{{ $settlement->driver->name }}</strong>
                <span class="text-slate-500 block">إذن الصرف: {{ $settlement->dispatchOrder->dispatch_number ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">المستشفى المورد إليه:</span>
                <strong class="text-slate-900">{{ $settlement->dispatchOrder->hospital->name ?? '-' }}</strong>
                <span class="text-slate-500 block">إشعار التسليم POD: {{ $settlement->deliveryReceipt->receipt_number ?? '-' }}</span>
            </div>
            <div class="text-left">
                <span class="text-slate-400 block">تاريخ التسوية:</span>
                <strong class="font-numeric">{{ $settlement->settled_at?->format('Y-m-d H:i') }}</strong>
                <span class="text-slate-500 block font-mono">قيد ERP: {{ $settlement->erp_journal_voucher }}</span>
            </div>
        </div>

        <!-- Settlement Summary KPI Grid -->
        <div class="grid grid-cols-4 gap-3 my-4 p-4 bg-slate-50 border border-slate-200 rounded-lg text-center text-xs">
            <div>
                <span class="text-slate-500 block">إجمالي الكمية المحملة</span>
                <strong class="font-numeric text-base text-slate-800">{{ number_format($settlement->total_dispatched_qty, 2) }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block">المسلّم للمستشفى</span>
                <strong class="font-numeric text-base text-emerald-700">{{ number_format($settlement->total_delivered_qty, 2) }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block">المرتجع للمخزن</span>
                <strong class="font-numeric text-base text-amber-700">{{ number_format($settlement->total_returned_qty, 2) }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block">تكلفة البضاعة الموردة (COGS)</span>
                <strong class="font-numeric text-base text-sky-800">{{ number_format($settlement->total_cogs_cost, 2) }} ج.م</strong>
            </div>
        </div>

        @if($settlement->deliveryReceipt)
            <!-- Reconciled Items Table -->
            <table class="doc-table mt-4">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الصنف</th>
                        <th>المحمل بالسيارة</th>
                        <th>المسلّم للموقع</th>
                        <th>المرتجع للمخزن</th>
                        <th>المرفوض / عجز</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($settlement->deliveryReceipt->items as $idx => $item)
                        <tr>
                            <td class="font-numeric">{{ $idx + 1 }}</td>
                            <td class="font-bold">{{ $item->product->name }}</td>
                            <td class="font-numeric">{{ number_format($item->dispatched_qty, 2) }}</td>
                            <td class="font-numeric font-bold text-emerald-800">{{ number_format($item->actual_delivered_qty, 2) }}</td>
                            <td class="font-numeric text-amber-700">{{ number_format($item->returned_qty, 2) }}</td>
                            <td class="font-numeric text-rose-700">{{ number_format($item->rejected_qty, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-8 mt-12 pt-6 border-t border-slate-200 text-center text-xs">
            <div>
                <span class="text-slate-500 block mb-8">إقرار السائق بإخلاء طرفه من العهدة وتوريد المرتجع</span>
                <strong class="text-slate-800">{{ $settlement->driver->name }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">محاسب المخازن والمالية المعتمد</span>
                <strong class="text-slate-800">{{ $settlement->accountant->name ?? 'محاسب المخازن' }}</strong>
            </div>
        </div>
    </div>
</body>
</html>
