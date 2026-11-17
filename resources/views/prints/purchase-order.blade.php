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
    <!-- Screen Print Toolbar -->
    <div class="print-toolbar no-print">
        <div>
            <strong>{{ $title }}</strong>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-action="print-now" class="btn btn-primary">
                طباعة أمر الشراء
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
                <p class="text-xs text-slate-500 mt-1">{{ $company->address ?? 'مصر - إدارة التوريدات الغذائية المركزية' }} • س.ت: {{ $company->commercial_register ?? '987654' }}</p>
            </div>
            <div class="text-left font-mono">
                <span class="text-xs text-slate-400 block">أمر شراء معمد (Purchase Order)</span>
                <span class="text-lg font-bold text-sky-800">{{ $po->po_number }}</span>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- Document Metadata -->
        <div class="grid grid-cols-3 gap-4 text-xs py-3">
            <div>
                <span class="text-slate-400 block">المورد المعتمد:</span>
                <strong class="text-slate-900 text-sm">{{ $po->supplier->name }}</strong>
                <span class="text-slate-500 block">كود: {{ $po->supplier->code }} • تليفون: {{ $po->supplier->phone ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">المخزن المستلم:</span>
                <strong class="text-slate-900">{{ $po->warehouse->name ?? 'المخزن الرئيسي العام' }}</strong>
                <span class="text-slate-500 block">تاريخ التسليم المتوقع: {{ $po->expected_delivery_date?->format('Y-m-d') }}</span>
            </div>
            <div class="text-left">
                <span class="text-slate-400 block">تاريخ الأمر:</span>
                <strong class="font-numeric">{{ $po->order_date?->format('Y-m-d') }}</strong>
                <span class="text-slate-500 block">الحالة: معتمد رسمياً</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="doc-table mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الصنف</th>
                    <th>بيان الصنف والخامة</th>
                    <th>الكمية المعمدة</th>
                    <th>الوحدة</th>
                    <th>سعر الوحدة</th>
                    <th>إجمالي القيمة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($po->items as $idx => $item)
                    <tr>
                        <td class="font-numeric">{{ $idx + 1 }}</td>
                        <td class="font-mono text-xs">{{ $item->product->sku ?? '-' }}</td>
                        <td class="font-bold">{{ $item->product->name }}</td>
                        <td class="font-numeric font-bold">{{ number_format($item->quantity_ordered, 2) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="font-numeric">{{ number_format($item->unit_price, 2) }} ج.م</td>
                        <td class="font-numeric font-bold">{{ number_format($item->total_price, 2) }} ج.م</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-left font-bold">الإجمالي قبل الضريبة:</td>
                    <td class="font-numeric font-bold">{{ number_format($po->subtotal, 2) }} ج.م</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-left font-bold">ضريبة القيمة المضافة:</td>
                    <td class="font-numeric font-bold">{{ number_format($po->tax_amount, 2) }} ج.م</td>
                </tr>
                <tr class="doc-total-row">
                    <td colspan="6" class="text-left font-bold text-sm">صافي إجمالي أمر الشراء المعمد:</td>
                    <td class="font-numeric font-bold text-sm text-sky-900">{{ number_format($po->total_amount, 2) }} ج.م</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures & Authorization Box -->
        <div class="grid grid-cols-3 gap-8 mt-12 pt-6 border-t border-slate-200 text-center text-xs">
            <div>
                <span class="text-slate-500 block mb-8">إعداد مسؤول المشتريات</span>
                <strong class="text-slate-800">{{ $po->creator->name ?? 'مسؤول المشتريات' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">اعتماد الإدارة المالية</span>
                <strong class="text-slate-800">{{ $po->approver->name ?? 'المدير المالي' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 block mb-8">توقيع وختم المورد بالاستلام</span>
                <span class="text-slate-400">..............................</span>
            </div>
        </div>
    </div>
</body>
</html>
