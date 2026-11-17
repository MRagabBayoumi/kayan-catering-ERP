<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    @vite(['resources/css/print/invoice-document.css', 'resources/js/pages/invoice-print.js'])
</head>
<body class="theme-emerald">
    <div class="print-toolbar no-print">
        <div>
            <strong>{{ $title }}</strong>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-action="print-now" class="btn btn-primary">
                طباعة إشعار التسليم الرسمي (POD)
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
                <p class="text-xs text-slate-500 mt-1">إشعار تسليم وتوريد رسمي معتمد • Proof of Delivery (POD)</p>
            </div>
            <div class="text-left font-mono">
                <span class="text-xs text-slate-400 block">إشعار تسليم وتوريد</span>
                <span class="text-lg font-bold text-emerald-800">{{ $receipt->receipt_number }}</span>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- Metadata -->
        <div class="grid grid-cols-3 gap-4 text-xs py-3">
            <div>
                <span class="text-slate-400 block">المستشفى والجهة المستلمة:</span>
                <strong class="text-slate-900 text-sm">{{ $receipt->hospital->name }}</strong>
                <span class="text-slate-500 block">العنوان: {{ $receipt->hospital->address ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">السائق المورّد:</span>
                <strong class="text-slate-900">{{ $receipt->driver->name ?? '-' }}</strong>
                <span class="text-slate-500 block">إذن الصرف: {{ $receipt->dispatchOrder->dispatch_number ?? '-' }}</span>
            </div>
            <div class="text-left">
                <span class="text-slate-400 block">تاريخ وساعة التسليم الفعلي:</span>
                <strong class="font-numeric">{{ $receipt->received_at?->format('Y-m-d H:i') }}</strong>
                <span class="text-emerald-700 block font-semibold">استلام مؤكد ومعتمد بالموقع</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="doc-table mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>بيان الصنف والخامة</th>
                    <th>الكمية المنصرفة بالسيارة</th>
                    <th>الكمية المستلمة فعلياً بالموقع</th>
                    <th>المرتجع مع السائق</th>
                    <th>الكمية المرفوضة</th>
                    <th>ملاحظات وسبب الرفض</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->items as $idx => $item)
                    <tr>
                        <td class="font-numeric">{{ $idx + 1 }}</td>
                        <td class="font-bold">{{ $item->product->name }}</td>
                        <td class="font-numeric">{{ number_format($item->dispatched_qty, 2) }}</td>
                        <td class="font-numeric font-bold text-emerald-800">{{ number_format($item->actual_delivered_qty, 2) }}</td>
                        <td class="font-numeric text-amber-700">{{ number_format($item->returned_qty, 2) }}</td>
                        <td class="font-numeric text-rose-700">{{ number_format($item->rejected_qty, 2) }}</td>
                        <td class="text-xs text-slate-500">{{ $item->rejection_reason ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures & Official Stamp -->
        <div class="grid grid-cols-2 gap-8 mt-12 pt-6 border-t border-slate-200 text-center text-xs">
            <div>
                <span class="text-slate-500 block mb-8">مسلم البضاعة (مندوب / سائق الشركة)</span>
                <strong class="text-slate-800">{{ $receipt->driver->name ?? 'سائق التوريد' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">استلمت أنا مسؤول المستشفى الأصناف أعلاه بحالة جيدة</span>
                <strong class="text-emerald-800 text-sm">{{ $receipt->receiving_officer_name }}</strong>
                <span class="text-slate-400 block mt-4">توقيع وختم المستشفى: ..............................</span>
            </div>
        </div>
    </div>
</body>
</html>
