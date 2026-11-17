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
                طباعة إذن الفحص والاستلام (GRN)
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
                <p class="text-xs text-slate-500 mt-1">إدارة المخازن ورقابة الجودة • محضر استلام وفحص توريدات خامات (GRN)</p>
            </div>
            <div class="text-left font-mono">
                <span class="text-xs text-slate-400 block">إذن استلام مخزني</span>
                <span class="text-lg font-bold text-emerald-800">{{ $grn->grn_number }}</span>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- Metadata -->
        <div class="grid grid-cols-3 gap-4 text-xs py-3">
            <div>
                <span class="text-slate-400 block">المورد:</span>
                <strong class="text-slate-900 text-sm">{{ $grn->supplier->name }}</strong>
                <span class="text-slate-500 block">فاتورة المورد: {{ $grn->supplier_invoice_reference ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">المخزن المستلم:</span>
                <strong class="text-slate-900">{{ $grn->warehouse->name ?? '-' }}</strong>
                <span class="text-slate-500 block">أمر الشراء المرتبط: {{ $grn->purchaseOrder->po_number ?? 'توريد مباشر' }}</span>
            </div>
            <div class="text-left">
                <span class="text-slate-400 block">تاريخ وساعة الاستلام:</span>
                <strong class="font-numeric">{{ $grn->receipt_date?->format('Y-m-d') }}</strong>
                <span class="text-emerald-700 block font-semibold">تم الفحص والمطابقة للجودة</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="doc-table mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصنف</th>
                    <th>رقم التشغيلة (Batch)</th>
                    <th>تاريخ الصلاحية</th>
                    <th>المسلّم</th>
                    <th>المقبول</th>
                    <th>المرفوض</th>
                    <th>سبب الرفض</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $idx => $item)
                    <tr>
                        <td class="font-numeric">{{ $idx + 1 }}</td>
                        <td class="font-bold">{{ $item->product->name }}</td>
                        <td class="font-mono text-xs">{{ $item->batch_number }}</td>
                        <td class="font-numeric text-xs">{{ $item->expiry_date?->format('Y-m-d') }}</td>
                        <td class="font-numeric">{{ number_format($item->quantity_delivered, 2) }}</td>
                        <td class="font-numeric font-bold text-emerald-800">{{ number_format($item->quantity_accepted, 2) }}</td>
                        <td class="font-numeric text-rose-700">{{ number_format($item->quantity_rejected, 2) }}</td>
                        <td class="text-xs text-slate-500">{{ $item->rejection_reason ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($grn->inspection_notes)
            <div class="mt-4 p-3 bg-slate-50 border border-slate-200 rounded text-xs text-slate-700">
                <strong>تقرير الفحص الفني:</strong> {{ $grn->inspection_notes }}
            </div>
        @endif

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-8 mt-12 pt-6 border-t border-slate-200 text-center text-xs">
            <div>
                <span class="text-slate-500 block mb-8">أمين المخزن المستلم</span>
                <strong class="text-slate-800">{{ $grn->receiver->name ?? 'أمين المخزن' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">مسؤول الجودة والفحص</span>
                <strong class="text-slate-800">{{ $grn->inspector->name ?? 'أخصائي سلامة الغذاء' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">مندوب شركة التوريد</span>
                <span class="text-slate-400">..............................</span>
            </div>
        </div>
    </div>
</body>
</html>
