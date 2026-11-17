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
                طباعة إذن الصرف والتحميل
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
                <p class="text-xs text-slate-500 mt-1">إدارة العمليات واللوجستيات • إذن تحميل وصرف بضاعة لسيارة المندوب</p>
            </div>
            <div class="text-left font-mono">
                <span class="text-xs text-slate-400 block">إذن تحميل وصرف</span>
                <span class="text-lg font-bold text-sky-800">{{ $dispatch->dispatch_number }}</span>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- Metadata -->
        <div class="grid grid-cols-3 gap-4 text-xs py-3">
            <div>
                <span class="text-slate-400 block">جهة التوريد (المستشفى):</span>
                <strong class="text-slate-900 text-sm">{{ $dispatch->hospital->name }}</strong>
                <span class="text-slate-500 block">الوجبة / المقنن: {{ $dispatch->recipe->name ?? 'طلبية عامة' }} ({{ $dispatch->planned_meals_count ?? '-' }} وجبة)</span>
            </div>
            <div>
                <span class="text-slate-400 block">السائق والسيارة المستلمة:</span>
                <strong class="text-slate-900">{{ $dispatch->driver->name ?? 'مندوب التوزيع' }}</strong>
                <span class="text-slate-500 block">{{ $dispatch->transitWarehouse->name ?? 'سيارة التوزيع' }}</span>
            </div>
            <div class="text-left">
                <span class="text-slate-400 block">تاريخ وساعة الصرف:</span>
                <strong class="font-numeric">{{ $dispatch->dispatch_date?->format('Y-m-d H:i') }}</strong>
                <span class="text-slate-500 block">المخزن المنصرف منه: {{ $dispatch->sourceWarehouse->name ?? '-' }}</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="doc-table mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الصنف</th>
                    <th>اسم الصنف والخامة</th>
                    <th>الكمية المخططة</th>
                    <th>كمية الاحتياط (Buffer)</th>
                    <th>إجمالي المحمل بالسيارة</th>
                    <th>الوحدة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dispatch->items as $idx => $item)
                    <tr>
                        <td class="font-numeric">{{ $idx + 1 }}</td>
                        <td class="font-mono text-xs">{{ $item->product->sku ?? '-' }}</td>
                        <td class="font-bold">{{ $item->product->name }}</td>
                        <td class="font-numeric">{{ number_format($item->planned_qty, 2) }}</td>
                        <td class="font-numeric">{{ number_format($item->buffer_qty, 2) }}</td>
                        <td class="font-numeric font-bold text-sky-900">{{ number_format($item->total_dispatched_qty, 2) }}</td>
                        <td>{{ $item->product->unit ?? 'كجم' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-8 mt-12 pt-6 border-t border-slate-200 text-center text-xs">
            <div>
                <span class="text-slate-500 block mb-8">أمين المخزن الرئيسي المنصرف منه</span>
                <strong class="text-slate-800">{{ $dispatch->storekeeper->name ?? 'أمين المخزن' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">استلمت أنا السائق / المندوب البضاعة كاملة</span>
                <strong class="text-slate-800">{{ $dispatch->driver->name ?? 'مندوب التوريد' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">اعتماد مدير الحركة والمخازن</span>
                <span class="text-slate-400">..............................</span>
            </div>
        </div>
    </div>
</body>
</html>
